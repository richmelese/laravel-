<?php

use Illuminate\Support\Facades\Route;

// Event admin JSON helper endpoints exposed under api-admin
Route::group(['prefix' => 'event'], function () {
    // Select2 data for events
    Route::get('/getForSelect2', 'EventController@getForSelect2')->name('api_admin.event.getForSelect2');

    // Attribute term select2
    Route::get('/attribute/getForSelect2', 'AttributeController@getForSelect2')->name('api_admin.event.attribute.term.getForSelect2');

    // Availability calendar data + save
    Route::group(['prefix' => 'availability'], function () {
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('api_admin.event.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('api_admin.event.availability.store');
    });
});

