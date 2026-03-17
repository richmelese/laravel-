<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('space.space_route_prefix', 'space')], function () {
    Route::get('/', 'SpaceController@index')->name('api.space.search');
    Route::get('/{slug}', 'SpaceController@detail')->name('api.space.detail');
});

