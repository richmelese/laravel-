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
    /** JSON resend verification email; requires Authorization: Bearer token (same as /api/auth/me). */
    Route::post('email/verification-notification', 'AuthController@resendEmailVerification')
        ->middleware(['throttle:6,1'])
        ->name('api.auth.email.verification-notification');
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
    /*
     * Vendor “my listings” (Sanctum personal access token).
     * Use Authorization: Bearer <token> — same as GET /api/auth/me.
     * Do not use /user/hotel web URLs for SPA+Bearer; those expect session cookies.
     */
    Route::get('vendor/hotels', 'VendorListingController@hotels')->name('api.user.vendor.hotels');
    Route::get('vendor/tours', 'VendorListingController@tours')->name('api.user.vendor.tours');
    Route::get('vendor/spaces', 'VendorListingController@spaces')->name('api.user.vendor.spaces');
    Route::get('vendor/cars', 'VendorListingController@cars')->name('api.user.vendor.cars');
    Route::get('vendor/boats', 'VendorListingController@boats')->name('api.user.vendor.boats');
    Route::get('vendor/events', 'VendorListingController@events')->name('api.user.vendor.events');
    Route::get('vendor/flights', 'VendorListingController@flights')->name('api.user.vendor.flights');
    Route::get('vendor/properties', 'VendorListingController@properties')->name('api.user.vendor.properties');
    Route::get('vendor/quick-manage', 'VendorListingController@quickManage')->name('api.user.vendor.quick_manage');

    Route::get('vendor/{resource}/recovery', 'VendorListingController@vendorRecoveryListing')
        ->where('resource', 'hotels|tours|spaces|cars|boats|events|flights|properties')
        ->name('api.user.vendor.recovery');

    // Short aliases (same handlers as vendor/* above) — avoids 404 on /api/user/hotel vs /api/user/vendor/hotels
    Route::get('hotel', 'VendorListingController@hotels')->name('api.user.hotel');
    Route::get('hotels', 'VendorListingController@hotels')->name('api.user.hotels');
    Route::get('tour', 'VendorListingController@tours')->name('api.user.tour');
    Route::get('tours', 'VendorListingController@tours')->name('api.user.tours');
    Route::get('space', 'VendorListingController@spaces')->name('api.user.space');
    Route::get('spaces', 'VendorListingController@spaces')->name('api.user.spaces');
    Route::get('car', 'VendorListingController@cars')->name('api.user.car');
    Route::get('cars', 'VendorListingController@cars')->name('api.user.cars');
    Route::get('boat', 'VendorListingController@boats')->name('api.user.boat');
    Route::get('boats', 'VendorListingController@boats')->name('api.user.boats');
    Route::get('event', 'VendorListingController@events')->name('api.user.event');
    Route::get('events', 'VendorListingController@events')->name('api.user.events');
    Route::get('flight', 'VendorListingController@flights')->name('api.user.flight');
    Route::get('flights', 'VendorListingController@flights')->name('api.user.flights');
    Route::get('property', 'VendorListingController@properties')->name('api.user.property');
    Route::get('properties', 'VendorListingController@properties')->name('api.user.properties');

    Route::get('{listing}/recovery', 'VendorListingController@recoveryListing')
        ->where('listing', 'hotel|hotels|tour|tours|space|spaces|car|cars|boat|boats|event|events|flight|flights|property|properties')
        ->name('api.user.listing.recovery');

    Route::get('booking-history', 'UserController@getBookingHistory')->name("api.user.booking_history");
    Route::get('dashboard', 'UserController@vendorDashboard')->name('api.user.dashboard');

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
