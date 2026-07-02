<?php
namespace Modules\Hotel\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Core\Events\CreatedServicesEvent;
use Modules\Core\Events\UpdatedServiceEvent;
use Modules\Core\Models\Attributes;
use Modules\Hotel\Hook;
use Modules\Location\Models\Location;
use Modules\Hotel\Models\Hotel;
use Modules\Hotel\Models\HotelTerm;
use Modules\Hotel\Models\HotelTranslation;
use Modules\Location\Models\LocationCategory;

class HotelController extends AdminController
{
    protected $hotelClass;
    protected $hotelTranslationClass;
    protected $hotelTermClass;
    protected $attributesClass;
    protected $locationClass;
    /**
     * @var string
     */
    private $locationCategoryClass;

    public function __construct(Hotel $hotel, HotelTranslation $hotelTrans, Location $locationClass, Attributes $attributesClass)
    {
        $this->setActiveMenu(route('hotel.admin.index'));
        $this->hotelClass = $hotel;
        $this->hotelTranslationClass = $hotelTrans;
        $this->hotelTermClass = HotelTerm::class;
        $this->attributesClass = $attributesClass;
        $this->locationClass = $locationClass;
        $this->locationCategoryClass = LocationCategory::class;
    }
    public function callAction($method, $parameters)
    {
        if (!Hotel::isEnable()) {
            $request = request();
            if ($request && ($request->wantsJson() || $request->is('api-admin/*'))) {
                return response()->json(['message' => __('Hotel module is disabled')], 503);
            }
            return redirect('/');
        }
        return parent::callAction($method, $parameters);
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    public function index(Request $request)
    {
        $this->checkPermission('hotel_view');
        $query = $this->hotelClass::query() ;
        $query->orderBy('id', 'desc');
        if (!empty($hotel_name = $request->input('s'))) {
            $query->where('title', 'LIKE', '%' . $hotel_name . '%');
            $query->orderBy('title', 'asc');
        }
        if (!empty($is_featured = $request->input('is_featured'))) {
            $query->where('is_featured', 1);
        }
        if (!empty($location_id = $request->query('location_id'))) {
            $query->where('location_id', $location_id);
        }
        if ($this->hasPermission('hotel_manage_others')) {
            if (!empty($author = $request->input('vendor_id'))) {
                $query->where('author_id', $author);
            }
        } else {
            $query->where('author_id', Auth::id());
        }
        $perPage = (int) $request->query('per_page', 20);
        if ($perPage <= 0) {
            $perPage = 20;
        }
        $data = [
            'rows'               => $query->with(['author'])->paginate($perPage),
            'hotel_manage_others' => $this->hasPermission('hotel_manage_others'),
            'breadcrumbs'        => [
                [
                    'name' => __('Hotels'),
                    'url'  => route('hotel.admin.index')
                ],
                [
                    'name'  => __('All'),
                    'class' => 'active'
                ],
            ],
            'page_title'=>__("Hotel Management")
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => $data['rows']->items(),
                'meta' => [
                    'current_page' => $data['rows']->currentPage(),
                    'per_page'     => $data['rows']->perPage(),
                    'total'        => $data['rows']->total(),
                    'last_page'    => $data['rows']->lastPage(),
                ],
                'hotel_manage_others' => $data['hotel_manage_others'],
            ]);
        }
        return view('Hotel::admin.index', $data);
    }

    public function create(Request $request)
    {
        $this->checkPermission('hotel_create');
        $row = new $this->hotelClass();
        $row->fill([
            'status' => 'publish'
        ]);
        $data = [
            'row'            => $row,
            'attributes'     => $this->attributesClass::where('service', 'hotel')->get(),
            'hotel_location' => $this->locationClass::where('status', 'publish')->get()->toTree(),
            'location_category' => $this->locationCategoryClass::where('status', 'publish')->get(),
            'translation'    => new $this->hotelTranslationClass(),
            'breadcrumbs'    => [
                [
                    'name' => __('Hotels'),
                    'url'  => route('hotel.admin.index')
                ],
                [
                    'name'  => __('Add Hotel'),
                    'class' => 'active'
                ],
            ],
            'page_title'     => __("Add new Hotel")
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row' => $data['row'],
                    'attributes' => $data['attributes'],
                    'hotel_location' => $data['hotel_location'],
                    'location_category' => $data['location_category'],
                    'translation' => $data['translation'],
                ],
            ]);
        }
        return view('Hotel::admin.detail', $data);
    }

    public function recovery(Request $request)
    {
        $this->checkPermission('hotel_view');
        $query = $this->hotelClass::onlyTrashed() ;
        $query->orderBy('id', 'desc');
        if (!empty($hotel_name = $request->input('s'))) {
            $query->where('title', 'LIKE', '%' . $hotel_name . '%');
            $query->orderBy('title', 'asc');
        }

        if ($this->hasPermission('hotel_manage_others')) {
            if (!empty($author = $request->input('vendor_id'))) {
                $query->where('author_id', $author);
            }
        } else {
            $query->where('author_id', Auth::id());
        }
        $data = [
            'rows'               => $query->with(['author'])->paginate(20),
            'hotel_manage_others' => $this->hasPermission('hotel_manage_others'),
            'recovery'           => 1,
            'breadcrumbs'        => [
                [
                    'name' => __('Hotels'),
                    'url'  => route('hotel.admin.index')
                ],
                [
                    'name'  => __('Recovery'),
                    'class' => 'active'
                ],
            ],
            'page_title'=>__("Recovery Hotel Management")
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => $data['rows']->items(),
                'meta' => [
                    'current_page' => $data['rows']->currentPage(),
                    'per_page'     => $data['rows']->perPage(),
                    'total'        => $data['rows']->total(),
                    'last_page'    => $data['rows']->lastPage(),
                ],
                'recovery' => true,
                'hotel_manage_others' => $data['hotel_manage_others'],
            ]);
        }
        return view('Hotel::admin.index', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('hotel_update');
        $row = $this->hotelClass::find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Hotel not found'], 404);
            }
            return redirect(route('hotel.admin.index'));
        }
        $translation = $row->translate($request->query('lang',get_main_lang()));
        if (!$this->hasPermission('hotel_manage_others')) {
            if ($row->author_id != Auth::id()) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
                return redirect(route('hotel.admin.index'));
            }
        }
        $data = [
            'row'            => $row,
            'translation'    => $translation,
            "selected_terms" => $row->terms->pluck('term_id'),
            'attributes'     => $this->attributesClass::where('service', 'hotel')->get(),
            'hotel_location'  => $this->locationClass::where('status', 'publish')->get()->toTree(),
            'location_category' => $this->locationCategoryClass::where('status', 'publish')->get(),
            'enable_multi_lang'=>true,
            'breadcrumbs'    => [
                [
                    'name' => __('Hotels'),
                    'url'  => route('hotel.admin.index')
                ],
                [
                    'name'  => __('Edit Hotel'),
                    'class' => 'active'
                ],
            ],
            'page_title'=>__("Edit: :name",['name'=>$row->title])
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row' => $data['row'],
                    'translation' => $data['translation'],
                    'selected_terms' => $data['selected_terms'],
                    'attributes' => $data['attributes'],
                    'hotel_location' => $data['hotel_location'],
                    'location_category' => $data['location_category'],
                    'enable_multi_lang' => $data['enable_multi_lang'],
                ],
            ]);
        }
        return view('Hotel::admin.detail', $data);
    }

    public function store( Request $request, $id ){

        if(is_demo_mode()){
            if($this->isApiRequest($request)) return response()->json(['message' => __('DEMO MODE: can not add data')], 403);
            return redirect()->back()->with('danger',__("DEMO MODE: can not add data"));
        }
        $request->validate([
            'title' => 'required|string|max:255',
            'video' => 'nullable|url',
        ]);
        if($id>0){
            $this->checkPermission('hotel_update');
            $row = $this->hotelClass::find($id);
            if (empty($row)) {
                if($this->isApiRequest($request)) return response()->json(['message' => __('Hotel not found')], 404);
                return redirect(route('hotel.admin.index'));
            }

            if($row->author_id != Auth::id() and !$this->hasPermission('hotel_manage_others'))
            {
                if($this->isApiRequest($request)) return response()->json(['message' => __('Forbidden')], 403);
                return redirect(route('hotel.admin.index'));
            }
        }else{
            $this->checkPermission('hotel_create');
            $row = new $this->hotelClass();
            $row->status = "publish";
        }
        $dataKeys = [
            'title',
            'content',
            'video',
            'image_id',
            'banner_image_id',
            'gallery',
            'is_featured',
            'policy',
            'location_id',
            'address',
            'map_lat',
            'map_lng',
            'map_zoom',
            'star_rate',
            'price',
            'sale_price',
            'check_in_time',
            'check_out_time',
            'allow_full_day',
            'enable_extra_price',
            'extra_price',
            'status',
            'min_day_before_booking',
            'min_day_stays',
            'enable_service_fee',
            'service_fee',
            'surrounding',
            'related_ids',
            'phone',
            'website',
        ];
        if($this->hasPermission('hotel_manage_others')){
            $dataKeys[] = 'author_id';
        }

        $row->fillByAttr($dataKeys,$request->input());
        if($request->input('slug')){
            $row->slug = $request->input('slug');
        }

        $res = $row->saveOriginOrTranslation($request->input('lang'),true);

        if ($res) {
            if(!$request->input('lang') or is_default_lang($request->input('lang'))) {
                $this->saveTerms($row, $request);
            }
            do_action(Hook::AFTER_SAVING,$row,$request);

            if($id > 0 ){
                event(new UpdatedServiceEvent($row));
                if($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Hotel updated'), 'data' => $row->fresh()]);
                }
                return back()->with('success',  __('Hotel updated') );
            }else{
                event(new CreatedServicesEvent($row));
                if($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Hotel created'), 'data' => $row->fresh()], 201);
                }
                return redirect(route('hotel.admin.edit',$row->id))->with('success', __('Hotel created') );
            }
        }
        if($this->isApiRequest($request)) {
            return response()->json(['message' => __('Could not save hotel')], 500);
        }
    }

    public function saveTerms($row, $request)
    {
        if (!$this->hasPermission('hotel_manage_attributes')) return;
        if (empty($request->input('terms'))) {
            $this->hotelTermClass::where('target_id', $row->id)->delete();
        } else {
            $term_ids = $request->input('terms');
            foreach ($term_ids as $term_id) {
                $this->hotelTermClass::firstOrCreate([
                    'term_id'   => $term_id,
                    'target_id' => $row->id,
                ]);
            }
            $this->hotelTermClass::where('target_id', $row->id)->whereNotIn('term_id', $term_ids)->delete();
        }
    }

    public function bulkEdit(Request $request)
    {
        $ids    = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) or !is_array($ids)) {
            if ($this->isApiRequest($request)) return response()->json(['message' => __('No items selected!')], 422);
            return redirect()->back()->with('error', __('No items selected!'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) return response()->json(['message' => __('Please select an action!')], 422);
            return redirect()->back()->with('error', __('Please select an action!'));
        }

        switch ($action) {
            case "delete":
                foreach ($ids as $id) {
                    $query = $this->hotelClass::where("id", $id);
                    if (!$this->hasPermission('hotel_manage_others')) {
                        $query->where("author_id", Auth::id());
                        $this->checkPermission('hotel_delete');
                    }
                    $row = $query->first();
                    if (!empty($row)) { $row->delete(); event(new UpdatedServiceEvent($row)); }
                }
                $msg = __('Deleted success!');
                break;
            case "permanently_delete":
                foreach ($ids as $id) {
                    $query = $this->hotelClass::where("id", $id);
                    if (!$this->hasPermission('hotel_manage_others')) {
                        $query->where("author_id", Auth::id());
                        $this->checkPermission('hotel_delete');
                    }
                    $row = $query->withTrashed()->first();
                    if ($row) $row->forceDelete();
                }
                $msg = __('Permanently delete success!');
                break;
            case "recovery":
                foreach ($ids as $id) {
                    $query = $this->hotelClass::withTrashed()->where("id", $id);
                    if (!$this->hasPermission('hotel_manage_others')) {
                        $query->where("author_id", Auth::id());
                        $this->checkPermission('hotel_delete');
                    }
                    $row = $query->first();
                    if (!empty($row)) { $row->restore(); event(new UpdatedServiceEvent($row)); }
                }
                $msg = __('Recovery success!');
                break;
            case "clone":
                $this->checkPermission('hotel_create');
                foreach ($ids as $id) {
                    (new $this->hotelClass())->saveCloneByID($id);
                }
                $msg = __('Clone success!');
                break;
            default:
                foreach ($ids as $id) {
                    $query = $this->hotelClass::where("id", $id);
                    if (!$this->hasPermission('hotel_manage_others')) {
                        $query->where("author_id", Auth::id());
                        $this->checkPermission('hotel_update');
                    }
                    $row = $query->first();
                    if (!empty($row)) { $row->status = $action; $row->save(); event(new UpdatedServiceEvent($row)); }
                }
                $msg = __('Update success!');
                break;
        }

        if ($this->isApiRequest($request)) return response()->json(['message' => $msg]);
        return redirect()->back()->with('success', $msg);
    }
    public function getForSelect2(Request $request)
    {
        $pre_selected = $request->query('pre_selected');
        $selected = $request->query('selected');

        if($pre_selected && $selected){
            if(is_array($selected))
            {
                $items = Hotel::select('id', 'title as text')->whereIn('id',$selected)->take(50)->get();

            }else{
                $items = Hotel::find($selected);
            }
            return [
                'results'=>$items
            ];
        }

        $q = $request->query('q');
        $query = Hotel::select('id', 'title as text')->where("status","publish");
        if ($q) {
            $query->where('title', 'like', '%' . $q . '%');
        }
        $res = $query->orderBy('id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }
}
