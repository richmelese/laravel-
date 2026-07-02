<?php

namespace Modules\MedicalBooking\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\MedicalBooking\Models\MedicalBooking;

class MedicalBookingController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu(route('medical_booking.admin.index'));
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    /**
     * GET /api-admin/medical-booking
     * List all bookings with filters and pagination.
     */
    public function index(Request $request)
    {
        $this->checkPermission('medical_booking_view');

        $query = MedicalBooking::query()->orderByDesc('id');

        if ($s = $request->query('s')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'LIKE', '%' . $s . '%')
                  ->orWhere('email', 'LIKE', '%' . $s . '%')
                  ->orWhere('phone', 'LIKE', '%' . $s . '%');
            });
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->query('request_type')) {
            $query->where('request_type', $type);
        }
        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $rows    = $query->paginate($perPage);

        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => $rows->items(),
                'meta' => [
                    'current_page' => $rows->currentPage(),
                    'per_page'     => $rows->perPage(),
                    'total'        => $rows->total(),
                    'last_page'    => $rows->lastPage(),
                ],
                'filters' => [
                    'statuses'      => MedicalBooking::getStatuses(),
                    'request_types' => MedicalBooking::getRequestTypes(),
                    'priorities'    => MedicalBooking::getPriorityLevels(),
                ],
            ]);
        }

        return view('MedicalBooking::admin.index', [
            'rows'       => $rows,
            'page_title' => __('Medical Booking Management'),
            'breadcrumbs'=> [
                ['name' => __('Medical Bookings'), 'url' => route('medical_booking.admin.index')],
                ['name' => __('All'), 'class' => 'active'],
            ],
        ]);
    }

    /**
     * GET /api-admin/medical-booking/{id}
     * Get a single booking.
     */
    public function show(Request $request, $id)
    {
        $this->checkPermission('medical_booking_view');

        $row = MedicalBooking::find($id);
        if (empty($row)) {
            return response()->json(['message' => __('Booking not found')], 404);
        }

        return response()->json(['data' => $row]);
    }

    /**
     * PUT/PATCH /api-admin/medical-booking/{id}
     * Update booking status and admin notes.
     */
    public function update(Request $request, $id)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $this->checkPermission('medical_booking_update');

        $row = MedicalBooking::find($id);
        if (empty($row)) {
            return response()->json(['message' => __('Booking not found')], 404);
        }

        $request->validate([
            'status'         => 'nullable|in:pending,confirmed,in_progress,completed,cancelled',
            'admin_note'     => 'nullable|string',
            'priority'       => 'nullable|in:normal,urgent,emergency',
            'fee'            => 'nullable|numeric|min:0',
            'currency'       => 'nullable|string|max:10',
            'payment_status' => 'nullable|in:unpaid,pending,paid,refunded,failed',
            'payment_gateway'=> 'nullable|string|max:100',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $row->fill($request->only([
            'status', 'admin_note', 'priority',
            'fee', 'currency', 'payment_status', 'payment_gateway', 'payment_reference',
        ]));

        if ($request->input('payment_status') === 'paid' && empty($row->paid_at)) {
            $row->paid_at = now();
        }
        $row->save();

        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => __('Booking updated'),
                'data'    => $row,
            ]);
        }

        return back()->with('success', __('Booking updated'));
    }

    /**
     * POST /api-admin/medical-booking/{id}/mark-paid
     * Manually mark a booking as paid.
     */
    public function markPaid(Request $request, $id)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $this->checkPermission('medical_booking_update');

        $row = MedicalBooking::find($id);
        if (empty($row)) {
            return response()->json(['message' => __('Booking not found')], 404);
        }

        $request->validate([
            'payment_gateway'   => 'nullable|string|max:100',
            'payment_reference' => 'nullable|string|max:255',
            'fee'               => 'nullable|numeric|min:0',
            'currency'          => 'nullable|string|max:10',
        ]);

        $row->payment_status    = 'paid';
        $row->paid_at           = now();
        $row->payment_gateway   = $request->input('payment_gateway', $row->payment_gateway ?? 'manual');
        $row->payment_reference = $request->input('payment_reference', $row->payment_reference);
        if ($request->filled('fee'))      $row->fee      = $request->input('fee');
        if ($request->filled('currency')) $row->currency = $request->input('currency');
        $row->save();

        return response()->json([
            'message' => __('Booking marked as paid'),
            'data'    => $row,
        ]);
    }

    /**
     * POST /api-admin/medical-booking/{id}/set-fee
     * Set the fee amount for a booking (admin sets the price before customer pays).
     */
    public function setFee(Request $request, $id)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $this->checkPermission('medical_booking_update');

        $row = MedicalBooking::find($id);
        if (empty($row)) {
            return response()->json(['message' => __('Booking not found')], 404);
        }

        $request->validate([
            'fee'      => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
        ]);

        $row->fee      = $request->input('fee');
        $row->currency = $request->input('currency', $row->currency ?? 'USD');
        $row->save();

        return response()->json([
            'message' => __('Fee updated'),
            'data'    => [
                'id'       => $row->id,
                'fee'      => $row->fee,
                'currency' => $row->currency,
            ],
        ]);
    }

    /**
     * DELETE /api-admin/medical-booking/{id}
     * Soft-delete a booking.
     */
    public function destroy(Request $request, $id)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $this->checkPermission('medical_booking_delete');

        $row = MedicalBooking::find($id);
        if (empty($row)) {
            return response()->json(['message' => __('Booking not found')], 404);
        }

        $row->delete();

        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Booking deleted')]);
        }

        return redirect(route('medical_booking.admin.index'))->with('success', __('Booking deleted'));
    }

    /**
     * POST /api-admin/medical-booking/bulk-action
     * Bulk update status or delete.
     */
    public function bulkEdit(Request $request)
    {
        if (is_demo_mode()) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
            }
            return redirect()->back()->with('error', __('DEMO MODE: You are not allowed to change data'));
        }

        $ids    = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) || !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('No items selected!')], 422);
            }
            return redirect()->back()->with('error', __('No items selected!'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Please select an action!')], 422);
            }
            return redirect()->back()->with('error', __('Please select an action!'));
        }

        switch ($action) {
            case 'delete':
                $this->checkPermission('medical_booking_delete');
                MedicalBooking::whereIn('id', $ids)->delete();
                break;
            default:
                $this->checkPermission('medical_booking_update');
                MedicalBooking::whereIn('id', $ids)->update(['status' => $action]);
                break;
        }

        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Update success!')]);
        }
        return redirect()->back()->with('success', __('Update success!'));
    }
}
