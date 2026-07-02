<?php

namespace Modules\MedicalBooking;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouterServiceProvider extends ServiceProvider
{
    protected $moduleNamespace = 'Modules\MedicalBooking\Controllers';

    protected $adminModuleNamespace = 'Modules\MedicalBooking\Admin';

    public function boot()
    {
        parent::boot();
    }

    public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapLanguageRoutes();
        $this->mapAdminRoutes();
        $this->mapAdminApiRoutes();
    }

    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/Routes/web.php');
    }

    protected function mapLanguageRoutes()
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->prefix(app()->getLocale())
            ->group(__DIR__ . '/Routes/language.php');
    }

    protected function mapAdminRoutes()
    {
        Route::middleware(['web', 'dashboard'])
            ->namespace($this->adminModuleNamespace)
            ->prefix(config('admin.admin_route_prefix') . '/module/medical-booking')
            ->group(__DIR__ . '/Routes/admin.php');
    }

    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/Routes/api.php');
    }

    protected function mapAdminApiRoutes()
    {
        Route::prefix('api-admin')
            ->middleware(['api', 'auth:sanctum'])
            ->namespace($this->adminModuleNamespace)
            ->group(__DIR__ . '/Routes/api-admin.php');
    }
}
