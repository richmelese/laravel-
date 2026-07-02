<?php

namespace Modules\Emergency\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;

abstract class EmergencyBaseController extends AdminController
{
    /** Fully-qualified model class name. */
    abstract protected function modelClass(): string;

    /** Validation rules applied on store/update. */
    abstract protected function validationRules(): array;

    /** Fields passed to Model::fill(). */
    abstract protected function fillable(): array;

    /** Columns searched when ?s= is present. */
    abstract protected function searchFields(): array;

    /** Human-readable singular name used in messages. */
    abstract protected function singularName(): string;

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    protected function resolveRow($id)
    {
        $class = $this->modelClass();
        return $class::find($id);
    }

    // ── GET /api-admin/emergency-{type} ───────────────────────────────────────

    public function index(Request $request)
    {
        $this->checkPermission('emergency_view');

        $class = $this->modelClass();
        $query = $class::query()->orderBy('sort_order')->orderByDesc('id');

        if ($s = $request->query('s')) {
            $fields = $this->searchFields();
            $query->where(function ($q) use ($s, $fields) {
                foreach ($fields as $i => $field) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->$method($field, 'LIKE', '%' . $s . '%');
                }
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $rows    = $query->paginate($perPage);

        return response()->json([
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
        ]);
    }

    // ── GET /api-admin/emergency-{type}/{id} ──────────────────────────────────

    public function show(Request $request, $id)
    {
        $this->checkPermission('emergency_view');

        $row = $this->resolveRow($id);
        if (empty($row)) {
            return response()->json(['message' => __($this->singularName() . ' not found')], 404);
        }

        return response()->json(['data' => $row]);
    }

    // ── GET /api-admin/emergency-{type}/create ────────────────────────────────

    public function create(Request $request)
    {
        $this->checkPermission('emergency_create');

        $class = $this->modelClass();
        return response()->json(['data' => ['row' => new $class()]]);
    }

    // ── GET /api-admin/emergency-{type}/{id}/edit ─────────────────────────────

    public function edit(Request $request, $id)
    {
        $this->checkPermission('emergency_update');

        $row = $this->resolveRow($id);
        if (empty($row)) {
            return response()->json(['message' => __($this->singularName() . ' not found')], 404);
        }

        return response()->json(['data' => ['row' => $row]]);
    }

    // ── POST /api-admin/emergency-{type}/store/{id} ───────────────────────────

    public function store(Request $request, $id = 0)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $request->validate($this->validationRules());

        if ($id > 0) {
            $this->checkPermission('emergency_update');
            $row = $this->resolveRow($id);
            if (empty($row)) {
                return response()->json(['message' => __($this->singularName() . ' not found')], 404);
            }
        } else {
            $this->checkPermission('emergency_create');
            $class = $this->modelClass();
            $row   = new $class();
        }

        $row->fill($request->only($this->fillable()));
        $row->save();

        return response()->json([
            'message' => $id > 0 ? __($this->singularName() . ' updated') : __($this->singularName() . ' created'),
            'data'    => $row,
        ], $id > 0 ? 200 : 201);
    }

    // ── DELETE /api-admin/emergency-{type}/{id} ───────────────────────────────

    public function destroy(Request $request, $id)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $this->checkPermission('emergency_delete');

        $row = $this->resolveRow($id);
        if (empty($row)) {
            return response()->json(['message' => __($this->singularName() . ' not found')], 404);
        }

        $row->delete();

        return response()->json(['message' => __($this->singularName() . ' deleted')]);
    }

    // ── POST /api-admin/emergency-{type}/bulk-action ──────────────────────────

    public function bulkEdit(Request $request)
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

        $class = $this->modelClass();

        switch ($action) {
            case 'delete':
                $this->checkPermission('emergency_delete');
                $class::whereIn('id', $ids)->delete();
                break;
            default:
                $this->checkPermission('emergency_update');
                $class::whereIn('id', $ids)->update(['status' => $action]);
                break;
        }

        return response()->json(['message' => __('Update success!')]);
    }
}
