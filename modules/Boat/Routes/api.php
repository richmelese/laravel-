<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('boat.boat_route_prefix', 'boat')], function () {
    Route::get('/', 'BoatController@index')->name('api.boat.search');
    Route::get('/{slug}', 'BoatController@detail')->name('api.boat.detail');
});
