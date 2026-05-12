<?php

namespace Modules\Boat\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Boat\Models\BusRoute;

class BusRouteController extends Controller
{
    public function index(Request $request)
    {
        $query = BusRoute::query()->orderByDesc('id');
        if ($s = $request->query('s')) {
            $query->where(function ($q) use ($s) {
                $q->where('from_location', 'like', '%' . $s . '%')
                    ->orWhere('to_location', 'like', '%' . $s . '%');
            });
        }
        return response()->json(['data' => $query->paginate((int) $request->query('per_page', 20))]);
    }

    public function store(Request $request, $id = 0)
    {
        $data = $request->validate([
            'from_location' => 'required|string|max:255',
            'to_location' => 'required|string|max:255',
            'distance_km' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:20',
        ]);
        $row = $id > 0 ? BusRoute::query()->find($id) : new BusRoute();
        if ($id > 0 && !$row) {
            return response()->json(['message' => 'Route not found'], 404);
        }
        $row->fill($data);
        $row->status = $row->status ?: 'active';
        $row->save();

        return response()->json(['message' => $id > 0 ? 'Route updated' : 'Route created', 'data' => $row], $id > 0 ? 200 : 201);
    }
}
