<?php

namespace Modules\Hospital\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Hospital\Models\Hospital;
use Modules\Hospital\Models\HospitalStaff;
use Modules\MedicalBooking\Models\MedicalBooking;

class HospitalController extends AdminController
{
    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    /**
     * True if the current user is a super-admin who can manage any hospital.
     */
    protected function isAdmin(): bool
    {
        return $this->hasPermission('hospital_manage_others');
    }

    /**
     * Resolve a hospital and check vendor access.
     * Returns the hospital or a 403/404 response.
     */
    protected function resolveHospital($id)
    {
        $hospital = Hospital::find($id);
        if (!$hospital) {
            return response()->json(['message' => __('Hospital not found')], 404);
        }
        if (!$this->isAdmin() && !$hospital->canManagedBy(Auth::user())) {
            return response()->json(['message' => __('You do not have permission to manage this hospital')], 403);
        }
        return $hospital;
    }

    // =========================================================================
    // HOSPITAL CRUD
    // =========================================================================

    /**
     * GET /api-admin/hospital
     * Admin: all hospitals. Vendor: only their own.
     */
    public function index(Request $request)
    {
        $this->checkPermission('hospital_view');

        $query = Hospital::with('author')->orderByDesc('id');

        // Vendor: scope to their own hospitals only
        if (!$this->isAdmin()) {
            $userId    = Auth::id();
            $staffHospitalIds = HospitalStaff::where('user_id', $userId)->pluck('hospital_id');
            $query->where(function ($q) use ($userId, $staffHospitalIds) {
                $q->where('author_id', $userId)
                  ->orWhereIn('id', $staffHospitalIds);
            });
        }

        if ($s = $request->query('s')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'LIKE', '%' . $s . '%')
                  ->orWhere('city', 'LIKE', '%' . $s . '%');
            });
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $rows    = $query->paginate($perPage);

        return response()->json([
            'data' => collect($rows->items())->map(fn($h) => $this->formatHospital($h)),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
            'statuses' => Hospital::getStatuses(),
        ]);
    }

    /**
     * GET /api-admin/hospital/{id}
     */
    public function show(Request $request, $id)
    {
        $this->checkPermission('hospital_view');

        $hospital = $this->resolveHospital($id);
        if ($hospital instanceof \Illuminate\Http\JsonResponse) return $hospital;

        $hospital->load('author', 'staff.user');

        return response()->json([
            'data' => $this->formatHospital($hospital, true),
        ]);
    }

    /**
     * POST /api-admin/hospital/store/0  → create
     * POST /api-admin/hospital/store/{id} → update
     */
    public function store(Request $request, $id = 0)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: Changes not allowed')], 403);
        }

        $request->validate([
            'name'           => 'required|string|max:255',
            'status'         => 'nullable|in:publish,draft',
            'description'    => 'nullable|string',
            'address'        => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'country'        => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'website'        => 'nullable|string|max:255',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
            'image_id'       => 'nullable|integer',
            'author_id'      => 'nullable|integer',
            'booking_amount' => 'nullable|numeric|min:0',
            'currency'       => 'nullable|string|max:10',
        ]);

        if ($id > 0) {
            $this->checkPermission('hospital_update');
            $hospital = $this->resolveHospital($id);
            if ($hospital instanceof \Illuminate\Http\JsonResponse) return $hospital;
        } else {
            $this->checkPermission('hospital_create');
            $hospital            = new Hospital();
            $hospital->author_id = $request->input('author_id', Auth::id());
        }

        $hospital->fill($request->only([
            'name', 'description', 'address', 'city', 'country',
            'phone', 'email', 'website', 'latitude', 'longitude',
            'image_id', 'status', 'booking_amount', 'currency',
        ]));

        // Admin can reassign author; vendor cannot
        if ($this->isAdmin() && $request->filled('author_id')) {
            $hospital->author_id = $request->input('author_id');
        }

        $hospital->status = $request->input('status', 'publish');
        $hospital->save();

        return response()->json([
            'message' => $id > 0 ? __('Hospital updated') : __('Hospital created'),
            'data'    => $this->formatHospital($hospital),
        ], $id > 0 ? 200 : 201);
    }

    /**
     * DELETE /api-admin/hospital/{id}
     */
    public function destroy(Request $request, $id)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: Changes not allowed')], 403);
        }

        $this->checkPermission('hospital_delete');

        $hospital = $this->resolveHospital($id);
        if ($hospital instanceof \Illuminate\Http\JsonResponse) return $hospital;

        $hospital->delete();

        return response()->json(['message' => __('Hospital deleted')]);
    }

    /**
     * POST /api-admin/hospital/bulk-action
     * Body: { ids: [...], action: "publish" | "draft" | "delete" }
     */
    public function bulkAction(Request $request)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: Changes not allowed')], 403);
        }

        $ids    = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) || !is_array($ids)) {
            return response()->json(['message' => __('No items selected!')], 422);
        }

        $query = Hospital::whereIn('id', $ids);

        // Vendor: only their own
        if (!$this->isAdmin()) {
            $userId           = Auth::id();
            $staffHospitalIds = HospitalStaff::where('user_id', $userId)->pluck('hospital_id');
            $query->where(function ($q) use ($userId, $staffHospitalIds) {
                $q->where('author_id', $userId)->orWhereIn('id', $staffHospitalIds);
            });
        }

        if ($action === 'delete') {
            $this->checkPermission('hospital_delete');
            $query->delete();
        } else {
            $this->checkPermission('hospital_update');
            $query->update(['status' => $action]);
        }

        return response()->json(['message' => __('Update success!')]);
    }

    // =========================================================================
    // STAFF MANAGEMENT
    // =========================================================================

    /**
     * GET /api-admin/hospital/{id}/staff
     * List all staff of a hospital.
     */
    public function staffIndex(Request $request, $id)
    {
        $this->checkPermission('hospital_view');

        $hospital = $this->resolveHospital($id);
        if ($hospital instanceof \Illuminate\Http\JsonResponse) return $hospital;

        $staff = HospitalStaff::with('user')->where('hospital_id', $id)->get();

        return response()->json([
            'data'  => $staff->map(fn($s) => [
                'id'         => $s->id,
                'user_id'    => $s->user_id,
                'role'       => $s->role,
                'role_label' => HospitalStaff::getRoles()[$s->role] ?? $s->role,
                'user'       => $s->user ? [
                    'id'    => $s->user->id,
                    'name'  => $s->user->name,
                    'email' => $s->user->email,
                ] : null,
            ]),
            'roles' => HospitalStaff::getRoles(),
        ]);
    }

    /**
     * POST /api-admin/hospital/{id}/staff
     * Add a staff member to a hospital.
     * Body: { user_id, role }
     */
    public function staffStore(Request $request, $id)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: Changes not allowed')], 403);
        }

        $this->checkPermission('hospital_update');

        $hospital = $this->resolveHospital($id);
        if ($hospital instanceof \Illuminate\Http\JsonResponse) return $hospital;

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role'    => 'nullable|in:owner,manager,staff',
        ]);

        $staff = HospitalStaff::updateOrCreate(
            ['hospital_id' => $id, 'user_id' => $request->input('user_id')],
            ['role' => $request->input('role', 'staff')]
        );

        return response()->json([
            'message' => __('Staff member added'),
            'data'    => $staff,
        ], 201);
    }

    /**
     * DELETE /api-admin/hospital/{id}/staff/{user_id}
     * Remove a staff member.
     */
    public function staffDestroy(Request $request, $id, $userId)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: Changes not allowed')], 403);
        }

        $this->checkPermission('hospital_update');

        $hospital = $this->resolveHospital($id);
        if ($hospital instanceof \Illuminate\Http\JsonResponse) return $hospital;

        HospitalStaff::where('hospital_id', $id)->where('user_id', $userId)->delete();

        return response()->json(['message' => __('Staff member removed')]);
    }

    // =========================================================================
    // MEDICAL BOOKING MANAGEMENT (hospital-scoped)
    // =========================================================================

    /**
     * GET /api-admin/hospital/{id}/bookings
     * List medical bookings for a specific hospital.
     * Admin: all. Vendor: only their hospital.
     */
    public function bookings(Request $request, $id)
    {
        $this->checkPermission('hospital_view');

        $hospital = $this->resolveHospital($id);
        if ($hospital instanceof \Illuminate\Http\JsonResponse) return $hospital;

        $query = MedicalBooking::where('hospital_id', $id)->orderByDesc('id');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }
        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }
        if ($s = $request->query('s')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'LIKE', '%' . $s . '%')
                  ->orWhere('email', 'LIKE', '%' . $s . '%')
                  ->orWhere('phone', 'LIKE', '%' . $s . '%');
            });
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $rows    = $query->paginate($perPage);

        return response()->json([
            'hospital' => ['id' => $hospital->id, 'name' => $hospital->name],
            'data'     => $rows->items(),
            'meta'     => [
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
            'filters' => [
                'statuses'         => MedicalBooking::getStatuses(),
                'priorities'       => MedicalBooking::getPriorityLevels(),
                'payment_statuses' => MedicalBooking::getPaymentStatuses(),
            ],
        ]);
    }

    /**
     * GET /api-admin/hospital/{id}/bookings/{booking_id}
     * Single booking detail within a hospital context.
     */
    public function bookingShow(Request $request, $id, $bookingId)
    {
        $this->checkPermission('hospital_view');

        $hospital = $this->resolveHospital($id);
        if ($hospital instanceof \Illuminate\Http\JsonResponse) return $hospital;

        $booking = MedicalBooking::where('hospital_id', $id)->find($bookingId);
        if (!$booking) {
            return response()->json(['message' => __('Booking not found')], 404);
        }

        return response()->json(['data' => $booking]);
    }

    /**
     * PUT /api-admin/hospital/{id}/bookings/{booking_id}
     * Update a booking's status, note, fee, priority.
     */
    public function bookingUpdate(Request $request, $id, $bookingId)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: Changes not allowed')], 403);
        }

        $this->checkPermission('hospital_update');

        $hospital = $this->resolveHospital($id);
        if ($hospital instanceof \Illuminate\Http\JsonResponse) return $hospital;

        $booking = MedicalBooking::where('hospital_id', $id)->find($bookingId);
        if (!$booking) {
            return response()->json(['message' => __('Booking not found')], 404);
        }

        $request->validate([
            'status'         => 'nullable|in:pending,confirmed,in_progress,completed,cancelled',
            'priority'       => 'nullable|in:normal,urgent,emergency',
            'admin_note'     => 'nullable|string',
            'fee'            => 'nullable|numeric|min:0',
            'currency'       => 'nullable|string|max:10',
            'payment_status' => 'nullable|in:unpaid,pending,paid,refunded,failed',
        ]);

        $booking->fill($request->only([
            'status', 'priority', 'admin_note', 'fee', 'currency', 'payment_status',
        ]));

        if ($request->input('payment_status') === 'paid' && empty($booking->paid_at)) {
            $booking->paid_at = now();
        }

        $booking->save();

        return response()->json([
            'message' => __('Booking updated'),
            'data'    => $booking,
        ]);
    }

    /**
     * POST /api-admin/hospital/{id}/bookings/{booking_id}/assign
     * Reassign a booking to this hospital (admin only).
     */
    public function bookingAssign(Request $request, $id, $bookingId)
    {
        $this->checkPermission('hospital_manage_others');

        $hospital = Hospital::find($id);
        if (!$hospital) {
            return response()->json(['message' => __('Hospital not found')], 404);
        }

        $booking = MedicalBooking::find($bookingId);
        if (!$booking) {
            return response()->json(['message' => __('Booking not found')], 404);
        }

        $booking->hospital_id = $id;
        $booking->save();

        return response()->json([
            'message' => __('Booking assigned to hospital'),
            'data'    => ['booking_id' => $booking->id, 'hospital_id' => $id, 'hospital_name' => $hospital->name],
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function formatHospital(Hospital $h, bool $withStaff = false): array
    {
        $data = [
            'id'          => $h->id,
            'name'        => $h->name,
            'description' => $h->description,
            'address'     => $h->address,
            'city'        => $h->city,
            'country'     => $h->country,
            'phone'       => $h->phone,
            'email'       => $h->email,
            'website'     => $h->website,
            'latitude'    => $h->latitude,
            'longitude'   => $h->longitude,
            'status'         => $h->status,
            'booking_amount' => $h->booking_amount,
            'currency'       => $h->currency ?? 'ETB',
            'image'          => $h->image_id ? get_file_url($h->image_id, 'full') : null,
            'author_id'      => $h->author_id,
            'author'      => $h->relationLoaded('author') && $h->author ? [
                'id'    => $h->author->id,
                'name'  => $h->author->name,
                'email' => $h->author->email,
            ] : null,
            'created_at'  => $h->created_at,
        ];

        if ($withStaff && $h->relationLoaded('staff')) {
            $data['staff'] = $h->staff->map(fn($s) => [
                'id'      => $s->id,
                'user_id' => $s->user_id,
                'role'    => $s->role,
                'user'    => $s->user ? ['id' => $s->user->id, 'name' => $s->user->name, 'email' => $s->user->email] : null,
            ]);
        }

        return $data;
    }
}
