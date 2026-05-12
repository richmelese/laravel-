<?php

namespace Modules\Boat\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Boat\Models\Bus;
use Modules\Media\Helpers\FileHelper;

class BusController extends Controller
{
    private const CACHE_SECONDS = 60;
    private const MAX_PER_PAGE = 100;
    private const PUBLIC_COLUMNS = [
        'id',
        'title',
        'description',
        'bus_number',
        'bus_type',
        'seat_capacity',
        'driver_name',
        'driver_phone',
        'departure_city',
        'arrival_city',
        'departure_location',
        'arrival_location',
        'departure_time',
        'arrival_time',
        'price',
        'image_id',
        'gallery',
        'status',
        'is_active',
        'create_user',
        'update_user',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function index(Request $request)
    {
        $version = (int) Cache::get('api_cache:bus:version', 1);
        $perPage = max(1, min((int) $request->query('per_page', 20), self::MAX_PER_PAGE));
        $payload = [
            'page' => (int) $request->query('page', 1),
            'per_page' => $perPage,
            'departure_city' => (string) $request->query('departure_city', ''),
            'arrival_city' => (string) $request->query('arrival_city', ''),
            'bus_type' => (string) $request->query('bus_type', ''),
            'v' => $version,
        ];
        $cacheKey = 'api:bus:index:' . md5(json_encode($payload));

        $page = Cache::remember($cacheKey, now()->addSeconds(self::CACHE_SECONDS), function () use ($request, $perPage) {
            $query = Bus::query()
                ->select(self::PUBLIC_COLUMNS)
                ->where('is_active', 1)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'active');
                })
                ->orderByDesc('id');

            if ($departureCity = $request->query('departure_city')) {
                $query->where('departure_city', 'like', '%' . $departureCity . '%');
            }
            if ($arrivalCity = $request->query('arrival_city')) {
                $query->where('arrival_city', 'like', '%' . $arrivalCity . '%');
            }
            if ($busType = $request->query('bus_type')) {
                $query->where('bus_type', $busType);
            }

            /** @var LengthAwarePaginator $result */
            $result = $query->paginate($perPage);
            $result->setCollection($this->formatCollectionForPublic($result->getCollection()));

            return $result;
        });

        return response()->json([
            'data' => $page,
        ]);
    }

    public function show($id)
    {
        $version = (int) Cache::get('api_cache:bus:version', 1);
        $cacheKey = 'api:bus:show:' . $version . ':' . (int) $id;
        $row = Cache::remember($cacheKey, now()->addSeconds(self::CACHE_SECONDS), function () use ($id) {
            $bus = Bus::query()
                ->select(self::PUBLIC_COLUMNS)
                ->where('is_active', 1)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'active');
                })
                ->find($id);

            if (!$bus) {
                return null;
            }

            return $this->formatForPublic($bus);
        });

        if (!$row) {
            return response()->json(['message' => 'Bus not found'], 404);
        }

        return response()->json(['data' => $row]);
    }

    /**
     * @param Collection<int, Bus> $buses
     * @return Collection<int, array<string, mixed>>
     */
    protected function formatCollectionForPublic(Collection $buses): Collection
    {
        $urlCache = [];

        return $buses->map(function (Bus $bus) use (&$urlCache) {
            return $this->formatForPublic($bus, $urlCache);
        });
    }

    /**
     * API returns media ids; adds resolved file URLs for convenience.
     *
     * @return array<string, mixed>
     */
    protected function formatForPublic(Bus $bus, array &$urlCache = []): array
    {
        $row = $bus->toArray();
        $row['image_id'] = $bus->image_id ? (int) $bus->image_id : null;
        $gallery = is_array($bus->gallery) ? $bus->gallery : [];
        $row['gallery'] = array_values(array_map('intval', $gallery));
        $row['image'] = $bus->image_id ? $this->resolveMediaUrl((int) $bus->image_id, $urlCache) : null;
        $row['gallery_images'] = array_values(array_filter(array_map(
            function (int $id) use (&$urlCache) {
                return $id ? $this->resolveMediaUrl($id, $urlCache) : null;
            },
            $row['gallery']
        )));

        return $row;
    }

    protected function resolveMediaUrl(int $id, array &$urlCache): ?string
    {
        if ($id <= 0) {
            return null;
        }
        if (array_key_exists($id, $urlCache)) {
            return $urlCache[$id];
        }
        $url = FileHelper::url($id);
        $urlCache[$id] = $url ?: null;

        return $urlCache[$id];
    }
}
