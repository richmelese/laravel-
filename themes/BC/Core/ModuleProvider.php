<?php

namespace Themes\BC\Core;

use Modules\ModuleServiceProvider;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot(){

        add_filter(\Modules\Core\Hook::CORE_SETTING_CONFIG,[$this,'alterSettings']);

        add_action(\Modules\Core\Hook::STYLE_SETTING_AFTER_GENERAL_STYLE, [$this, 'showCustomFieldsAfterLogo']);

        $this->mergeConfigFrom(__DIR__.'/Configs/bc.php', 'bc');

    }
    public function alterSettings($settings){
        if (!empty($settings['style'])) {
            $settings['style']['keys'][] = 'enable_header_sticky';
        }
        return $settings;
    }

    public function showCustomFieldsAfterLogo(){
        echo view('Core::admin.settings.header');
    }
}
