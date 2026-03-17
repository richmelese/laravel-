<?php

namespace Themes\BC\Tour;

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Themes\BC\Tour\Pages\Components\SearchForm;
use Themes\BC\Tour\Pages\Components\Filter;
use Themes\BC\Tour\Pages\Components\FilterForMap;

class ModuleProvider extends \Modules\ModuleServiceProvider
{
    public function boot()
    {
        $this->registerRoutes();


        Livewire::component('tour::search-form', SearchForm::class);
        Livewire::component('tour::filter', Filter::class);
        Livewire::component('tour::filter-for-map', FilterForMap::class);
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

