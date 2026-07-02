<?php

namespace Modules\Announcement;

use Modules\ModuleServiceProvider;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadViewsFrom(__DIR__ . '/Views', 'Announcement');
    }

    public function register()
    {
        $this->app->register(RouterServiceProvider::class);
    }

    public static function getAdminMenu()
    {
        return [
            'announcement' => [
                'position'   => 55,
                'url'        => route('announcement.admin.index'),
                'title'      => __('Announcements'),
                'icon'       => 'ion ion-ios-megaphone',
                'permission' => 'announcement_view',
                'group'      => 'system',
            ],
        ];
    }
}
