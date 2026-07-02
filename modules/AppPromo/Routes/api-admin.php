<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'app-promo'], function () {
    // Get / save promotion section settings
    Route::get('/settings', 'AppPromoController@getSettings')->name('api_admin.app_promo.settings');
    Route::put('/settings', 'AppPromoController@saveSettings')->name('api_admin.app_promo.settings.update');
    Route::patch('/settings', 'AppPromoController@saveSettings')->name('api_admin.app_promo.settings.patch');

    // List all redemptions
    Route::get('/redemptions', 'AppPromoController@redemptions')->name('api_admin.app_promo.redemptions');
    // Bulk revoke
    Route::post('/redemptions/bulk-action', 'AppPromoController@bulkAction')->name('api_admin.app_promo.redemptions.bulk');
    // Revoke single redemption (allows user to reuse)
    Route::delete('/redemptions/{id}', 'AppPromoController@revokeRedemption')->name('api_admin.app_promo.redemptions.revoke')->where('id', '[0-9]+');
});
