<?php
use Illuminate\Support\Facades\Route;
use Modules\Booking\Admin\EditBookingPage;

Route::get('/','\\Modules\\Booking\\Admin\\BookingController@index')->name('booking.admin.index');
Route::get('/email_preview/{id}','\\Modules\\Booking\\Admin\\BookingController@email_preview')->name('booking.admin.email_preview');
Route::post('/bulkEdit','\\Modules\\Booking\\Admin\\BookingController@bulkEdit')->name('booking.admin.bulkEdit');

Route::get('/edit/{id?}', EditBookingPage::class)->name('booking.admin.edit');