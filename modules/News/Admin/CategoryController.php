<?php
namespace Modules\News\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\News\Models\NewsCategory;
use Illuminate\Support\Str;
use Modules\News\Models\NewsCategoryTranslation;

class CategoryController extends AdminController
{
    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    public function __construct()
    {
        $this->setActiveMenu(route('news.admin.index'));
    }

    public function index(Request $request)
    {
        $this->checkPermission('news_manage_others');

        $catlist = NewsCategory::query();
        if ($catename = $request->query('s')) {
            $catlist = $catlist->where('name', 'LIKE', '%' . $catename . '%');
        }
        $catlist = $catlist->orderby('name', 'asc');
        if ($this->isApiRequest($request)) {
            $perPage = (int) $request->query('per_page', 20);
            if ($perPage < 1) {
                $perPage = 20;
            }
            $rows = $catlist->paginate($perPage);
            return response()->json([
                'data' => $rows->items(),
                'meta' => [
                    'current_page' => $rows->currentPage(),
                    'per_page'     => $rows->perPage(),
                    'total'        => $rows->total(),
                    'last_page'    => $rows->lastPage(),
                ],
            ]);
        }
        $rows = $catlist->get();

        $data = [
            'rows'        => $rows->toTree(),
            'row'         => new NewsCategory(),
            'breadcrumbs' => [
                [
                    'name' => __('News'),
                    'url'  => route('news.admin.index')
                ],
                [
                    'name'  => __('Category'),
                    'class' => 'active'
                ],
            ],
            'translation'=>new NewsCategoryTranslation()
        ];
        return view('News::admin.category.index', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('news_manage_others');
        $row = NewsCategory::find($id);

        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Category not found'], 404);
            }
            return redirect(route('news.admin.category.index'));
        }
        $translation = $row->translate($request->query('lang',get_main_lang()));
        $data = [
            'row'     => $row,
            'translation'     => $translation,
            'parents' => NewsCategory::get()->toTree(),
            'enable_multi_lang'=>true
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row' => $data['row'],
                    'translation' => $data['translation'],
                    'parents' => $data['parents'],
                    'enable_multi_lang' => $data['enable_multi_lang'],
                ],
            ]);
        }
        return view('News::admin.category.detail', $data);
    }

    public function store(Request $request, $id){
        $this->checkPermission('news_manage_others');

        if($id>0){
            $row = NewsCategory::find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => 'Category not found'], 404);
                }
                return redirect(route('news.admin.category.index'));
            }
        }else{
            $row = new NewsCategory();
            $row->status = "publish";
        }
        $row->fill($request->input());
        if($request->input('slug')){
            $row->slug = $request->input('slug');
        }
        $res = $row->saveOriginOrTranslation($request->input('lang'));

        if ($res) {
            if($id > 0 ){
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Category updated'), 'data' => $row]);
                }
                return back()->with('success',  __('Category updated') );
            }else{
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Category created'), 'data' => $row], 201);
                }
                return redirect(route('news.admin.category.index'))->with('success', __('Category created') );
            }
        }
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('news_manage_others');
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Please select at least 1 item!')], 422);
            }
            return redirect()->back()->with('error', __('Please select at least 1 item!'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Please select an Action!')], 422);
            }
            return redirect()->back()->with('error', __('Please select an Action!'));
        }
        if ($action == 'delete') {
            foreach ($ids as $id) {
                $query = NewsCategory::where("id", $id)->first();
                if(!empty($query)){
                    $query->delete();
                }
            }
        }
        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Update success!')]);
        }
        return redirect()->back()->with('success', __('Update success!'));
    }

    public function getForSelect2(Request $request)
    {
        $pre_selected = $request->query('pre_selected');
        $selected = $request->query('selected');

        if($pre_selected && $selected){
            $items = NewsCategory::find($selected);

            return [
                'results'=>$items
            ];
        }
        $q = $request->query('q');
        $query = NewsCategory::select('id', 'name as text')->where("status","publish");
        if ($q) {
            $query->where('name', 'like', '%' . $q . '%');
        }
        $res = $query->orderBy('id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }
}
