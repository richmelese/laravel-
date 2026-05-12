<?php
namespace Modules\Api\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Booking\Models\Service;
use Modules\Flight\Controllers\FlightController;
use Illuminate\Support\Arr;

class SearchController extends Controller
{
    private const DEFAULT_LIMIT = 9;
    private const MAX_LIMIT = 100;
    private const SEARCH_CACHE_TTL = 60;
    private const DETAIL_CACHE_TTL = 120;
    private const META_CACHE_TTL = 300;

    protected function resolveLimit(Request $request, string $settingKey = ''): int
    {
        $configured = $settingKey ? (int) setting_item($settingKey, self::DEFAULT_LIMIT) : self::DEFAULT_LIMIT;
        $raw = (int) $request->query('limit', $configured > 0 ? $configured : self::DEFAULT_LIMIT);

        return max(1, min(self::MAX_LIMIT, $raw));
    }

    /**
     * @param array<string,mixed> $parts
     */
    protected function buildCacheKey(string $prefix, array $parts): string
    {
        $parts['v'] = (int) Cache::get('api_cache:search:version', 1);
        ksort($parts);
        return $prefix . ':' . md5(json_encode($parts));
    }

    public function search($type = ''){
        $request = request();
        $type = $type ? $type : $request->get('type');
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }

        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }

        $limit = $this->resolveLimit($request, $type . '_page_limit_item');
        $params = $request->query();
        $params['type'] = $type;
        $params['limit'] = $limit;
        $cacheKey = $this->buildCacheKey('api:search', $params);

        $payload = Cache::remember($cacheKey, now()->addSeconds(self::SEARCH_CACHE_TTL), function () use ($class, $request, $limit) {
            $query = new $class();
            $rows = $query->search($request->input())->paginate($limit);

            return [
                'total'=>$rows->total(),
                'total_pages'=>$rows->lastPage(),
                'data'=>$rows->getCollection()->map(function($row){
                    return $row->dataForApi();
                })->values(),
            ];
        });

        return $this->sendSuccess($payload);
    }


    public function searchServices(){
        $request = request();
        $limit = $this->resolveLimit($request);
        $params = $request->query();
        $params['limit'] = $limit;
        $cacheKey = $this->buildCacheKey('api:search:services', $params);

        $payload = Cache::remember($cacheKey, now()->addSeconds(self::SEARCH_CACHE_TTL), function () use ($request, $limit) {
            $query = new Service();
            $rows = $query->search($request->input())->paginate($limit);

            return [
                'total'=>$rows->total(),
                'total_pages'=>$rows->lastPage(),
                'data'=>$rows->getCollection()->map(function($row){
                    return $row->dataForApi();
                })->values(),
            ];
        });

        return $this->sendSuccess($payload);
    }

    public function getFilters($type = ''){
        $request = request();
        $type = $type ? $type : $request->get('type');
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }
        $params = $request->query();
        $params['type'] = $type;
        $cacheKey = $this->buildCacheKey('api:search:filters', $params);
        $data = Cache::remember($cacheKey, now()->addSeconds(self::META_CACHE_TTL), function () use ($class, $request) {
            return call_user_func([$class,'getFiltersSearch'],$request);
        });
        return $this->sendSuccess(
            [
                'data'=>$data
            ]
        );
    }

    public function getFormSearch($type = ''){
        $request = request();
        $type = $type ? $type : $request->get('type');
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }
        $params = $request->query();
        $params['type'] = $type;
        $cacheKey = $this->buildCacheKey('api:search:form', $params);
        $data = Cache::remember($cacheKey, now()->addSeconds(self::META_CACHE_TTL), function () use ($class, $request) {
            return call_user_func([$class,'getFormSearch'],$request);
        });
        return $this->sendSuccess(
            [
                'data'=>$data
            ]
        );
    }

    public function detail($type = '',$id = '')
    {
        if(empty($type)){
            return $this->sendError(__("Resource is not available"));
        }
        if(empty($id)){
            return $this->sendError(__("Resource ID is not available"));
        }

        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }

        $row = $class::find($id);
        if(empty($row))
        {
            return $this->sendError(__("Resource not found"));
        }

        if($type=='flight'){
            return app()->make(FlightController::class)->getData(\request(),$id);
        }
        $cacheKey = $this->buildCacheKey('api:search:detail', ['type' => $type, 'id' => (string) $id]);
        $payload = Cache::remember($cacheKey, now()->addSeconds(self::DETAIL_CACHE_TTL), function () use ($row) {
            return $row->dataForApi(true);
        });
        return $this->sendSuccess([
            'data'=>$payload
        ]);

    }

    public function checkAvailability(Request $request , $type = '',$id = ''){
        if(empty($type)){
            return $this->sendError(__("Resource is not available"));
        }
        if(empty($id)){
            return $this->sendError(__("Resource ID is not available"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }

        $service = $class::find($id);
        if (empty($service)) {
            return $this->sendError(__("Service not found"));
        }
        $classAvailability = $class::getClassAvailability();
        $classAvailability = app()->make($classAvailability);
        $request->merge(['id' => $id]);
        $data = [];
        if($type == "hotel"){
            $request->merge(['hotel_id' => $id]);
            $resJson =  $classAvailability->checkAvailability($request);
            $data = Arr::get($resJson->getData(true), 'rooms', []);
        } else {
            $resJson = $classAvailability->loadDates($request);
            $data = $resJson->getData(true);
        }
        return $this->sendSuccess(['data' => $data, 'booking_data' => $service->getBookingData()]);
    }

    public function checkBoatAvailability(Request $request ,$id = ''){
        if(empty($id)){
            return $this->sendError(__("Boat ID is not available"));
        }
        $class = get_bookable_service_by_id('boat');
        $classAvailability = $class::getClassAvailability();
        $classAvailability = app()->make($classAvailability);
        $request->merge(['id' => $id]);
        return $classAvailability->availabilityBooking($request);
    }
}
