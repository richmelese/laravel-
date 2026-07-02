<?php

namespace Modules\Emergency;

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
            'emergency' => [
                'position'   => 57,
                'url'        => route('emergency.admin.index'),
                'title'      => __('Emergency Info'),
                'icon'       => 'ion ion-ios-alert',
                'permission' => 'emergency_view',
                'group'      => 'system',
            ],
        ];
    }
}
