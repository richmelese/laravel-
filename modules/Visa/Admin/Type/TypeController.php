<?php

namespace Modules\Visa\Admin\Type;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\Visa\Models\VisaType;

class TypeController extends AdminController
{
    public function index(Request $request)
    {
        $this->checkPermission('visa_view');

        $query = app(VisaType::class)->query();
        if (!empty($search = $request->query('s'))) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        $query->orderBy('name', 'asc');

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $rows = $query->paginate($perPage);

        return response()->json([
            'data' => [
                'rows' => $rows,
            ],
        ]);
    }

    public function getForSelect2(Request $request)
    {
        $this->checkPermission('visa_view');

        $preSelected = $request->query('pre_selected');
        $selected = $request->query('selected');

        if ($preSelected && $selected) {
            $items = app(VisaType::class)->whereIn('id', (array) $selected)->get();
            $results = $items->map(function ($item) {
                return ['id' => $item->id, 'text' => $item->translate()->name];
            });
            return response()->json(['results' => $results]);
        }

        $q = $request->query('q');
        $query = app(VisaType::class)->where('status', 'publish');
        if ($q) {
            $query->where('name', 'like', '%' . $q . '%');
        }
        $items = $query->orderBy('name', 'asc')->limit(50)->get();
        $results = $items->map(function ($item) {
            return ['id' => $item->id, 'text' => $item->translate()->name];
        });

        return response()->json(['results' => $results]);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('visa_view');

        $row = app(VisaType::class)->find($id);
        if (!$row) {
            return response()->json(['message' => __('Visa Type not found')], 404);
        }

        return response()->json([
            'data' => [
                'row' => $row,
                'translation' => $row->translate($request->query('lang')),
            ],
        ]);
    }

    public function store(Request $request, $id = 0)
    {
        $this->checkPermission('visa_manage_others');

        $request->validate([
            'name' => 'required',
            'status' => 'required',
        ]);

        if ($id > 0) {
            $row = app(VisaType::class)->find($id);
            if (!$row) {
                return response()->json(['message' => __('Visa Type not found')], 404);
            }
        } else {
            $row = app(VisaType::class);
        }

        $row->fillByAttr(['name', 'status'], $request->input());
        $row->saveOriginOrTranslation($request->input('lang'));

        return response()->json([
            'message' => $id > 0 ? __('Visa Type updated') : __('Visa Type saved'),
            'data' => $row,
        ], $id > 0 ? 200 : 201);
    }

    public function destroy($id)
    {
        $this->checkPermission('visa_manage_others');

        $row = app(VisaType::class)->find($id);
        if (!$row) {
            return response()->json(['message' => __('Visa Type not found')], 404);
        }
        $row->delete();

        return response()->json(['message' => __('Visa Type deleted')]);
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('visa_manage_others');

        $ids = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) || !is_array($ids)) {
            return response()->json(['message' => __('Please select at least 1 item!')], 422);
        }
        if (empty($action)) {
            return response()->json(['message' => __('Please select an Action!')], 422);
        }

        $visaType = app(VisaType::class);

        if ($action == 'publish') {
            $visaType->whereIn('id', $ids)->update(['status' => 'publish']);
        } elseif ($action == 'draft') {
            $visaType->whereIn('id', $ids)->update(['status' => 'draft']);
        } elseif ($action == 'delete') {
            $visaType->whereIn('id', $ids)->delete();
        }

        return response()->json(['message' => __('Visa Type saved')]);
    }
}
