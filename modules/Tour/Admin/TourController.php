<?php
namespace Modules\Tour\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\AdminController;
use Modules\Core\Events\CreatedServicesEvent;
use Modules\Core\Events\UpdatedServiceEvent;
use Modules\Core\Models\Attributes;
use Modules\Location\Models\LocationCategory;
use Modules\Tour\Hook;
use Modules\Tour\Models\TourTerm;
use Modules\Tour\Models\Tour;
use Modules\Tour\Models\TourCategory;
use Modules\Tour\Models\TourTranslation;
use Modules\Location\Models\Location;

class TourController extends AdminController
{
    protected $tourClass;
    protected $tourTranslationClass;
    protected $tourCategoryClass;
    protected $tourTermClass;
    protected $attributesClass;
    protected $locationClass;
    /**
     * @var string
     */
    private $locationCategoryClass;

    public function __construct()
    {
        $this->setActiveMenu(route('tour.admin.index'));
        $this->tourClass = Tour::class;
        $this->tourTranslationClass = TourTranslation::class;
        $this->tourCategoryClass = TourCategory::class;
        $this->tourTermClass = TourTerm::class;
        $this->attributesClass = Attributes::class;
        $this->locationClass = Location::class;
        $this->locationCategoryClass = LocationCategory::class;
    }

