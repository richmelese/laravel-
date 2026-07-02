<?php

use Illuminate\Support\Facades\Route;

Route::group([], function () {
    Route::get('/', 'MedicalBookingController@index')->name('medical_booking.admin.index');
    Route::post('/bulkEdit', 'MedicalBookingController@bulkEdit')->name('medical_booking.admin.bulkEdit');
});
