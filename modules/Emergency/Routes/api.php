<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'emergency'], function () {
    Route::get('/', 'EmergencyController@index')->name('api.emergency.index');
    Route::get('/{type}', 'EmergencyController@byType')->name('api.emergency.by_type')
        ->where('type', 'hotline|number|medical|health|travel|contacts');
});

// ── Alert / Help Button ───────────────────────────────────────────────────────
Route::group(['prefix' => 'alert'], function () {
    // Alert type options
    Route::get('/meta', 'AlertController@meta')->name('api.alert.meta');

    // Check whether the authenticated user has any bookings (true/false + count)
    Route::get('/check-booked', 'AlertController@checkBooked')
        ->name('api.alert.check_booked')
        ->middleware('auth:sanctum');

    // All services the authenticated user has booked (car, hotel, tour, etc.)
    Route::get('/my-bookings', 'AlertController@myBookings')
        ->name('api.alert.my_bookings')
        ->middleware('auth:sanctum');

    // Send alert (logged-in, guest+booking_code, or pure guest with name+email)
    Route::post('/', 'AlertController@send')
        ->name('api.alert.send')
        ->middleware('throttle:10,1');

    // My alert history (requires auth)
    Route::get('/', 'AlertController@myAlerts')
        ->name('api.alert.my')
        ->middleware('auth:sanctum');
});
