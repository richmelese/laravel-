<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'booking'], function () {
    Route::get('/', 'BookingController@index')->name('api_admin.booking.index');
    Route::get('/{id}', 'BookingController@show')->name('api_admin.booking.show')->where('id', '[0-9]+');
    Route::put('/{id}', 'BookingController@update')->name('api_admin.booking.update')->where('id', '[0-9]+');
    Route::patch('/{id}', 'BookingController@update')->name('api_admin.booking.patch')->where('id', '[0-9]+');
    Route::post('/bulkEdit', 'BookingController@bulkEdit')->name('api_admin.booking.bulkEdit');
});
