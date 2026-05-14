<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Boat\Models\Boat;
use Modules\Car\Models\Car;
use Modules\Event\Models\Event;
use Modules\Flight\Models\Flight;
use Modules\Hotel\Models\Hotel;
use Modules\Property\Models\Property;
use Modules\Space\Models\Space;
use Modules\Tour\Models\Tour;

/**
 * Vendor “my listings” JSON APIs for SPA clients using Sanctum Bearer tokens.
 * Mirrors web vendor index queries (author_id = current user), not session cookies.
 */
class VendorListingController extends Controller
{
    private const CACHE_TTL_SECONDS = 45;
    private const SELECT_COLUMNS = [
        'id',
        'title',
        'slug',
        'status',
        'author_id',
        'location_id',
        'image_id',
        'price',
        'sale_price',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Summary counts for vendor “quick manage” tiles (Hotel, Tour, Space, …).
     * Same scope as listing APIs: author_id = current user, non–soft-deleted rows only.
     */
    public function quickManage(Request $request)
    {
        $user = Auth::user();
        if (! $user->hasPermission('dashboard_vendor_access')) {
            return response()->json([
                'status' => 0,
                'message' => __('You do not have access to the vendor dashboard.'),
            ], 403);
        }

        $items = [];
        foreach (get_bookable_services() as $type => $className) {
            if (! is_string($className) || ! class_exists($className)) {
                continue;
            }
            if (! method_exists($className, 'isEnable') || ! $className::isEnable()) {
                continue;
            }
            $permission = $type . '_view';
            $canManage = $user->hasPermission($permission);
            $title = method_exists($className, 'getModelName')
                ? call_user_func([$className, 'getModelName'])
                : ucfirst((string) $type);
            $icon = method_exists($className, 'getServiceIconFeatured')
                ? call_user_func([$className, 'getServiceIconFeatured'])
                : null;
            $count = (int) $className::query()->where('author_id', $user->id)->count();
            $items[] = [
                'type' => $type,
                'title' => $title,
                'count' => $count,
                'listings_label' => trans_choice(':count listing|:count listings', $count, ['count' => $count]),
                'can_manage' => $canManage,
                'icon' => $icon,
            ];
        }

        return $this->sendSuccess(['items' => $items]);
    }

    public function hotels(Request $request)
    {
        return $this->listing(
            $request,
            Hotel::class,
            'hotel_view',
            fn () => Hotel::isEnable(),
            ['translation']
        );
    }

    public function tours(Request $request)
    {
        return $this->listing(
            $request,
            Tour::class,
            'tour_view',
            fn () => Tour::isEnable(),
            ['translation']
        );
    }

    public function spaces(Request $request)
    {
        return $this->listing(
            $request,
            Space::class,
            'space_view',
            fn () => Space::isEnable(),
            ['translation']
        );
    }

    public function cars(Request $request)
    {
        return $this->listing(
            $request,
            Car::class,
            'car_view',
            fn () => Car::isEnable(),
            ['translation']
        );
    }

    public function boats(Request $request)
    {
        return $this->listing(
            $request,
            Boat::class,
            'boat_view',
            fn () => Boat::isEnable(),
            ['translation']
        );
    }

    public function events(Request $request)
    {
        return $this->listing(
            $request,
            Event::class,
            'event_view',
            fn () => Event::isEnable(),
            ['translation']
        );
    }

    public function flights(Request $request)
    {
        return $this->listing(
            $request,
            Flight::class,
            'flight_view',
            fn () => Flight::isEnable(),
            ['translation']
        );
    }

    public function properties(Request $request)
    {
        return $this->listing(
            $request,
            Property::class,
            'property_view',
            fn () => Property::isEnable(),
            ['translation']
        );
    }

    /**
     * GET …/vendor/hotels/recovery — same as active list with ?recovery=1 (soft-deleted rows).
     */
    public function vendorRecoveryListing(Request $request, string $resource)
    {
        $request->merge(['recovery' => true]);

        return match ($resource) {
            'hotels' => $this->hotels($request),
            'tours' => $this->tours($request),
            'spaces' => $this->spaces($request),
            'cars' => $this->cars($request),
            'boats' => $this->boats($request),
            'events' => $this->events($request),
            'flights' => $this->flights($request),
            'properties' => $this->properties($request),
            default => response()->json(['message' => __('Not found')], 404),
        };
    }

    /**
     * GET …/hotel/recovery, …/tour/recovery, etc. (singular/plural aliases).
     */
    public function recoveryListing(Request $request, string $listing)
    {
        $request->merge(['recovery' => true]);

        return match (strtolower($listing)) {
            'hotel', 'hotels' => $this->hotels($request),
            'tour', 'tours' => $this->tours($request),
            'space', 'spaces' => $this->spaces($request),
            'car', 'cars' => $this->cars($request),
            'boat', 'boats' => $this->boats($request),
            'event', 'events' => $this->events($request),
            'flight', 'flights' => $this->flights($request),
            'property', 'properties' => $this->properties($request),
            default => response()->json(['message' => __('Not found')], 404),
        };
    }

    /**
     * @param  class-string  $modelClass
     * @param  string[]  $with
     */
    protected function listing(Request $request, string $modelClass, string $permission, callable $moduleEnabled, array $with)
    {
        $user = Auth::user();
        if (! $user->hasPermission($permission)) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (! $moduleEnabled()) {
            return response()->json(['message' => __('Service is not available')], 503);
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int) $request->query('page', 1));

        $cacheKey = sprintf(
            'api:vendor-listing:%s:%s:%s',
            strtolower(class_basename($modelClass)),
            md5(json_encode([
                'u' => $user->id,
                'recovery' => (int) $request->boolean('recovery'),
                'per_page' => $perPage,
                'page' => $page,
            ])),
            app_get_locale()
        );

        $payload = Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($modelClass, $user, $request, $with, $perPage) {
            $query = $modelClass::query()
                ->select($this->resolveSelectableColumns($modelClass))
                ->where('author_id', $user->id)
                ->orderByDesc('id');

            if ($request->boolean('recovery')) {
                if (! in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
                    return ['error' => response()->json(['message' => __('Recovery listing is not available for this service')], 400)];
                }
                $query->onlyTrashed();
            }

            $paginator = $query->with($with)->paginate($perPage);

            return [
                'status' => 1,
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ];
        });

        if (isset($payload['error'])) {
            /** @var \Illuminate\Http\JsonResponse $response */
            $response = $payload['error'];
            return $response;
        }

        return response()->json($payload);
    }

    /**
     * @param class-string $modelClass
     * @return string[]
     */
    protected function resolveSelectableColumns(string $modelClass): array
    {
        $model = new $modelClass();
        $columns = [];
        foreach (self::SELECT_COLUMNS as $column) {
            if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $column)) {
                $columns[] = $column;
            }
        }

        return $columns !== [] ? $columns : ['*'];
    }
}
