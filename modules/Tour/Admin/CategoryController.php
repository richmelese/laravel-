<?php
namespace Modules\Tour\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\AdminController;
use Modules\Tour\Hook;
use Modules\Tour\Models\Tour;
use Modules\Tour\Models\TourCategory;
use Modules\Tour\Models\TourCategoryTranslation;

class CategoryController extends AdminController
{
    protected $tourCategoryClass;
    public function __construct()
    {
        $this->setActiveMenu(route('tour.admin.index'));
        $this->tourCategoryClass = TourCategory::class;
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
        $this->checkPermission('tour_manage_others');
        $listCategory = $this->tourCategoryClass::query();
        if (!empty($search = $request->query('s'))) {
            $listCategory->where('name', 'LIKE', '%' . $search . '%');
        }
        $listCategory->orderBy('created_at', 'desc');
        $data = [
            'rows'        => $listCategory->get()->toTree(),
            'row'         => new $this->tourCategoryClass(),
            'translation'    => new TourCategoryTranslation(),
            'breadcrumbs' => [
                [
                    'name' => __('Tour'),
                    'url'  => route('tour.admin.index')
                ],
                [
                    'name'  => __('Category'),
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

        return view('Tour::admin.category.index', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('tour_manage_others');
        $row = $this->tourCategoryClass::find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Category not found')], 404);
            }
            return redirect(route('tour.admin.category.index'));
        }
        $translation = $row->translate($request->query('lang',get_main_lang()));
        $data = [
            'translation'    => $translation,
            'enable_multi_lang'=>true,
            'row'         => $row,
            'parents'     => $this->tourCategoryClass::get()->toTree(),
            'breadcrumbs' => [
                [
                    'name' => __('Tour'),
                    'url'  => route('tour.admin.index')
                ],
                [
                    'name'  => __('Category'),
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
        return view('Tour::admin.category.detail', $data);
    }

    public function store(Request $request , $id)
    {
        $this->checkPermission('tour_manage_others');
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Validation failed'), 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }
        if($id>0){
            $row = $this->tourCategoryClass::find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Category not found')], 404);
                }
                return redirect(route('tour.admin.category.index'));
            }
        }else{
            $row = new $this->tourCategoryClass();
            $row->status = "publish";
        }

        $row->fill($request->input());
        $res = $row->saveOriginOrTranslation($request->input('lang'),true);

        if ($res) {
            do_action(Hook::AFTER_SAVING_CATEGORY,$row,$request);
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Category saved'), 'data' => $row]);
            }
            return back()->with('success',  __('Category saved') );
        }
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('tour_manage_others');
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Select at least 1 item!')], 422);
            }
            return redirect()->back()->with('error', __('Select at least 1 item!'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Select an Action!')], 422);
            }
            return redirect()->back()->with('error', __('Select an Action!'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = $this->tourCategoryClass::where("id", $id)->first();
                if(!empty($query)){
                    //Sync child category
                    $list_childs = $this->tourCategoryClass::where("parent_id", $id)->get();
                    if(!empty($list_childs)){
                        foreach ($list_childs as $child){
                            $child->parent_id = null;
                            $child->save();
                        }
                    }
                    //Del parent category
                    $query->delete();
                }
            }
        } else {
            foreach ($ids as $id) {
                $query = $this->tourCategoryClass::where("id", $id);
                $query->update(['status' => $action]);
            }
        }
        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Updated success!')]);
        }
        return redirect()->back()->with('success', __('Updated success!'));
    }

    public function getForSelect2(Request $request)
    {
        $pre_selected = $request->query('pre_selected');
        $selected = $request->query('selected');

        if($pre_selected && $selected){
            $item = $this->tourCategoryClass::find($selected);
            if(empty($item)){
                return $this->isApiRequest($request) ? response()->json(['results' => []]) : [];
            }
            $payload = [
                'results'=>[
                    'id'=>$item->id,
                    'text'=>$item->name
                ]
            ];
            return $this->isApiRequest($request) ? response()->json($payload) : $payload;
        }
        $q = $request->query('q');
        $query = $this->tourCategoryClass::select('id', 'name as text')->where("status","publish");
        if ($q) {
            $query->where('name', 'like', '%' . $q . '%');
        }
        $res = $query->orderBy('id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }
}

