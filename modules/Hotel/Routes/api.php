<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => env('HOTEL_ROUTE_PREFIX', 'hotel')], function () {
    Route::get('/', 'HotelController@index')->name('api.hotel.search');
    Route::get('/check-availability', 'HotelController@checkAvailability')->name('api.hotel.check_availability');
    Route::get('/{slug}', 'HotelController@detail')->name('api.hotel.detail');
});
