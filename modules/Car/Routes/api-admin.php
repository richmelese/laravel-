<?php

use Illuminate\Support\Facades\Route;

// Car admin JSON helper endpoints exposed under api-admin
Route::group(['prefix' => 'car'], function () {
    // Select2 data for cars
    Route::get('/getForSelect2', 'CarController@getForSelect2')->name('api_admin.car.getForSelect2');

    // Attribute term select2
    Route::get('/attribute/getForSelect2', 'AttributeController@getForSelect2')->name('api_admin.car.attribute.term.getForSelect2');

    // Availability calendar data + save
    Route::group(['prefix' => 'availability'], function () {
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('api_admin.car.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('api_admin.car.availability.store');
    });
});

