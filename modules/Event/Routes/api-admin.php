<?php

use Illuminate\Support\Facades\Route;

// Event admin JSON helper endpoints exposed under api-admin
Route::group(['prefix' => 'event'], function () {
    // CRUD (JSON)
    Route::get('/', 'EventController@index')->name('api_admin.event.index');
    Route::get('/recovery', 'EventController@recovery')->name('api_admin.event.recovery');
    Route::get('/create', 'EventController@create')->name('api_admin.event.create');
    Route::get('/edit/{id}', 'EventController@edit')->name('api_admin.event.edit');
    Route::post('/store/{id}', 'EventController@store')->name('api_admin.event.store');
    Route::post('/bulkEdit', 'EventController@bulkEdit')->name('api_admin.event.bulkEdit');

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

