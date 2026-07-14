<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('boat.boat_route_prefix', 'boat')], function () {
    Route::get('/', 'BoatController@index')->name('api.boat.search');
    Route::get('/{slug}', 'BoatController@detail')->name('api.boat.detail');
});

Route::group(['prefix' => 'buses'], function () {
    Route::get('/', 'BusController@index')->name('api.buses.index');
    Route::get('/{id}/seat-availability', 'BusBookingController@busSeatAvailability')->name('api.buses.seat_availability')->where('id', '[0-9]+');
    Route::get('/{id}', 'BusController@show')->name('api.buses.show')->where('id', '[0-9]+');
});

Route::group(['prefix' => 'bus'], function () {
    Route::get('/', 'BusController@index')->name('api.bus.index');
    Route::get('/{id}/seat-availability', 'BusBookingController@busSeatAvailability')->name('api.bus.seat_availability')->where('id', '[0-9]+');
    Route::get('/{id}', 'BusController@show')->name('api.bus.show')->where('id', '[0-9]+');
});

Route::group(['prefix' => 'bus-routes'], function () {
    Route::get('/', 'BusBookingController@routes')->name('api.bus_routes.index');
});

Route::group(['prefix' => 'bus-schedules'], function () {
    Route::get('/search', 'BusBookingController@searchSchedules')->name('api.bus_schedules.search');
    Route::get('/{id}/seats', 'BusBookingController@scheduleSeats')->name('api.bus_schedules.seats')->where('id', '[0-9]+');
});

Route::group(['prefix' => 'bus-schedules', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/{id}/select-seat', 'BusBookingController@selectSeat')->name('api.bus_schedules.select_seat')->where('id', '[0-9]+');
});

Route::group(['prefix' => 'bus-bookings', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/', 'BusBookingController@createBooking')->name('api.bus_bookings.create');
    Route::get('/{id}', 'BusBookingController@bookingDetail')->name('api.bus_bookings.show')->where('id', '[0-9]+');
    Route::post('/{id}/payments', 'BusBookingController@payBooking')->name('api.bus_bookings.pay')->where('id', '[0-9]+');
});

Route::group(['prefix' => 'bus-bookings'], function () {
    // Ticket + QR + verification are all public and gated by ticket_code (an unguessable
    // random token), not auth — the customer lands on the Chapa confirm redirect with no
    // Sanctum token, so these must be reachable without one.
    Route::get('/ticket/{ticket_code}', 'BusBookingController@ticketByCode')->name('api.bus_bookings.ticket');
    Route::get('/ticket/{ticket_code}/qr-code', 'BusBookingController@qrCodeByCode')->name('api.bus_bookings.qr_code');
    Route::get('/verify/{ticket_code}', 'BusBookingController@verifyTicket')->name('api.bus_bookings.verify');

    // Chapa return URL — the customer's browser lands here after completing checkout.
    Route::get('/payment/confirm/chapa', 'BusBookingController@confirmChapaPayment')->name('api.bus_bookings.payment.confirm.chapa');

    // Chapa webhook — Chapa's server POSTs here directly (no auth, no CSRF).
    Route::post('/payment/webhook/chapa', 'BusBookingController@webhookChapaPayment')->name('api.bus_bookings.payment.webhook.chapa')->withoutMiddleware(['web', 'csrf']);

    // Cancel redirect — the customer clicks "Cancel" on the Chapa hosted page.
    Route::get('/payment/cancel/chapa', 'BusBookingController@cancelChapaPayment')->name('api.bus_bookings.payment.cancel.chapa');
});
