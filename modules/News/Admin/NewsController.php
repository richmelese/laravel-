<?php
namespace Modules\News\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\AdminController;
use Modules\Language\Models\Language;
use Modules\News\Models\NewsCategory;
use Modules\News\Models\News;
use Modules\News\Models\NewsTranslation;

class NewsController extends AdminController
{
    protected function bumpNewsApiCacheVersion(): void
    {
        Cache::add('api_cache:news:version', 1);
        Cache::increment('api_cache:news:version');
    }

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
        $this->checkPermission('news_view');
        $dataNews = News::query()->orderBy('id', 'desc');
        $post_name = $request->query('s');
        $cate = $request->query('cate_id');
        if ($cate) {
           $dataNews->where('cat_id', $cate);
        }
        if ($post_name) {
            $dataNews->where('title', 'LIKE', '%' . $post_name . '%');
            $dataNews->orderBy('title', 'asc');
        }

        $data = [
            'rows'        => $dataNews->with("author")->with("category")->paginate(20),
            'categories'  => NewsCategory::get(),
            'breadcrumbs' => [
                [
                    'name' => __('News'),
                    'url'  => route('news.admin.index')
                ],
                [
                    'name'  => __('All'),
                    'class' => 'active'
                ],
            ],
            "languages"=>Language::getActive(false),
            "locale"=>\App::getLocale(),
            'page_title'=>__("News Management")
        ];
        if ($this->isApiRequest($request)) {
            $rows = $data['rows'];
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
        return view('News::admin.news.index', $data);
    }

    public function create(Request $request)
    {
        $this->checkPermission('news_create');
        $row = new News();
        $row->fill([
            'status' => 'publish',
        ]);
        $data = [
            'categories'        => NewsCategory::get()->toTree(),
            'row'         => $row,
            'breadcrumbs' => [
                [
                    'name' => __('News'),
                    'url'  => route('news.admin.index')
                ],
                [
                    'name'  => __('Add News'),
                    'class' => 'active'
                ],
            ],
            'translation'=>new NewsTranslation()
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row' => $data['row'],
                    'categories' => $data['categories'],
                    'translation' => $data['translation'],
                ],
            ]);
        }
        return view('News::admin.news.detail', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('news_update');

        $row = News::find($id);

        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'News not found'], 404);
            }
            return redirect(route('news.admin.index'));
        }
        $translation = $row->translate($request->query('lang',get_main_lang()));

        $data = [
            'row'  => $row,
            'translation'  => $translation,
            'categories' => NewsCategory::get()->toTree(),
            'tags' => $row->tags,
            'enable_multi_lang'=>true
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row' => $data['row'],
                    'translation' => $data['translation'],
                    'categories' => $data['categories'],
                    'tags' => $data['tags'],
                    'enable_multi_lang' => $data['enable_multi_lang'],
                ],
            ]);
        }
        return view('News::admin.news.detail', $data);
    }

    public function store(Request $request, $id){
        if(is_demo_mode()){
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __("DEMO MODE: Disable update")], 403);
            }
            return redirect()->back()->with('danger',__("DEMO MODE: Disable update"));
        }
        if($id>0){
            $this->checkPermission('news_update');
            $row = News::find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => 'News not found'], 404);
                }
                return redirect(route('news.admin.index'));
            }
        }else{
            $this->checkPermission('news_create');
            $row = new News();
            $row->status = "publish";
        }

        $row->fill($request->input());
        if($request->input('slug')){
            $row->slug = $request->input('slug');
        }
        $row->author_id = $request->input('author_id');
        $res = $row->saveOriginOrTranslation($request->query('lang'),true);

        if ($res) {
            if(is_default_lang($request->query('lang'))){
                $row->saveTag($request->input('tag_name'), $request->input('tag_ids'));
            }
            $this->bumpNewsApiCacheVersion();
            if($id > 0 ){
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('News updated'), 'data' => $row]);
                }
                return back()->with('success',  __('News updated') );
            }else{
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('News created'), 'data' => $row], 201);
                }
                return redirect(route('news.admin.edit',$row->id))->with('success', __('News created') );
            }
        }
    }

    public function bulkEdit(Request $request)
    {
        if(is_demo_mode()){
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __("DEMO MODE: Disable update")], 403);
            }
            return redirect()->back()->with('danger',__("DEMO MODE: Disable update"));
        }
        $this->checkPermission('news_update');
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
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = News::where("id", $id);
                if (!$this->hasPermission('news_manage_others')) {
                    $query->where("create_user", Auth::id());
                    $this->checkPermission('news_delete');
                }
                $query->first();
                if(!empty($query)){
                    $query->delete();
                }
            }
            $this->bumpNewsApiCacheVersion();
        } else {
            foreach ($ids as $id) {
                $query = News::where("id", $id);
                if (!$this->hasPermission('news_manage_others')) {
                    $query->where("create_user", Auth::id());
                    $this->checkPermission('news_update');
                }
                $query->update(['status' => $action]);
            }
            $this->bumpNewsApiCacheVersion();
        }
        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Update success!')]);
        }
        return redirect()->back()->with('success', __('Update success!'));
    }

    public function trans($id,$locale){
        $row = News::find($id);

        if(empty($row)){
            return redirect()->back()->with("danger",__("News does not exists"));
        }

        $translated = News::query()->where('origin_id',$id)->where('lang',$locale)->first();
        if(!empty($translated)){
            redirect($translated->getEditUrl());
        }

        $language = Language::where('locale',$locale)->first();
        if(empty($language)){
            return redirect()->back()->with("danger",__("Language does not exists"));
        }

        $new = $row->replicate();

        if(!$row->origin_id){
            $new->origin_id = $row->id;
        }

        $new->lang = $locale;

        $new->save();


        return redirect($new->getEditUrl());
    }
}
