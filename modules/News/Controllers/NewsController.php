<?php
namespace Modules\News\Controllers;

use Illuminate\Http\Request;
use Modules\FrontendController;
use Modules\Language\Models\Language;
use Modules\News\Models\News;
use Modules\News\Models\NewsCategory;
use Modules\News\Models\NewsTranslation;
use Modules\News\Models\Tag;

class NewsController extends FrontendController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $layout = setting_item("news_layout_search", 'normal');
        if ($request->query('_layout')) {
            $layout = $request->query('_layout');
        }
        $model_News = News::query()->select("core_news.*");
        $model_News->where("core_news.status", "publish")->orderBy('core_news.id', 'desc');
        if (!empty($search = $request->input("s"))) {
            $model_News->where(function($query) use ($search) {
                $query->where('core_news.title', 'LIKE', '%' . $search . '%');
                $query->orWhere('core_news.content', 'LIKE', '%' . $search . '%');
            });

            if( setting_item('site_enable_multi_lang') && setting_item('site_locale') != app_get_locale() ){
                $model_News->leftJoin('core_news_translations', function ($join) use ($search) {
                    $join->on('core_news.id', '=', 'core_news_translations.origin_id');
                });
                $model_News->orWhere(function($query) use ($search) {
                    $query->where('core_news_translations.title', 'LIKE', '%' . $search . '%');
                    $query->orWhere('core_news_translations.content', 'LIKE', '%' . $search . '%');
                });
            }

            $title_page = __('Search results : ":s"', ["s" => $search]);
        }
        $data = [
            'rows'              => $model_News->with('author', 'translation', 'category')->paginate((int)setting_item('news_posts_per_page', 5)),
            'model_category'    => NewsCategory::query()->where("status", "publish"),
            'model_tag'         => Tag::query(),
            'model_news'        => News::query()->where("status", "publish"),
            'custom_title_page' => $title_page ?? "",
            'breadcrumbs'       => [
                [
                    'name'  => __('News'),
                    'class' => 'active'
                ]
            ],
            "seo_meta" => News::getSeoMetaForPageList(),
            "languages"=>Language::getActive(false),
            "locale"=> app()->getLocale(),
            'header_transparent'=>true,
            'layout'=>$layout
        ];

        // API/JSON response for /api/news
        if ($request->wantsJson() || $request->is('api/*')) {
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

        return view('News::frontend.index', $data);
    }

    public function detail(Request $request, $slug)
    {
        $row = News::where('slug', $slug)->where('status','publish')->first();
        if (empty($row)) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'News not found'], 404);
            }
            return redirect('/');
        }
        $adminbar_buttons = [];

        if(is_admin()){
            $adminbar_buttons[] = ['label' => __('Edit News'), 'url' => route('news.admin.edit',['id' => $row->id]), 'icon' => 'edit'];
        }
        $translation = $row->translate();

        if (!empty($cat_id = $row->cat_id)) {
            $related = News::where('cat_id', $cat_id)->where("status","publish")->take(4)->whereNotIn('id', [$row->id])->with(['translation'])->get();
        }

        $data = [
            'row'               => $row,
            'translation'       => $translation,
            'model_category'    => NewsCategory::where("status", "publish"),
            'model_tag'         => Tag::query(),
            'model_news'        => News::where("status", "publish"),
            'custom_title_page' => $title_page ?? "",
            'related'           => $related ?? false,
            'breadcrumbs'       => [
                [
                    'name' => __('News'),
                    'url'  => route('news.index')
                ],
                [
                    'name'  => $translation->title,
                    'class' => 'active'
                ],
            ],
            'seo_meta'  => $row->getSeoMetaWithTranslation(app()->getLocale(),$translation),
            'adminbar_buttons' => $adminbar_buttons
        ];
        $this->setActiveMenu($row);

        // API/JSON response for /api/news/{slug}
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'data' => [
                    'news'        => $row,
                    'translation' => $translation,
                    'related'     => $related ?? [],
                ],
            ]);
        }

        return view('News::frontend.detail', $data);
    }
}
