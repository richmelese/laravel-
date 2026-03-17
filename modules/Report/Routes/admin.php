<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 7/1/2019
 * Time: 10:02 AM
 */
use Illuminate\Support\Facades\Route;
Route::group(['prefix' => 'booking'],function (){
    Route::get('/','\\Modules\\Booking\\Admin\\BookingController@index')->name('booking.admin.booking');
    Route::get('/email_preview/{id}','\\Modules\\Booking\\Admin\\BookingController@email_preview')->name('booking.admin.booking.email_preview');
    Route::post('/bulkEdit','\\Modules\\Booking\\Admin\\BookingController@bulkEdit')->name('booking.admin.booking.bulkEdit');
});
Route::get('/enquiry','EnquiryController@index')->name('report.admin.enquiry.index');

Route::post('/enquiry/bulkEdit','EnquiryController@bulkEdit')->name('report.admin.enquiry.bulkEdit');

Route::get('/enquiry/{enquiry}/reply','EnquiryController@reply')->name('report.admin.enquiry.reply');
Route::post('/enquiry/{enquiry}/reply/store','EnquiryController@replyStore')->name('report.admin.enquiry.replyStore');


Route::get('/statistic','StatisticController@index')->name('report.admin.statistic.index');
Route::match(['get','post'],'/statistic/reloadChart','StatisticController@reloadChart')->name('report.admin.statistic.reloadChart');
