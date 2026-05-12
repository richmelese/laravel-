<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'coupon'], function () {
    Route::get('/','CouponController@index')->name('api_admin.coupon.index');
    Route::get('/create','CouponController@create')->name('api_admin.coupon.create');
    Route::get('/edit/{id}','CouponController@edit')->name('api_admin.coupon.edit')->where('id', '[0-9]+');
    Route::post('/store/{id}','CouponController@store')->name('api_admin.coupon.store')->where('id', '[0-9]+');
    Route::post('/bulkEdit','CouponController@bulkEdit')->name('api_admin.coupon.bulkEdit');
    Route::get('/get_services', 'CouponController@getServiceForSelect2')->name('api_admin.coupon.getServices');
});
