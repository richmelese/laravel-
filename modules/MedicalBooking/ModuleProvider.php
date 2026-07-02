<?php

namespace Modules\MedicalBooking;

use Modules\ModuleServiceProvider;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadViewsFrom(__DIR__ . '/Views', 'MedicalBooking');
    }

    public function register()
    {
        $this->app->register(RouterServiceProvider::class);
    }

    public static function getAdminMenu()
    {
        return [
            'medical_booking' => [
                'position'   => 56,
                'url'        => route('medical_booking.admin.index'),
                'title'      => __('Medical Bookings'),
                'icon'       => 'ion ion-ios-medkit',
                'permission' => 'medical_booking_view',
                'group'      => 'system',
            ],
        ];
    }
}
