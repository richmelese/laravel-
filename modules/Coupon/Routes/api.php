<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix'=>config('booking.booking_route_prefix')],function(){
    Route::post('/{code}/apply-coupon','CouponController@applyCoupon')->name('api.coupon.apply');
    Route::post('/{code}/remove-coupon','CouponController@removeCoupon')->name('api.coupon.remove');
});

Route::group(['prefix'=>'user/coupon', 'middleware' => ['auth:sanctum','verified']],function(){
    Route::get('/','CouponController@index')->name('api.coupon.vendor.index');
    Route::get('/create','CouponController@create')->name('api.coupon.vendor.create');
    Route::get('/edit/{id}','CouponController@edit')->name('api.coupon.vendor.edit')->where('id', '[0-9]+');
    Route::delete('/{id}', 'CouponController@delete')->name('api.coupon.vendor.delete')->where('id', '[0-9]+');
    Route::post('/store/{id}','CouponController@store')->name('api.coupon.vendor.store')->where('id', '[0-9]+');
    Route::get('/get_services', 'CouponController@getServiceForSelect2')->name('api.coupon.vendor.getServices');
});
