<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('car.car_route_prefix', 'car')], function () {
    Route::get('/', 'CarController@index')->name('api.car.search');
    Route::get('/filters', 'CarController@filters')->name('api.car.filters');
    Route::get('/{slug}', 'CarController@detail')->name('api.car.detail');
});

