<?php
namespace Modules\Location\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Location\Models\Location;
use Modules\Location\Models\LocationTranslation;

class LocationController extends AdminController
{
    private Location $location;

    public function __construct(Location $location)
    {
        $this->setActiveMenu(route('location.admin.index'));
        $this->location = $location;
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    public function index(Request $request)
    {
        $this->checkPermission('location_view');
        $listLocation = $this->location::query() ;
        if (!empty($search = $request->query('s'))) {
            $listLocation->where('name', 'LIKE', '%' . $search . '%');
        }
        $listLocation->orderBy('created_at', 'asc');
        $data = [
            'rows'        => $listLocation->get()->toTree(),
            'row'         => $this->location,
            'translation' => new ($this->location->getTranslationModelName()),
            'breadcrumbs' => [
                [
                    'name' => __('Location'),
                    'url'  => route('location.admin.index')
                ],
                [
                    'name'  => __('All'),
                    'class' => 'active'
                ],
            ]
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'rows'        => $data['rows'],
                    'row'         => $data['row'],
                    'translation' => $data['translation'],
                ],
            ]);
        }

        return view('Location::admin.index', $data);
    }

    public function create(Request $request)
    {
        $this->checkPermission('location_create');
        $row = new Location();
        $row->status = 'publish';
        $translation = new LocationTranslation();
        $data = [
            'translation' => $translation,
            'enable_multi_lang' => true,
            'row'         => $row,
            'parents'     => $this->location::get()->toTree(),
            'breadcrumbs' => [
                [
                    'name' => __('Location'),
                    'url'  => route('location.admin.index')
                ],
                [
                    'name'  => __('Create'),
                    'class' => 'active'
                ],
            ]
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row'         => $data['row'],
                    'translation' => $data['translation'],
                    'parents'     => $data['parents'],
                    'enable_multi_lang' => $data['enable_multi_lang'],
                ],
            ]);
        }

        return view('Location::admin.detail', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('location_update');
        $row = $this->location::find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Location not found')], 404);
            }

            return redirect(route('location.admin.index'));
        }
        $translation = $row->translate($request->query('lang', get_main_lang()));
        $data = [
            'translation' => $translation,
            'enable_multi_lang'=>true,
            'row'         => $row,
            'parents'     => $this->location::get()->toTree(),
            'breadcrumbs' => [
                [
                    'name' => __('Location'),
                    'url'  => route('location.admin.index')
                ],
                [
                    'name'  => __('Edit'),
                    'class' => 'active'
                ],
            ]
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row'         => $data['row'],
                    'translation' => $data['translation'],
                    'parents'     => $data['parents'],
                    'enable_multi_lang' => $data['enable_multi_lang'],
                ],
            ]);
        }

        return view('Location::admin.detail', $data);
    }

    public function store( Request $request, $id ){
        if(is_demo_mode()){
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __("DEMO MODE: can not add data")], 403);
            }

            return redirect()->back()->with('danger',__("DEMO MODE: can not add data"));
        }

        if ($id > 0) {
            $this->checkPermission('location_update');
            $row = $this->location::find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Location not found')], 404);
                }

                return redirect(route('location.admin.index'));
            }
        } else {
            $this->checkPermission('location_create');
            $row = new Location();
            $row->status = "publish";
        }

        $row->fill($request->input());
        $row->trip_ideas = $request->input('trip_ideas');
        if($request->input('slug')){
            $row->slug = $request->input('slug');
        }
        do_action(\Modules\Location\Hook::BEFORE_SAVING,$row,$request);
        $res = $row->saveOriginOrTranslation($request->input('lang'),true);
        if ($res) {
            if ($this->isApiRequest($request)) {
                return response()->json([
                    'message' => $id > 0 ? __('Location updated') : __('Location created'),
                    'data'    => $row,
                ]);
            }
            if($id > 0 ){
                return back()->with('success',  __('Location updated') );
            }else{
                return redirect(route('location.admin.edit',$row->id))->with('success', __('Location created') );
            }
        }
    }

    public function getForSelect2(Request $request)
    {
        $pre_selected = $request->query('pre_selected');
        $selected = $request->query('selected');

        if($pre_selected && $selected){
            if(is_array($selected))
            {
                $items = $this->location::select('id', 'name as text')->whereIn('id',$selected)->take(50)->get();
                return response()->json([
                    'items'=>$items
                ]);
            }else{
                $items = $this->location::find($selected);
            }

            return response()->json([
                'results'=>$items
            ]);
        }

        $q = $request->query('q');
        $query = $this->location::select('id', 'name as text')->where("status","publish");
        if ($q) {
            $query->where('name', 'like', '%' . $q . '%');
        }
        $res = $query->orderBy('id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }

    public function bulkEdit(Request $request)
    {
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __("Select at least 1 item!")], 422);
            }

            return redirect()->back()->with('error', __("Select at least 1 item!"));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Select an Action!')], 422);
            }

            return redirect()->back()->with('error', __('Select an Action!'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = $this->location::where("id", $id);
                if (!$this->hasPermission('location_manage_others')) {
                    $query->where("create_user", Auth::id());
                    $this->checkPermission('location_delete');
                }
                $query->first();
                if(!empty($query)){
                    //Sync child location
                    $list_childs = $this->location::where("parent_id", $id)->get();
                    if(!empty($list_childs)){
                        foreach ($list_childs as $child){
                            $child->parent_id = null;
                            $child->save();
                        }
                    }
                    //Del parent location
                    $query->delete();
                }
            }
        } else {
            foreach ($ids as $id) {
                $query = $this->location::where("id", $id);
                if (!$this->hasPermission('location_manage_others')) {
                    $query->where("create_user", Auth::id());
                    $this->checkPermission('location_update');
                }
                $query->update(['status' => $action]);
            }
        }
        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Updated success!')]);
        }

        return redirect()->back()->with('success', __('Updated success!'));
    }
}
