<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'vendor', 'middleware' => ['auth:sanctum']], function () {
    Route::prefix('chapa')->group(function () {
        Route::get('/banks', 'ChapaApiController@banks')->name('api.vendor.chapa.banks');

        // Self (vendor reading / updating their own subaccount)
        Route::get('/subaccount', 'ChapaApiController@show')->name('api.vendor.chapa.subaccount.show');
        Route::post('/subaccount', 'ChapaApiController@store')->name('api.vendor.chapa.subaccount.store');

        // Link an EXISTING Chapa subaccount id (no API call to Chapa).
        // Use this when Chapa returns "This subaccount does exist".
        Route::post('/subaccount/link', 'ChapaApiController@link')
            ->name('api.vendor.chapa.subaccount.link');

        // Admin acting on behalf of a specific vendor (or vendor reading/managing their own by id)
        Route::get('/subaccount/{vendor}', 'ChapaApiController@showByVendorId')
            ->whereNumber('vendor')
            ->name('api.vendor.chapa.subaccount.byVendor');
        Route::post('/subaccount/{vendor}', 'ChapaApiController@storeForVendor')
            ->whereNumber('vendor')
            ->name('api.vendor.chapa.subaccount.storeForVendor');
        Route::post('/subaccount/{vendor}/link', 'ChapaApiController@linkForVendor')
            ->whereNumber('vendor')
            ->name('api.vendor.chapa.subaccount.linkForVendor');
    });
});
