<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('tour.tour_route_prefix', 'tour')], function () {
    Route::get('/', 'TourController@index')->name('api.tour.search');
    Route::get('/filters', 'TourController@filters')->name('api.tour.filters');
    Route::get('/{slug}', 'TourController@detail')->name('api.tour.detail');
});
