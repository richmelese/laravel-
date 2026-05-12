<?php
namespace Modules\Api\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\News\Models\News;
use Modules\News\Models\NewsCategory;

class NewsController extends Controller
{
    private const MAX_PER_PAGE = 50;

    public function search(Request $request){
        $perPage = max(1, min((int) $request->query('per_page', 10), self::MAX_PER_PAGE));
        $params = $request->query();
        $params['per_page'] = $perPage;
        $params['v'] = (int) Cache::get('api_cache:news:version', 1);
        ksort($params);
        $cacheKey = sprintf('api:news:search:%s:%s', app_get_locale(), md5(json_encode($params)));
        $payload = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($request) {
            $model_News = News::query()->select("core_news.*");
            $model_News->where("core_news.status", "publish")->orderBy('core_news.id', 'desc');
            if (!empty($search = $request->query("s"))) {
                $model_News->where(function ($query) use ($search) {
                    $query->where('core_news.title', 'LIKE', '%' . $search . '%');
                    $query->orWhere('core_news.content', 'LIKE', '%' . $search . '%');
                });

                if (setting_item('site_enable_multi_lang') && setting_item('site_locale') != app_get_locale()) {
                    $model_News->leftJoin('core_news_translations', function ($join) use ($search) {
                        $join->on('core_news.id', '=', 'core_news_translations.origin_id');
                    });
                    $model_News->orWhere(function ($query) use ($search) {
                        $query->where('core_news_translations.title', 'LIKE', '%' . $search . '%');
                        $query->orWhere('core_news_translations.content', 'LIKE', '%' . $search . '%');
                    });
                }
            }
            if ($cat_id = $request->query('cat_id')) {
                $model_News->where('cat_id', $cat_id);
            }
            $perPage = max(1, min((int) $request->query('per_page', 10), self::MAX_PER_PAGE));
            $rows = $model_News->with("author")->with('translation')->with("category")->paginate($perPage);
            $total = $rows->total();

            return [
                'total' => $total,
                'total_pages' => $rows->lastPage(),
                'data' => $rows->map(function ($row) {
                    return $row->dataForApi();
                }),
            ];
        });

        return $this->sendSuccess(
            $payload
        );
    }
    public function category(Request $request){
        $params = $request->query();
        $params['v'] = (int) Cache::get('api_cache:news:version', 1);
        ksort($params);
        $cacheKey = sprintf('api:news:category:%s:%s', app_get_locale(), md5(json_encode($params)));
        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
            $model_News = NewsCategory::query()->select("core_news_category.*");
            $model_News->where("core_news_category.status", "publish");
            if (!empty($search = $request->query("s"))) {
                $model_News->where(function ($query) use ($search) {
                    $query->where('core_news_category.name', 'LIKE', '%' . $search . '%');
                });

                if (setting_item('site_enable_multi_lang') && setting_item('site_locale') != app_get_locale()) {
                    $model_News->leftJoin('core_news_category_translations', function ($join) use ($search) {
                        $join->on('core_news_category.id', '=', 'core_news_category_translations.origin_id');
                    });
                    $model_News->orWhere(function ($query) use ($search) {
                        $query->where('core_news_category_translations.title', 'LIKE', '%' . $search . '%');
                    });
                }
            }
            $rows = $model_News->with('translation')->get()->toTree();

            return [
                'data' => $rows->map(function ($row) {
                    return $row->dataForApi();
                }),
            ];
        });

        return $this->sendSuccess(
            $payload
        );
    }

    public function detail($id = '')
    {
        $cacheKey = sprintf(
            'api:news:detail:%s:%s:%s',
            app_get_locale(),
            (string) Cache::get('api_cache:news:version', 1),
            (string) $id
        );
        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($id) {
            $query = News::query()->where('status', 'publish');
            if (is_numeric($id)) {
                $row = (clone $query)->where('id', (int) $id)->first();
            } else {
                $row = (clone $query)->where('slug', $id)->first();
            }

            if (empty($row)) {
                return null;
            }

            return [
                'status' => 1,
                'data' => $row->dataForApi(true),
            ];
        });

        if (empty($payload)) {
            return response()->json([
                'status' => 0,
                'message' => __("News not found"),
            ], 404);
        }

        return response()->json($payload);
    }
}
