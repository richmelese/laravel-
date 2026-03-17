<?php 
namespace Themes\BC\Styles\Solotour;

use Illuminate\Support\ServiceProvider;

class StyleProvider extends ServiceProvider
{
    public function boot()
    {
      
    }

    public static function getSettingPages()
    {
        $configs = [
            'tour' => [
                'id'   => 'solotour_settings',
                'title' => __("Solo Tour Settings"),
                'position'=>100,
                'view'=>"Core::admin.settings.general",
                "keys"=>[
                    'solotour_topbar_text',
                    'solotour_footer_text_left',
                    'solotour_footer_text_right',
                ],
                'html_keys'=>[
                    
                ]
            ]
        ];
        return $configs;
    }

}
