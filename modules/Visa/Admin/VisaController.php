<?php

namespace Modules\Visa\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\Visa\Models\VisaService;

class VisaController extends AdminController
{
    public function index(Request $request)
    {
        $this->checkPermission('visa_view');

        $query = VisaService::query()->with(['visaType']);
        if (!empty($search = $request->query('s'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $rows = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'data' => [
                'rows' => $rows,
            ],
        ]);
    }
}
