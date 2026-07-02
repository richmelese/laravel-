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

    // =========================================================================
    // BOOKING HISTORY  –  GET /api-admin/booking/history
    // =========================================================================

    /**
     * Vendor's full booking history with rich filters.
     *
     * Query params:
     *   s            – search by name / email / phone / code
     *   status       – filter by booking status
     *   object_model – service type (hotel, car, tour …)
     *   date_from    – booking created_at >= YYYY-MM-DD
     *   date_to      – booking created_at <= YYYY-MM-DD
     *   start_date   – service start_date >= YYYY-MM-DD
     *   end_date     – service end_date   <= YYYY-MM-DD
     *   per_page     – items per page (max 100, default 20)
     */
    public function history(Request $request)
    {
        $this->checkPermission('booking_view');

        $query = Booking::where('status', '!=', 'draft')
            ->whereIn('object_model', array_keys(get_bookable_services()));

        // Vendor scope
        if (!$this->hasPermission('booking_manage_others')) {
            $query->where('vendor_id', Auth::id());
        } elseif ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->query('vendor_id'));
        }

        // Search
        if ($s = $request->query('s')) {
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', '%' . $s . '%')
                  ->orWhere('last_name',  'like', '%' . $s . '%')
                  ->orWhere('email',      'like', '%' . $s . '%')
                  ->orWhere('phone',      'like', '%' . $s . '%')
                  ->orWhere('code',       'like', '%' . $s . '%');
            });
        }

        // Filters
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($model = $request->query('object_model')) {
            $query->where('object_model', $model);
        }
        if ($from = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($start = $request->query('start_date')) {
            $query->whereDate('start_date', '>=', $start);
        }
        if ($end = $request->query('end_date')) {
            $query->whereDate('end_date', '<=', $end);
        }

        $query->orderBy('id', 'desc');

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $rows    = $query->with(['vendor'])->paginate($perPage);

        return response()->json([
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
            'filters' => [
                'statuses'       => config('booking.statuses'),
                'service_types'  => array_keys(get_bookable_services()),
            ],
        ]);
    }

    // =========================================================================
    // BOOKING REPORT  –  GET /api-admin/booking/report
    // =========================================================================

    /**
     * Booking summary / stats for the vendor (or all vendors for admin).
     *
     * Query params:
     *   date_from    – YYYY-MM-DD  (default: start of current month)
     *   date_to      – YYYY-MM-DD  (default: today)
     *   object_model – filter by service type
     *   group_by     – "day" | "month" (default: day)
     */
    public function report(Request $request)
    {
        $this->checkPermission('booking_view');

        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->query('date_to',   now()->toDateString());

        $base = Booking::where('status', '!=', 'draft')
            ->whereIn('object_model', array_keys(get_bookable_services()))
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        // Vendor scope
        if (!$this->hasPermission('booking_manage_others')) {
            $base->where('vendor_id', Auth::id());
        } elseif ($request->filled('vendor_id')) {
            $base->where('vendor_id', $request->query('vendor_id'));
        }

        if ($model = $request->query('object_model')) {
            $base->where('object_model', $model);
        }

        // ── Summary totals ────────────────────────────────────────────────────
        $summary = (clone $base)->selectRaw('
            COUNT(*)                                          AS total_bookings,
            SUM(CASE WHEN status = "completed"  THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = "confirmed"  THEN 1 ELSE 0 END) AS confirmed,
            SUM(CASE WHEN status = "cancelled"  THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN status = "pending"    THEN 1 ELSE 0 END) AS pending,
            COALESCE(SUM(total), 0)                           AS total_revenue,
            COALESCE(SUM(CASE WHEN status != "cancelled" THEN total ELSE 0 END), 0) AS confirmed_revenue
        ')->first();

        // ── By service type ───────────────────────────────────────────────────
        $byService = (clone $base)->selectRaw('
            object_model,
            COUNT(*)          AS total_bookings,
            COALESCE(SUM(CASE WHEN status != "cancelled" THEN total ELSE 0 END), 0) AS revenue
        ')->groupBy('object_model')->get();

        // ── By status ─────────────────────────────────────────────────────────
        $byStatus = (clone $base)->selectRaw('
            status,
            COUNT(*) AS total_bookings,
            COALESCE(SUM(total), 0) AS revenue
        ')->groupBy('status')->get();

        // ── Timeline (daily or monthly) ───────────────────────────────────────
        $groupBy  = $request->query('group_by', 'day');
        $dateExpr = $groupBy === 'month'
            ? "DATE_FORMAT(created_at, '%Y-%m')"
            : "DATE(created_at)";

        $timeline = (clone $base)->selectRaw("
            {$dateExpr}              AS period,
            COUNT(*)                 AS total_bookings,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END), 0) AS revenue
        ")->groupByRaw($dateExpr)->orderByRaw($dateExpr)->get();

        return response()->json([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'summary' => [
                'total_bookings'    => (int) $summary->total_bookings,
                'completed'         => (int) $summary->completed,
                'confirmed'         => (int) $summary->confirmed,
                'cancelled'         => (int) $summary->cancelled,
                'pending'           => (int) $summary->pending,
                'total_revenue'     => (float) $summary->total_revenue,
                'confirmed_revenue' => (float) $summary->confirmed_revenue,
            ],
            'by_service' => $byService,
            'by_status'  => $byStatus,
            'timeline'   => $timeline,
        ]);
    }
}
