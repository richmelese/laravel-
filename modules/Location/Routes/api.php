<?php

use Illuminate\Http\Request;

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

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('location.location_route_prefix', 'location')], function () {
    Route::get('/{slug}', 'LocationController@detail')->name('api.location.detail');
    Route::get('/search/searchForSelect2', 'LocationController@searchForSelect2')->name('api.location.searchForSelect2');
    Route::get('/state', 'LocationController@stateList')->name('api.location.state');
});
