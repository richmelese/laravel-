<?php
namespace Modules\Booking\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\AdminController;
use Modules\Booking\Emails\NewBookingEmail;
use Modules\Booking\Events\BookingUpdatedEvent;
use Modules\Booking\Models\Booking;

class BookingController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu(route('booking.admin.booking'));
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    public function index(Request $request)
    {
        $this->checkPermission('booking_view');
        $query = Booking::where('status', '!=', 'draft');
        if (!empty($request->s)) {
            if( is_numeric($request->s) ){
                $query->Where('id', '=', $request->s);
            }else{
                $query->where(function ($query) use ($request) {
                    $query->where('first_name', 'like', '%' . $request->s . '%')
                        ->orWhere('last_name', 'like', '%' . $request->s . '%')
                        ->orWhere('email', 'like', '%' . $request->s . '%')
                        ->orWhere('phone', 'like', '%' . $request->s . '%')
                        ->orWhere('address', 'like', '%' . $request->s . '%')
                        ->orWhere('address2', 'like', '%' . $request->s . '%');
                });
            }
        }
        if ($this->hasPermission('booking_manage_others')) {
            if (!empty($request->vendor_id)) {
                $query->where('vendor_id', $request->vendor_id);
            }
        } else {
            $query->where('vendor_id', Auth::id());
        }
        $query->whereIn('object_model', array_keys(get_bookable_services()));
        $query->orderBy('id','desc');
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $data = [
            'rows'                  => $query->with(['vendor'])->paginate($perPage),
            'page_title'            => __("All Bookings"),
            'booking_manage_others' => $this->hasPermission('booking_manage_others'),
            'booking_update'        => $this->hasPermission('booking_update'),
            'statues'               => config('booking.statuses')
        ];
        if ($this->isApiRequest($request)) {
            $rows = $data['rows'];
            return response()->json([
                'data' => $rows->items(),
                'meta' => [
                    'current_page' => $rows->currentPage(),
                    'per_page'     => $rows->perPage(),
                    'total'        => $rows->total(),
                    'last_page'    => $rows->lastPage(),
                ],
                'booking_manage_others' => $data['booking_manage_others'],
                'booking_update'        => $data['booking_update'],
                'statuses'              => $data['statues'],
            ]);
        }
        return view('Booking::admin.booking.index', $data);
    }

    public function show(Request $request, $id)
    {
        $this->checkPermission('booking_view');
        $query = Booking::query()->where('id', $id)->where('status', '!=', 'draft');
        if (!$this->hasPermission('booking_manage_others')) {
            $query->where('vendor_id', Auth::id());
        }
        $query->whereIn('object_model', array_keys(get_bookable_services()));
        $booking = $query->with(['vendor'])->first();
        if (empty($booking)) {
            return response()->json(['message' => __('Not found')], 404);
        }
        return response()->json([
            'data' => $booking,
            'statuses'       => config('booking.statuses'),
            'booking_update' => $this->hasPermission('booking_update'),
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!$this->hasPermission('booking_manage_others')) {
            $this->checkPermission('booking_update');
        }
        $query = Booking::query()->where('id', $id)->where('status', '!=', 'draft');
        if (!$this->hasPermission('booking_manage_others')) {
            $query->where('vendor_id', Auth::id());
        }
        $query->whereIn('object_model', array_keys(get_bookable_services()));
        $booking = $query->first();
        if (empty($booking)) {
            return response()->json(['message' => __('Not found')], 404);
        }
        $previousStatus = $booking->status;
        $validated = $request->validate([
            'status'       => ['required', Rule::in(config('booking.statuses'))],
            'object_model' => ['required', Rule::in(array_keys(get_bookable_services()))],
            'object_id'    => ['required', 'integer'],
        ]);
        $booking->update([
            'status'       => $validated['status'],
            'object_model' => $validated['object_model'],
            'object_id'    => $validated['object_id'],
        ]);
        if ($validated['status'] == Booking::CANCELLED && $previousStatus !== Booking::CANCELLED) {
            $booking->tryRefundToWallet();
        }
        event(new BookingUpdatedEvent($booking));
        return response()->json([
            'message' => __('Booking updated successfully'),
            'data'    => $booking->fresh(['vendor']),
        ]);
    }

    public function bulkEdit(Request $request)
    {
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('No items selected')], 422);
            }
            return redirect()->back()->with('error', __('No items selected'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Please select action')], 422);
            }
            return redirect()->back()->with('error', __('Please select action'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = Booking::where("id", $id);
                if (!$this->hasPermission('booking_manage_others')) {
                    $query->where("vendor_id", Auth::id());
                }
                $row = $query->first();
                if(!empty($row)){
                    $row->delete();
                    event(new BookingUpdatedEvent($row));

                }
            }
        } else {
            foreach ($ids as $id) {
                $query = Booking::where("id", $id);
                if (!$this->hasPermission('booking_manage_others')) {
                    $query->where("vendor_id", Auth::id());
                    $this->checkPermission('booking_update');
                }
                $item = $query->first();
                if(!empty($item)){
                    $item->status = $action;
                    $item->save();

                    if($action == Booking::CANCELLED) $item->tryRefundToWallet();
                    event(new BookingUpdatedEvent($item));
                }
            }
        }
        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Update success')]);
        }
        return redirect()->back()->with('success', __('Update success'));
    }

    public function email_preview(Request $request, $id)
    {
        $booking = Booking::find($id);
        return (new NewBookingEmail($booking))->render();
    }
}
