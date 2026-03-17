<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('tour.tour_route_prefix', 'tour')], function () {
    Route::get('/', 'TourController@index')->name('api.tour.search');
    Route::get('/{slug}', 'TourController@detail')->name('api.tour.detail');
});
