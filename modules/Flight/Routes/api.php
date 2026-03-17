<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('flight.flight_route_prefix', 'flight')], function () {
    Route::get('/', 'FlightController@index')->name('api.flight.search');
    Route::post('/getData/{id}', 'FlightController@getData')->name('api.flight.getData');
});

