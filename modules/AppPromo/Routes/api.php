<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'app-promo'], function () {
    // Homepage promotion section content
    Route::get('/', 'AppPromoController@section')->name('api.app_promo.section');
    // Pre-check: is this code valid and unused for this email? (no side effects)
    Route::post('/validate', 'AppPromoController@validate')->name('api.app_promo.validate');
    // Apply promo code to a booking (records redemption)
    Route::post('/redeem', 'AppPromoController@redeem')->name('api.app_promo.redeem');
});