    public function callAction($method, $parameters)
    {
        if (! Tour::isEnable()) {
            $request = request();
            if ($request && ($request->expectsJson() || $request->is('api-admin/*'))) {
                return response()->json(['message' => __('Tour module is disabled')], 503);
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
        $this->checkPermission('tour_view');
        $query = $this->tourClass::query();
        $query->orderBy('id', 'desc');
        if (!empty($tour_name = $request->input('s'))) {
            $query->where('title', 'LIKE', '%' . $tour_name . '%');
            $query->orderBy('title', 'asc');
        }
        if (!empty($cate = $request->input('cate_id'))) {
            $query->where('category_id', $cate);
        }
        if (!empty($is_featured = $request->input('is_featured'))) {
            $query->where('is_featured', 1);
        }
        if (!empty($location_id = $request->query('location_id'))) {
            $query->where('location_id', $location_id);
        }
        if ($this->hasPermission('tour_manage_others')) {
            if (!empty($author = $request->input('vendor_id'))) {
                $query->where('author_id', $author);
            }
        } else {
            $query->where('author_id', Auth::id());
        }
        $data = [
            'rows'               => $query->with([
                'author',
                'category_tour'
            ])->paginate(20),
            'tour_categories'    => $this->tourCategoryClass::where('status', 'publish')->get()->toTree(),
            'tour_manage_others' => $this->hasPermission('tour_manage_others'),
            'page_title'         => __("Tour Management"),
            'breadcrumbs'        => [
                [
                    'name' => __('Tours'),
                    'url'  => route('tour.admin.index')
                ],
                [
                    'name'  => __('All'),
                    'class' => 'active'
                ],
            ]
        ];
        if ($this->isApiRequest($request)) {
            $rows = $data['rows'];
            return response()->json([
                'data'                 => $rows->items(),
                'meta'                 => [
                    'current_page' => $rows->currentPage(),
                    'per_page'     => $rows->perPage(),
                    'total'        => $rows->total(),
                    'last_page'    => $rows->lastPage(),
                ],
                'tour_categories'      => $data['tour_categories'],
                'tour_manage_others'   => $data['tour_manage_others'],
                'page_title'           => $data['page_title'],
            ]);
        }
        return view('Tour::admin.index', $data);
    }

    public function recovery(Request $request)
    {
        $this->checkPermission('tour_view');
        $query = $this->tourClass::onlyTrashed();
        $query->orderBy('id', 'desc');
        if (!empty($tour_name = $request->input('s'))) {
            $query->where('title', 'LIKE', '%' . $tour_name . '%');
            $query->orderBy('title', 'asc');
        }
        if (!empty($cate = $request->input('cate_id'))) {
            $query->where('category_id', $cate);
        }
        if ($this->hasPermission('tour_manage_others')) {
            if (!empty($author = $request->input('vendor_id'))) {
                $query->where('author_id', $author);
            }
        } else {
            $query->where('author_id', Auth::id());
        }
        $data = [
            'rows'               => $query->with([
                'author',
                'category_tour'
            ])->paginate(20),
            'tour_categories'    => $this->tourCategoryClass::where('status', 'publish')->get()->toTree(),
            'tour_manage_others' => $this->hasPermission('tour_manage_others'),
            'page_title'         => __("Recovery Tour Management"),
            'recovery'           => 1,
            'breadcrumbs'        => [
                [
                    'name' => __('Tours'),
                    'url'  => route('tour.admin.index')
                ],
                [
                    'name'  => __('Recovery'),
                    'class' => 'active'
                ],
            ]
        ];
        if ($this->isApiRequest($request)) {
            $rows = $data['rows'];
            return response()->json([
                'data'               => $rows->items(),
                'meta'               => [
                    'current_page' => $rows->currentPage(),
                    'per_page'     => $rows->perPage(),
                    'total'        => $rows->total(),
                    'last_page'    => $rows->lastPage(),
                ],
                'recovery'           => true,
                'tour_categories'    => $data['tour_categories'],
                'tour_manage_others' => $data['tour_manage_others'],
                'page_title'         => $data['page_title'],
            ]);
        }
        return view('Tour::admin.index', $data);
    }

    public function create(Request $request)
    {
        $this->checkPermission('tour_create');
        $row = new Tour();
        $row->fill([
            'status' => 'publish'
        ]);
        $data = [
            'row'               => $row,
            'attributes'        => $this->attributesClass::where('service', 'tour')->get(),
            'tour_category'     => $this->tourCategoryClass::where('status', 'publish')->get()->toTree(),
            'tour_location'     => $this->locationClass::where('status', 'publish')->get()->toTree(),
            'location_category' => $this->locationCategoryClass::where("status", "publish")->get(),
            'translation'       => new $this->tourTranslationClass(),
            'breadcrumbs'       => [
                [
                    'name' => __('Tours'),
                    'url'  => route('tour.admin.index')
                ],
                [
                    'name'  => __('Add Tour'),
                    'class' => 'active'
                ],
            ],
            'page_title'        => __("Add Tour"),
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row'               => $data['row'],
                    'attributes'        => $data['attributes'],
                    'tour_category'     => $data['tour_category'],
                    'tour_location'     => $data['tour_location'],
                    'location_category' => $data['location_category'],
                    'translation'       => $data['translation'],
                    'page_title'        => $data['page_title'],
                ],
            ]);
        }
        return view('Tour::admin.detail', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('tour_update');
        $row = $this->tourClass::find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Tour not found')], 404);
            }
            return redirect(route('tour.admin.index'));
        }
        $translation = $row->translate($request->query('lang',get_main_lang()));
        if (!$this->hasPermission('tour_manage_others')) {
            if ($row->author_id != Auth::id()) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Forbidden')], 403);
                }
                return redirect(route('tour.admin.index'));
            }
        }
        $data = [
            'row'               => $row,
            'translation'       => $translation,
            "selected_terms"    => $row->tour_term->pluck('term_id'),
            'attributes'        => $this->attributesClass::where('service', 'tour')->get(),
            'tour_category'     => $this->tourCategoryClass::where('status', 'publish')->get()->toTree(),
            'tour_location'     => $this->locationClass::where('status', 'publish')->get()->toTree(),
            'location_category' => $this->locationCategoryClass::where("status", "publish")->get(),
            'enable_multi_lang' => true,
            'breadcrumbs'       => [
                [
                    'name' => __('Tours'),
                    'url'  => route('tour.admin.index')
                ],
                [
                    'name'  => __('Edit Tour'),
                    'class' => 'active'
                ],
            ],
            'page_title'=>__('Edit Tour')
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row'               => $data['row'],
                    'translation'       => $data['translation'],
                    'selected_terms'    => $data['selected_terms'],
                    'attributes'        => $data['attributes'],
                    'tour_category'     => $data['tour_category'],
                    'tour_location'     => $data['tour_location'],
                    'location_category' => $data['location_category'],
                    'enable_multi_lang' => $data['enable_multi_lang'],
                    'page_title'        => $data['page_title'],
                ],
            ]);
        }
        return view('Tour::admin.detail', $data);
    }

    public function store(Request $request, $id)
    {

        if (is_demo_mode()) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __("DEMO MODE: can not add data")], 403);
            }
            return redirect()->back()->with('danger', __("DEMO MODE: can not add data"));
        }
        if ($id > 0) {
            $this->checkPermission('tour_update');
            $row = $this->tourClass::find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Tour not found')], 404);
                }
                return redirect(route('tour.admin.index'));
            }
            if ($row->author_id != Auth::id() and !$this->hasPermission('tour_manage_others')) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Forbidden')], 403);
                }
                return redirect(route('tour.admin.index'));
            }
        } else {
            $this->checkPermission('tour_create');
            $row = new $this->tourClass();
            $row->status = "publish";
        }
        if (! empty($request->input('enable_fixed_date'))) {
            $rules = [
                'start_date'        => 'required|date',
                'end_date'         => 'required|date|after_or_equal:start_date',
                'last_booking_date' => 'required|date|before:start_date|after:'.now(),
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Validation failed'), 'errors' => $validator->errors()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $row->fill($request->input());
        if ($request->input('slug')) {
            $row->slug = $request->input('slug');
        }
        $row->ical_import_url = $request->ical_import_url;
        $row->author_id = $request->input('author_id');
        $row->default_state = $request->input('default_state', 1);
        $row->enable_service_fee = $request->input('enable_service_fee');
        $row->service_fee = $request->input('service_fee');
        $res = $row->saveOriginOrTranslation($request->input('lang'), true);

        if ($res) {
            if (!$request->input('lang') or is_default_lang($request->input('lang'))) {
                $this->saveTerms($row, $request);
                $row->saveMeta($request);
            }

            do_action(Hook::AFTER_SAVING,$row,$request);

            if ($id > 0) {
                event(new UpdatedServiceEvent($row));
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Tour updated'), 'data' => $row]);
                }
                return back()->with('success', __('Tour updated'));
            } else {
                event(new CreatedServicesEvent($row));
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Tour created'), 'data' => $row], 201);
                }
                return redirect(route('tour.admin.edit', $row->id))->with('success', __('Tour created'));
            }
        }
    }

    public function saveTerms($row, $request)
    {
        if (empty($request->input('terms'))) {
            $this->tourTermClass::where('tour_id', $row->id)->delete();
        } else {
            $term_ids = $request->input('terms');
            foreach ($term_ids as $term_id) {
                $this->tourTermClass::firstOrCreate([
                    'term_id' => $term_id,
                    'tour_id' => $row->id
                ]);
            }
            $this->tourTermClass::where('tour_id', $row->id)->whereNotIn('term_id', $term_ids)->delete();
        }
    }

    public function bulkEdit(Request $request)
    {

        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('No items selected!')], 422);
            }
            return redirect()->back()->with('error', __('No items selected!'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Please select an action!')], 422);
            }
            return redirect()->back()->with('error', __('Please select an action!'));
        }
        switch ($action) {
            case "delete":
                foreach ($ids as $id) {
                    $query = $this->tourClass::where("id", $id);
                    if (!$this->hasPermission('tour_manage_others')) {
                        $query->where("create_user", Auth::id());
                        $this->checkPermission('tour_delete');
                    }
                    $row = $query->first();
                    if (!empty($row)) {
                        $row->delete();
                        event(new UpdatedServiceEvent($row));
                    }
                }
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Deleted success!')]);
                }
                return redirect()->back()->with('success', __('Deleted success!'));
            case "permanently_delete":
                foreach ($ids as $id) {
                    $query = $this->tourClass::where("id", $id);
                    if (!$this->hasPermission('tour_manage_others')) {
                        $query->where("create_user", Auth::id());
                        $this->checkPermission('tour_delete');
                    }
                    $row = $query->withTrashed()->first();
                    if ($row) {
                        $row->forceDelete();
                    }
                }
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Permanently delete success!')]);
                }
                return redirect()->back()->with('success', __('Permanently delete success!'));
            case "recovery":
                foreach ($ids as $id) {
                    $query = $this->tourClass::withTrashed()->where("id", $id);
                    if (!$this->hasPermission('tour_manage_others')) {
                        $query->where("create_user", Auth::id());
                        $this->checkPermission('tour_delete');
                    }
                    $row = $query->first();
                    if (!empty($row)) {
                        $row->restore();
                        event(new UpdatedServiceEvent($row));
                    }
                }
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Recovery success!')]);
                }
                return redirect()->back()->with('success', __('Recovery success!'));
            case "clone":
                $this->checkPermission('tour_create');
                foreach ($ids as $id) {
                    (new $this->tourClass())->saveCloneByID($id);
                }
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Clone success!')]);
                }
                return redirect()->back()->with('success', __('Clone success!'));
            default:
                // Change status
                foreach ($ids as $id) {
                    $query = $this->tourClass::where("id", $id);
                    if (!$this->hasPermission('tour_manage_others')) {
                        $query->where("create_user", Auth::id());
                        $this->checkPermission('tour_update');
                    }
                    $row = $query->first();
                    if ($row) {
                        $row->status = $action;
                        $row->save();
                        event(new UpdatedServiceEvent($row));
                    }
                }
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Update success!')]);
                }
                return redirect()->back()->with('success', __('Update success!'));
        }
    }

    public function getForSelect2(Request $request)
    {
        $pre_selected = $request->query('pre_selected');
        $selected = $request->query('selected');
        if ($pre_selected && $selected) {
            if (is_array($selected)) {
                $items = $this->tourClass::select('id', 'title as text')->whereIn('id', $selected)->take(50)->get();

            } else {
                $items = $this->tourClass::find($selected);
            }

            return [
                'results'=>$items
            ];
        }
        $q = $request->query('q');
        $query = $this->tourClass::select('id', 'title as text')->where("status", "publish");
        if ($q) {
            $query->where('title', 'like', '%' . $q . '%');
        }
        $res = $query->orderBy('id', 'desc')->limit(20)->get();
        return $this->sendSuccess([
            'results' => $res
        ]);
    }
}
