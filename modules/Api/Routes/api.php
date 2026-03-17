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
/* Config */
Route::get('configs','BookingController@getConfigs')->name('api.get_configs');
Route::get('configs/countries','CountryController@index')->name('api.country.index');
/* Service */
Route::get('services','SearchController@searchServices')->name('api.service-search');
Route::get('{type}/search','SearchController@search')->name('api.search2');
Route::get('{type}/detail/{id}','SearchController@detail')->name('api.detail');
Route::get('{type}/availability/{id}','SearchController@checkAvailability')->name('api.service.check_availability');
Route::get('boat/availability-booking/{id}','SearchController@checkBoatAvailability')->name('api.service.checkBoatAvailability');

Route::get('{type}/filters','SearchController@getFilters')->name('api.service.filter');
Route::get('{type}/form-search','SearchController@getFormSearch')->name('api.service.form');

Route::group(['middleware' => 'api'],function(){
    Route::post('{type}/write-review/{id}','ReviewController@writeReview')->name('api.service.write_review');
});


/* Layout HomePage */
Route::get('home-page','HomeController@index')->middleware('api')->name('api.get_home_layout');

Route::post('forgot-password', 'AuthController@forgotPassword');
Route::post('reset-password', 'AuthController@resetPassword');

/* Register - Login */
Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('login', 'AuthController@login')->middleware(['throttle:login']);
    Route::post('register', 'AuthController@register');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refreshToken');
    Route::get('me', 'AuthController@me');
    Route::post('me', 'AuthController@updateUser');
    Route::post('change-password', 'AuthController@changePassword');

    Route::post('forgot-password', 'AuthController@forgotPassword')->name('api.forgot-password');
    Route::post('reset-password', 'AuthController@resetPassword')->name('api.reset-password');
});



/* User */
Route::group(['prefix' => 'user', 'middleware' => ['api']], function ($router) {
    Route::get('booking-history', 'UserController@getBookingHistory')->name("api.user.booking_history");

    // Wishlist
    Route::post('/wishlist/{object_model}/{object_id}', 'UserController@addWishList')->name("api.user.wishList.add");
    Route::delete('/wishlist/{object_model}/{object_id}', 'UserController@deleteWishList')->name("api.user.wishList.delete");
    Route::get('/wishlist','UserController@indexWishlist')->name("api.user.wishList.index");
    Route::delete('/wishlist', 'UserController@deleteWishListAll')->name("api.user.wishList.delete_all");

    Route::post('/permanently_delete','UserController@permanentlyDelete')->name("user.permanently.delete");

    // My Tickets
    Route::group(['prefix' => '/ticket'], function ($router) {
        Route::get('/','MyTicketController@index')->name("api.user.my-ticket.index");
        Route::get('/qr-image/{ticket_id}','MyTicketController@qrImage')->name("api.user.my-ticket.qr-image");
    });

    // Manage Tickets
    Route::group(['prefix' => '/booking/ticket'], function ($router) {
        Route::get('/','TicketController@index')->name("api.user.tickets.index");
        Route::post('/scan/{booking_id}/{ticket_id}','TicketController@scan')->name("api.user.tickets.scan");
    });

    // QR Generator
    Route::get('/qr-code','QRController@generate')->name("api.user.qr-code");

});

/* Location */
Route::get('locations','LocationController@search')->name('api.location.search');
Route::get('location/{id}','LocationController@detail')->name('api.location.detail');

// Booking
Route::group(['prefix'=>config('booking.booking_route_prefix')],function(){
    Route::post('/addToCart','BookingController@addToCart')->name("api.booking.add_to_cart");
    Route::post('/addEnquiry','BookingController@addEnquiry')->name("api.booking.add_enquiry");
    Route::post('/doCheckout','BookingController@doCheckout')->name('api.booking.doCheckout');
    Route::get('/confirm/{gateway}','BookingController@confirmPayment');
    Route::get('/cancel/{gateway}','BookingController@cancelPayment');
    Route::get('/{code}','BookingController@detail');
    Route::get('/{code}/thankyou','BookingController@thankyou')->name('booking.thankyou');
    Route::get('/{code}/checkout','BookingController@checkout');
    Route::get('/{code}/check-status','BookingController@checkStatusCheckout');
});

// Gateways
Route::get('/gateways','BookingController@getGatewaysForApi');

// News
Route::get('news','NewsController@search')->name('api.news.search');
Route::get('news/category','NewsController@category')->name('api.news.category');
Route::get('news/{id}','NewsController@detail')->name('api.news.detail');

/* Media */
Route::group(['prefix'=>'media','middleware' => 'auth:sanctum'],function(){
    Route::post('/store','MediaController@store')->name("api.media.store");
});
