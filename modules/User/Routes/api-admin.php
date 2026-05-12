<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user'], function () {
    Route::get('/', 'UserController@index')->name('api_admin.user.index');
    Route::post('/store/{id}', 'UserController@store')->name('api_admin.user.store')->where('id', '[0-9]+');
    Route::get('/{id}', 'UserController@show')->name('api_admin.user.show')->where('id', '[0-9]+');
    Route::put('/{id}', 'UserController@update')->name('api_admin.user.update')->where('id', '[0-9]+');
    Route::patch('/{id}', 'UserController@update')->name('api_admin.user.patch')->where('id', '[0-9]+');
    Route::post('/{id}/verify-email', 'UserController@verifyEmail')->name('api_admin.user.verify_email')->where('id', '[0-9]+');
});

Route::group(['prefix' => 'role'], function () {
    Route::get('/', 'RoleController@index')->name('api_admin.role.index');
    Route::get('/{id}', 'RoleController@show')->name('api_admin.role.show')->where('id', '[0-9]+');
    Route::put('/{id}', 'RoleController@update')->name('api_admin.role.update')->where('id', '[0-9]+');
    Route::patch('/{id}', 'RoleController@update')->name('api_admin.role.patch')->where('id', '[0-9]+');
});

Route::group(['prefix' => 'verification'], function () {
    Route::get('/', 'VerificationController@index')->name('api_admin.verification.index');
    Route::post('/bulkEdit', 'VerificationController@bulkEdit')->name('api_admin.verification.bulkEdit');
    Route::get('/{id}', 'VerificationController@show')->name('api_admin.verification.show')->where('id', '[0-9]+');
    Route::put('/{id}', 'VerificationController@store')->name('api_admin.verification.update')->where('id', '[0-9]+');
    Route::patch('/{id}', 'VerificationController@store')->name('api_admin.verification.patch')->where('id', '[0-9]+');
});

Route::group(['prefix' => 'subscriber'], function () {
    Route::get('/', 'SubscriberController@index')->name('api_admin.subscriber.index');
    Route::post('/bulkEdit', 'SubscriberController@bulkEdit')->name('api_admin.subscriber.bulkEdit');
    Route::post('/', 'SubscriberController@store')->name('api_admin.subscriber.store');
    Route::get('/{id}', 'SubscriberController@show')->name('api_admin.subscriber.show')->where('id', '[0-9]+');
    Route::put('/{id}', 'SubscriberController@update')->name('api_admin.subscriber.update')->where('id', '[0-9]+');
    Route::patch('/{id}', 'SubscriberController@update')->name('api_admin.subscriber.patch')->where('id', '[0-9]+');
});

Route::group(['prefix' => 'vendor-request'], function () {
    Route::get('/', 'UserController@vendorRequestIndex')->name('api_admin.vendor_request.index');
    Route::put('/{id}', 'UserController@vendorRequestUpdate')->name('api_admin.vendor_request.update')->where('id', '[0-9]+');
    Route::patch('/{id}', 'UserController@vendorRequestUpdate')->name('api_admin.vendor_request.patch')->where('id', '[0-9]+');
    Route::post('/bulkEdit', 'UserController@vendorRequestBulkEdit')->name('api_admin.vendor_request.bulkEdit');
});