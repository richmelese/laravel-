<?php

namespace Themes\BC\Car;

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Themes\BC\Car\Pages\Components\SearchForm;
use Themes\BC\Car\Pages\Components\Filter;
use Themes\BC\Car\Pages\Components\FilterForMap;

class ModuleProvider extends \Modules\ModuleServiceProvider
{
    public function boot()
    {
        $this->registerRoutes();


        Livewire::component('car::search-form', SearchForm::class);
        Livewire::component('car::filter', Filter::class);
        Livewire::component('car::filter-for-map', FilterForMap::class);
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
