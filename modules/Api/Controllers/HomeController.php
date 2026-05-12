<?php
namespace Modules\Api\Controllers;
use Modules\Template\Models\Template;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $version = (int) Cache::get('api_cache:home:version', 1);
        $cacheKey = sprintf(
            'api:home-page:%s:%s:%s',
            app_get_locale(),
            (string) setting_item('api_app_layout'),
            $version
        );

        $res = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $res = [
                'id' => auth()->id(),
            ];

            $template = app(Template::class)->find(setting_item('api_app_layout'));
            if (!empty($template)) {
                $translate = $template->translate();
                $res = $translate->getProcessedContentAPI();
            }

            return $res;
        });

        return $this->sendSuccess(
            [
                "data"=>$res
            ]
        );
    }
}