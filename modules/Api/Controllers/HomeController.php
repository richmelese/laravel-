<?php
namespace Modules\Api\Controllers;
use Modules\Template\Models\Template;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $res = [
            'id'=>auth()->id()
        ];
        $template = app(Template::class)->find(setting_item('api_app_layout'));
        if(!empty($template)){
            $translate = $template->translate();
            $res = $translate->getProcessedContentAPI();
        }
        return $this->sendSuccess(
            [
                "data"=>$res
            ]
        );
    }
}