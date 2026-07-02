<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'medical-booking'], function () {

    // List all bookings (filterable + paginated)
    Route::get('/', 'MedicalBookingController@index')
        ->name('api_admin.medical_booking.index');

    // Bulk status update / delete
    Route::post('/bulk-action', 'MedicalBookingController@bulkEdit')
        ->name('api_admin.medical_booking.bulkEdit');

    // Get single booking
    Route::get('/{id}', 'MedicalBookingController@show')
        ->name('api_admin.medical_booking.show')
        ->where('id', '[0-9]+');

    // Update booking (status, admin note, priority, fee, payment fields)
    Route::put('/{id}', 'MedicalBookingController@update')
        ->name('api_admin.medical_booking.update')
        ->where('id', '[0-9]+');
    Route::patch('/{id}', 'MedicalBookingController@update')
        ->name('api_admin.medical_booking.patch')
        ->where('id', '[0-9]+');

    // Set fee amount (admin sets price before customer pays)
    Route::post('/{id}/set-fee', 'MedicalBookingController@setFee')
        ->name('api_admin.medical_booking.set_fee')
        ->where('id', '[0-9]+');

    // Manually mark as paid
    Route::post('/{id}/mark-paid', 'MedicalBookingController@markPaid')
        ->name('api_admin.medical_booking.mark_paid')
        ->where('id', '[0-9]+');

    // Soft delete
    Route::delete('/{id}', 'MedicalBookingController@destroy')
        ->name('api_admin.medical_booking.destroy')
        ->where('id', '[0-9]+');
});
