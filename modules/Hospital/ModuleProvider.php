<?php

namespace Modules\Hospital;

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
            'hospital' => [
                'position'   => 58,
                'url'        => '/admin/module/hospital',
                'title'      => __('Hospitals'),
                'icon'       => 'ion ion-md-medkit',
                'permission' => 'hospital_view',
                'group'      => 'system',
            ],
        ];
    }
}
