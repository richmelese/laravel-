<?php

namespace Themes\BC\Boat;

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Themes\BC\Boat\Pages\Components\SearchForm;
use Themes\BC\Boat\Pages\Components\Filter;
use Themes\BC\Boat\Pages\Components\FilterForMap;

class ModuleProvider extends \Modules\ModuleServiceProvider
{
    public function boot()
    {
        $this->registerRoutes();

        Livewire::component('boat::search-form', SearchForm::class);
        Livewire::component('boat::filter', Filter::class);
        Livewire::component('boat::filter-for-map', FilterForMap::class);
    }

    public function registerRoutes()
    {
        Route::middleware('web')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
            });
        if (is_enable_language_route()) {
            Route::middleware('web')
                ->prefix(app()->getLocale())
                ->group(function () {
                    $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
                });
        }
    }
}
