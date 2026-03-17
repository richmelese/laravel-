<?php 

namespace Themes\BC;

use Illuminate\Support\ServiceProvider;

class StyleProvider extends ServiceProvider
{
    public function register()
    {
        if(!is_installed()){
            return;
        }

        if(defined('BC_ACTIVE_STYLE')){
            $providerClass = "\\Themes\\BC\\Styles\\".ucfirst(BC_ACTIVE_STYLE)."\\StyleProvider";
            if(class_exists($providerClass)){
                $this->app->register($providerClass);
            }
        }
        
    }
}
