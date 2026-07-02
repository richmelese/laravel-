<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => env('EVENT_ROUTE_PREFIX', 'event')], function () {
    Route::get('/', 'EventController@index')->name('api.event.search');
    Route::get('/filters', 'EventController@filters')->name('api.event.filters');
    Route::get('/{slug}', 'EventController@detail')->name('api.event.detail');
});

