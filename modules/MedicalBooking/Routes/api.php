<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'medical-booking'], function () {

    // Form dropdown options
    Route::get('/meta', 'MedicalBookingController@meta')
        ->name('api.medical_booking.meta');

    // Available payment gateways list
    Route::get('/gateways', 'MedicalBookingController@gateways')
        ->name('api.medical_booking.gateways');

    // Chapa return URL (user lands here after completing payment on Chapa hosted page)
    Route::get('/payment/confirm/chapa', 'MedicalBookingController@confirmChapa')
        ->name('api.medical_booking.payment.confirm.chapa');

    // Chapa webhook — Chapa POSTs here automatically (no auth, no CSRF)
    Route::post('/payment/webhook/chapa', 'MedicalBookingController@webhookChapa')
        ->name('api.medical_booking.payment.webhook.chapa')
        ->withoutMiddleware(['web', 'csrf']);

    // Cancel redirect (user clicks Cancel on Chapa hosted page)
    Route::get('/payment/cancel/{gateway}', 'MedicalBookingController@cancelPayment')
        ->name('api.medical_booking.payment.cancel');

    // Submit a new medical booking (public, no auth)
    Route::post('/', 'MedicalBookingController@store')
        ->name('api.medical_booking.store');

    // Check payment status — verified by ?email= (no auth)
    Route::get('/{id}/payment-status', 'MedicalBookingController@paymentStatus')
        ->name('api.medical_booking.payment_status')
        ->where('id', '[0-9]+');

    // Initiate payment — verified by email in body (no auth)
    Route::post('/{id}/pay', 'MedicalBookingController@initiatePayment')
        ->name('api.medical_booking.pay')
        ->where('id', '[0-9]+');
});
