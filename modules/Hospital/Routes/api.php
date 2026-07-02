<?php

use Illuminate\Support\Facades\Route;

// Public hospital endpoints
Route::group(['prefix' => 'hospitals'], function () {
    Route::get('/',     'HospitalController@index')->name('api.hospital.index');
    Route::get('/{id}', 'HospitalController@show')->name('api.hospital.show')->where('id', '[0-9]+');
});
