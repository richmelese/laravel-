<?php

namespace Modules\Boat\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Boat\Models\Bus;

class BusController extends Controller
{
    protected function bumpBusApiCacheVersion(): void
    {
        Cache::add('api_cache:bus:version', 1);
        Cache::increment('api_cache:bus:version');
    }

    public function index(Request $request)
    {
        $query = Bus::query()->orderByDesc('id');

        if ($search = $request->query('s')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('bus_number', 'like', "%{$search}%")
                    ->orWhere('departure_city', 'like', "%{$search}%")
                    ->orWhere('arrival_city', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->paginate((int) $request->query('per_page', 20)),
        ]);
    }

    public function recovery(Request $request)
    {
        $query = Bus::onlyTrashed()->orderByDesc('id');
        if ($search = $request->query('s')) {
            $query->where('title', 'like', "%{$search}%");
        }

        return response()->json([
            'data' => $query->paginate((int) $request->query('per_page', 20)),
        ]);
    }

    public function create()
    {
        return response()->json([
            'data' => [
                'title' => null,
                'description' => null,
                'bus_number' => null,
                'bus_type' => null,
                'seat_capacity' => null,
                'driver_name' => null,
                'driver_phone' => null,
                'departure_city' => null,
                'arrival_city' => null,
                'departure_location' => null,
                'arrival_location' => null,
                'departure_time' => null,
                'arrival_time' => null,
                'price' => null,
                'image_id' => null,
                'gallery' => [],
                'status' => 'active',
                'is_active' => 1,
            ],
        ]);
    }

    public function edit($id)
    {
        $row = Bus::find($id);
        if (!$row) {
            return response()->json(['message' => 'Bus not found'], 404);
        }
        return response()->json(['data' => $row]);
    }

    public function store(Request $request, $id = 0)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'bus_number' => 'nullable|string|max:255',
            'bus_type' => 'nullable|string|max:100',
            'seat_capacity' => 'nullable|integer|min:1',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:50',
            'departure_city' => 'nullable|string|max:255',
            'arrival_city' => 'nullable|string|max:255',
            'departure_location' => 'nullable|string|max:255',
            'arrival_location' => 'nullable|string|max:255',
            'departure_time' => 'nullable|string',
            'arrival_time' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'image_id' => 'nullable|integer|min:1',
            'gallery' => 'nullable|array',
            'gallery.*' => 'integer|min:1',
            'status' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        foreach (['departure_time', 'arrival_time'] as $field) {
            $v = $request->input($field);
            if ($v === null || $v === '') {
                continue;
            }
            if (!$this->isValidBusScheduleTime($v)) {
                return response()->json([
                    'message' => 'The ' . str_replace('_', ' ', $field) . ' must be a time (H:i or H:i:s) or a full date/datetime.',
                    'errors' => [
                        $field => ['Invalid time format. Use 14:30, 14:30:00, or 2025-12-25 14:30:00.'],
                    ],
                ], 422);
            }
        }

        $validated['title'] = $validated['title'] ?? ($validated['name'] ?? null);
        if (!$validated['title']) {
            return response()->json(['message' => 'The name field is required.'], 422);
        }

        $row = $id > 0 ? Bus::find($id) : new Bus();
        if ($id > 0 && !$row) {
            return response()->json(['message' => 'Bus not found'], 404);
        }

        $row->fill(array_diff_key($validated, array_flip([
            'name',
            'departure_time',
            'arrival_time',
            'gallery',
            'image_id',
        ])));

        if (array_key_exists('image_id', $validated)) {
            $v = $validated['image_id'];
            $row->image_id = $v === null || (int) $v === 0 ? null : (int) $v;
        }

        if ($request->has('gallery')) {
            $row->gallery = $this->normalizeGalleryIds($request->input('gallery'));
        }

        $row->departure_time = $this->parseBusScheduleToDatetime($request->input('departure_time'));
        $row->arrival_time = $this->parseBusScheduleToDatetime($request->input('arrival_time'));
        if (array_key_exists('is_active', $validated)) {
            $row->is_active = $validated['is_active'] ? 1 : 0;
        }

        if ($row->exists) {
            $row->update_user = Auth::id();
        } else {
            $row->create_user = Auth::id();
        }

        $row->save();
        $this->bumpBusApiCacheVersion();

        return response()->json([
            'message' => $id > 0 ? 'Bus updated' : 'Bus created',
            'data' => $row,
        ], $id > 0 ? 200 : 201);
    }

    /**
     * True if value is "14:30", "14:30:00", or any string Carbon can parse (full datetime).
     */
    protected function isValidBusScheduleTime(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $value = trim($value);
        if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value)) {
            return true;
        }
        try {
            Carbon::parse($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Store as datetime: time-only values use a fixed date so the column stays a "time of day" for routes.
     */
    protected function parseBusScheduleToDatetime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value)) {
            if (substr_count($value, ':') === 1) {
                $value .= ':00';
            }

            return Carbon::createFromFormat('H:i:s', $value, config('app.timezone'))->setDate(2000, 1, 1);
        }

        return Carbon::parse($value, config('app.timezone'));
    }

    /**
     * @return int[]|null
     */
    protected function normalizeGalleryIds(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                return null;
            }
        }
        if (!is_array($value)) {
            return null;
        }
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $value),
            static fn (int $n) => $n > 0
        )));

        return $ids !== [] ? $ids : [];
    }

    public function bulkEdit(Request $request)
    {
        $ids = $request->input('ids', []);
        $action = $request->input('action');

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'No items selected!'], 422);
        }

        if (!$action) {
            return response()->json(['message' => 'Please select an action!'], 422);
        }

        $query = Bus::withTrashed()->whereIn('id', $ids);

        switch ($action) {
            case 'delete':
                Bus::whereIn('id', $ids)->delete();
                $this->bumpBusApiCacheVersion();
                return response()->json(['message' => 'Deleted success!']);
            case 'permanently_delete':
                $query->forceDelete();
                $this->bumpBusApiCacheVersion();
                return response()->json(['message' => 'Permanently delete success!']);
            case 'recovery':
                $query->restore();
                $this->bumpBusApiCacheVersion();
                return response()->json(['message' => 'Recovery success!']);
            default:
                Bus::whereIn('id', $ids)->update(['status' => $action, 'update_user' => Auth::id()]);
                $this->bumpBusApiCacheVersion();
                return response()->json(['message' => 'Update success!']);
        }
    }
}
