<?php

namespace Modules\Boat\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Boat\Models\Bus;
use Modules\Boat\Models\BusRoute;
use Modules\Boat\Models\BusSchedule;

class BusScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = BusSchedule::query()->with(['bus', 'route'])->orderByDesc('id');
        if ($busId = $request->query('bus_id')) {
            $query->where('bus_id', $busId);
        }
        if ($routeId = $request->query('route_id')) {
            $query->where('route_id', $routeId);
        }
        if ($date = $request->query('date')) {
            $query->whereDate('departure_time', $date);
        }
        return response()->json(['data' => $query->paginate((int) $request->query('per_page', 20))]);
    }

    public function store(Request $request, $id = 0)
    {
        $data = $request->validate([
            'bus_id' => 'required|integer|min:1',
            'route_id' => 'required|integer|min:1',
            'departure_time' => 'required|date',
            'arrival_time' => 'nullable|date',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:20',
        ]);

        if (!Bus::query()->find($data['bus_id'])) {
            return response()->json(['message' => 'Bus not found'], 422);
        }
        if (!BusRoute::query()->find($data['route_id'])) {
            return response()->json(['message' => 'Route not found'], 422);
        }

        $row = $id > 0 ? BusSchedule::query()->find($id) : new BusSchedule();
        if ($id > 0 && !$row) {
            return response()->json(['message' => 'Schedule not found'], 404);
        }
        $row->fill($data);
        $row->status = $row->status ?: 'scheduled';
        $row->save();

        return response()->json(['message' => $id > 0 ? 'Schedule updated' : 'Schedule created', 'data' => $row], $id > 0 ? 200 : 201);
    }
}
