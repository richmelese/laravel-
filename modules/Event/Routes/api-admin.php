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

    Route::group(['prefix' => 'attribute'], function () {
        Route::get('/', 'AttributeController@index')->name('api_admin.event.attribute.index');
        Route::get('/edit/{id}', 'AttributeController@edit')->name('api_admin.event.attribute.edit');
        Route::post('/store/{id}', 'AttributeController@store')->name('api_admin.event.attribute.store');
        Route::post('/editAttrBulk', 'AttributeController@editAttrBulk')->name('api_admin.event.attribute.editAttrBulk');

        Route::get('/terms/{id}', 'AttributeController@terms')->name('api_admin.event.attribute.term.index');
        Route::get('/term_edit/{id}', 'AttributeController@term_edit')->name('api_admin.event.attribute.term.edit');
        Route::post('/term_store', 'AttributeController@term_store')->name('api_admin.event.attribute.term.store');
        Route::post('/editTermBulk', 'AttributeController@editTermBulk')->name('api_admin.event.attribute.term.editTermBulk');

        Route::get('/getForSelect2', 'AttributeController@getForSelect2')->name('api_admin.event.attribute.term.getForSelect2');
    });

    // Availability calendar data + save
    Route::group(['prefix' => 'availability'], function () {
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('api_admin.event.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('api_admin.event.availability.store');
    });
});

