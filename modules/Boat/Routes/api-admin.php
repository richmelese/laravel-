<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'boat'], function () {
    Route::get('/', 'BoatController@index')->name('api_admin.boat.index');
    Route::get('/recovery', 'BoatController@recovery')->name('api_admin.boat.recovery');
    Route::get('/create', 'BoatController@create')->name('api_admin.boat.create');
    Route::get('/edit/{id}', 'BoatController@edit')->name('api_admin.boat.edit');
    Route::post('/store/{id}', 'BoatController@store')->name('api_admin.boat.store');
    Route::post('/bulkEdit', 'BoatController@bulkEdit')->name('api_admin.boat.bulkEdit');

    Route::get('/getForSelect2', 'BoatController@getForSelect2')->name('api_admin.boat.getForSelect2');

    Route::get('/attribute/getForSelect2', 'AttributeController@getForSelect2')->name('api_admin.boat.attribute.term.getForSelect2');

    Route::group(['prefix' => 'availability'], function () {
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('api_admin.boat.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('api_admin.boat.availability.store');
    });
});

