<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'hospital'], function () {

    // ── Hospital CRUD ─────────────────────────────────────────────────────────
    Route::get('/',                'HospitalController@index')->name('api_admin.hospital.index');
    Route::get('/{id}',            'HospitalController@show')->name('api_admin.hospital.show')->where('id', '[0-9]+');
    Route::post('/store/{id}',     'HospitalController@store')->name('api_admin.hospital.store')->where('id', '[0-9]+');
    Route::post('/store/0',        'HospitalController@store')->name('api_admin.hospital.create');
    Route::delete('/{id}',         'HospitalController@destroy')->name('api_admin.hospital.destroy')->where('id', '[0-9]+');
    Route::post('/bulk-action',    'HospitalController@bulkAction')->name('api_admin.hospital.bulk_action');

    // ── Staff Management ──────────────────────────────────────────────────────
    Route::get('/{id}/staff',              'HospitalController@staffIndex')->name('api_admin.hospital.staff.index')->where('id', '[0-9]+');
    Route::post('/{id}/staff',             'HospitalController@staffStore')->name('api_admin.hospital.staff.store')->where('id', '[0-9]+');
    Route::delete('/{id}/staff/{user_id}', 'HospitalController@staffDestroy')->name('api_admin.hospital.staff.destroy')->where('id', '[0-9]+')->where('user_id', '[0-9]+');

    // ── Medical Bookings (hospital-scoped) ────────────────────────────────────
    Route::get('/{id}/bookings',                          'HospitalController@bookings')->name('api_admin.hospital.bookings')->where('id', '[0-9]+');
    Route::get('/{id}/bookings/{booking_id}',             'HospitalController@bookingShow')->name('api_admin.hospital.booking.show')->where('id', '[0-9]+')->where('booking_id', '[0-9]+');
    Route::put('/{id}/bookings/{booking_id}',             'HospitalController@bookingUpdate')->name('api_admin.hospital.booking.update')->where('id', '[0-9]+')->where('booking_id', '[0-9]+');
    Route::patch('/{id}/bookings/{booking_id}',           'HospitalController@bookingUpdate')->name('api_admin.hospital.booking.patch')->where('id', '[0-9]+')->where('booking_id', '[0-9]+');
    Route::post('/{id}/bookings/{booking_id}/assign',     'HospitalController@bookingAssign')->name('api_admin.hospital.booking.assign')->where('id', '[0-9]+')->where('booking_id', '[0-9]+');
});
