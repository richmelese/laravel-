<?php

use Illuminate\Support\Facades\Route;

// Car admin JSON helper endpoints exposed under api-admin
Route::group(['prefix' => 'car'], function () {
    // CRUD (JSON)
    Route::get('/', 'CarController@index')->name('api_admin.car.index');
    Route::get('/recovery', 'CarController@recovery')->name('api_admin.car.recovery');
    Route::get('/create', 'CarController@create')->name('api_admin.car.create');
    Route::get('/edit/{id}', 'CarController@edit')->name('api_admin.car.edit');
    Route::post('/store/{id}', 'CarController@store')->name('api_admin.car.store');
    Route::post('/bulkEdit', 'CarController@bulkEdit')->name('api_admin.car.bulkEdit');

    // Select2 data for cars
    Route::get('/getForSelect2', 'CarController@getForSelect2')->name('api_admin.car.getForSelect2');

    Route::group(['prefix' => 'attribute'], function () {
        Route::get('/', 'AttributeController@index')->name('api_admin.car.attribute.index');
        Route::get('/edit/{id}', 'AttributeController@edit')->name('api_admin.car.attribute.edit');
        Route::post('/store/{id}', 'AttributeController@store')->name('api_admin.car.attribute.store');
        Route::post('/editAttrBulk', 'AttributeController@editAttrBulk')->name('api_admin.car.attribute.editAttrBulk');

        Route::get('/terms/{id}', 'AttributeController@terms')->name('api_admin.car.attribute.term.index');
        Route::get('/term_edit/{id}', 'AttributeController@term_edit')->name('api_admin.car.attribute.term.edit');
        Route::post('/term_store', 'AttributeController@term_store')->name('api_admin.car.attribute.term.store');
        Route::post('/editTermBulk', 'AttributeController@editTermBulk')->name('api_admin.car.attribute.term.editTermBulk');

        Route::get('/getForSelect2', 'AttributeController@getForSelect2')->name('api_admin.car.attribute.term.getForSelect2');
    });

    // Availability calendar data + save
    Route::group(['prefix' => 'availability'], function () {
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('api_admin.car.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('api_admin.car.availability.store');
    });
});

