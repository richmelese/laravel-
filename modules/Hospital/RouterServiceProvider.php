<?php

namespace Modules\Hospital;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouterServiceProvider extends ServiceProvider
{
    protected $moduleNamespace      = 'Modules\Hospital\Controllers';
    protected $adminModuleNamespace = 'Modules\Hospital\Admin';

    public function boot() { parent::boot(); }

    public function map()
    {
        $this->mapApiRoutes();
        $this->mapAdminApiRoutes();
        $this->mapWebRoutes();
        $this->mapAdminRoutes();
    }

    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/Routes/web.php');
    }

    protected function mapAdminRoutes()
    {
        Route::middleware(['web', 'dashboard'])
            ->namespace($this->adminModuleNamespace)
            ->prefix(config('admin.admin_route_prefix') . '/module/hospital')
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
