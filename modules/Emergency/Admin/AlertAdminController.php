<?php

namespace Modules\Emergency\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\Emergency\Models\AlertRequest;

class AlertAdminController extends AdminController
{
    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    /**
     * GET /api-admin/alert
     * List all alert requests with filters.
     */
    public function index(Request $request)
    {
        $this->checkPermission('emergency_view');

        $query = AlertRequest::with('user')->orderByDesc('id');

        // Search by name, email, or phone
        if ($s = $request->query('s')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'LIKE', '%' . $s . '%')
                  ->orWhere('email', 'LIKE', '%' . $s . '%')
                  ->orWhere('phone', 'LIKE', '%' . $s . '%')
                  ->orWhere('booking_code', 'LIKE', '%' . $s . '%');
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->query('alert_type')) {
            $query->where('alert_type', $type);
        }
        if ($request->query('unread')) {
            $query->where('status', 'new');
        }

        if ($request->boolean('include_inactive')) {
            if ($request->query('is_active') !== null) {
                $query->where('is_active', $request->boolean('is_active'));
            }
        } else {
            $query->where('is_active', true);
        }

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $page    = max((int) $request->query('page', 1), 1);
        $rows    = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();

        return response()->json([
            'data' => collect($rows->items())->map(fn($r) => $this->formatRow($r)),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'from'         => $rows->firstItem(),
                'to'           => $rows->lastItem(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
            'links' => [
                'first' => $rows->url(1),
                'last'  => $rows->url($rows->lastPage()),
                'prev'  => $rows->previousPageUrl(),
                'next'  => $rows->nextPageUrl(),
            ],
            'filters' => [
                'statuses'    => AlertRequest::getStatuses(),
                'alert_types' => AlertRequest::getAlertTypes(),
            ],
            'new_count' => AlertRequest::where('status', 'new')->where('is_active', true)->count(),
        ]);
    }

    /**
     * GET /api-admin/alert/{id}
     * View a single alert with full user + booking info.
     */
    public function show(Request $request, $id)
    {
        $this->checkPermission('emergency_view');

        $alert = AlertRequest::with('user')->find($id);
        if (!$alert) {
            return response()->json(['message' => __('Alert not found')], 404);
        }

        // Auto-acknowledge when admin views it
        if ($alert->status === 'new') {
            $alert->status = 'acknowledged';
            $alert->responded_at = now();
            $alert->save();
        }

        return response()->json(['data' => $this->formatRow($alert, true)]);
    }

    /**
     * PUT/PATCH /api-admin/alert/{id}
     * Update alert status and admin note.
     */
    public function update(Request $request, $id)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $this->checkPermission('emergency_update');

        $alert = AlertRequest::find($id);
        if (!$alert) {
            return response()->json(['message' => __('Alert not found')], 404);
        }

        $request->validate([
            'status'     => 'nullable|in:new,acknowledged,in_progress,resolved,dismissed',
            'is_active'  => 'nullable|boolean',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        if ($request->filled('status')) {
            $alert->status = $request->input('status');
            if (in_array($alert->status, ['acknowledged', 'in_progress', 'resolved']) && !$alert->responded_at) {
                $alert->responded_at = now();
            }
        }
        if ($request->has('is_active')) {
            $alert->is_active = $request->boolean('is_active');
        }
        if ($request->filled('admin_note')) {
            $alert->admin_note = $request->input('admin_note');
        }

        $alert->save();

        return response()->json([
            'message' => __('Alert updated'),
            'data'    => $this->formatRow($alert),
        ]);
    }

    /**
     * DELETE /api-admin/alert/{id}
     * Deactivates the alert instead of removing it from the database.
     */
    public function destroy(Request $request, $id)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $this->checkPermission('emergency_update');

        $alert = AlertRequest::find($id);
        if (!$alert) {
            return response()->json(['message' => __('Alert not found')], 404);
        }

        if (!$alert->is_active) {
            return response()->json([
                'message' => __('Alert is already deactivated'),
                'data'    => $this->formatRow($alert),
            ]);
        }

        $alert->is_active = false;
        $alert->save();

        return response()->json([
            'message' => __('Alert deactivated'),
            'data'    => $this->formatRow($alert),
        ]);
    }

    /**
     * POST /api-admin/alert/bulk-action
     * Bulk status update or deactivate.
     * Body: { ids: [1,2,3], action: "resolved" | "dismissed" | "delete" | "deactivate" }
     */
    public function bulkAction(Request $request)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $ids    = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) || !is_array($ids)) {
            return response()->json(['message' => __('No items selected!')], 422);
        }
        if (empty($action)) {
            return response()->json(['message' => __('Please select an action!')], 422);
        }

        if (in_array($action, ['delete', 'deactivate'], true)) {
            $this->checkPermission('emergency_update');
            AlertRequest::whereIn('id', $ids)->update(['is_active' => false]);
        } else {
            $this->checkPermission('emergency_update');
            $update = ['status' => $action];
            if (in_array($action, ['acknowledged', 'in_progress', 'resolved'])) {
                $update['responded_at'] = now();
            }
            AlertRequest::whereIn('id', $ids)->update($update);
        }

        return response()->json(['message' => __('Update success!')]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function formatRow(AlertRequest $alert, bool $full = false): array
    {
        $data = [
            'id'              => $alert->id,
            'alert_type'      => $alert->alert_type,
            'alert_label'     => AlertRequest::getAlertTypes()[$alert->alert_type] ?? $alert->alert_type,
            'status'          => $alert->status,
            'status_label'    => AlertRequest::getStatuses()[$alert->status] ?? $alert->status,
            'is_active'       => (bool) ($alert->is_active ?? true),
            'name'            => $alert->name,
            'email'           => $alert->email,
            'phone'           => $alert->phone,
            'booking_code'    => $alert->booking_code,
            'description'     => $alert->message,
            'message'         => $alert->message,
            'booked_services' => $alert->booked_services ?? [],
            'location'        => $alert->location,
            'latitude'        => $alert->latitude,
            'longitude'       => $alert->longitude,
            'admin_note'      => $alert->admin_note,
            'responded_at'    => $alert->responded_at,
            'created_at'      => $alert->created_at,
        ];

        if ($full) {
            $data['user'] = $alert->user ? [
                'id'    => $alert->user->id,
                'name'  => $alert->user->name,
                'email' => $alert->user->email,
                'phone' => $alert->user->phone ?? null,
            ] : null;
        }

        return $data;
    }
}
