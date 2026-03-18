<?php

use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Booking (JSON-capable; controller returns JSON when called via /api)
Route::group(['prefix' => config('booking.booking_route_prefix', 'booking')], function () {
    Route::post('/addToCart', 'BookingController@addToCart')->name('api.booking.addToCart');
    Route::post('/addEnquiry', 'BookingController@addEnquiry')->name('api.booking.addEnquiry');
    Route::post('/doCheckout', 'BookingController@doCheckout')->name('api.booking.doCheckout');

    Route::get('/confirm/{gateway}', 'BookingController@confirmPayment')->name('api.booking.confirmPayment');
    Route::get('/cancel/{gateway}', 'BookingController@cancelPayment')->name('api.booking.cancelPayment');
    Route::match(['get','post'], '/callback/{gateway}', 'BookingController@callbackPayment')->name('api.booking.callbackPayment');

    Route::get('/{code}', 'BookingController@detail')->name('api.booking.detail');
    Route::get('/{code}/checkout', 'BookingController@checkout')->name('api.booking.checkout');
    Route::get('/{code}/check-status', 'BookingController@checkStatusCheckout')->name('api.booking.checkStatus');

    // Vendor/admin helpers (secured)
    Route::post('/setPaidAmount', 'BookingController@setPaidAmount')->name('api.booking.setPaidAmount')->middleware('auth:sanctum');
    Route::post('/storeNoteBooking', 'BookingController@storeNoteBooking')->name('api.booking.storeNoteBooking')->middleware('auth:sanctum');
});

