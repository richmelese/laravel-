<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('property.property_route_prefix', 'property')], function () {
    Route::get('/', 'PropertyController@index')->name('api.property.search');
    Route::get('/{slug}', 'PropertyController@detail')->name('api.property.detail');
});
