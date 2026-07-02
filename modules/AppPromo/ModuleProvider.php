<?php

namespace Modules\AppPromo;

use Modules\ModuleServiceProvider;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
    }

    public function register()
    {
        $this->app->register(RouterServiceProvider::class);
    }

    public static function getAdminMenu()
    {
        return [
            'app_promo' => [
                'position'   => 57,
                'url'        => route('api_admin.app_promo.settings'),
                'title'      => __('App Promotion'),
                'icon'       => 'ion ion-ios-phone-portrait',
                'permission' => 'app_promo_manage',
                'group'      => 'system',
            ],
        ];
    }
}
