<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'space'], function () {
    Route::get('/', 'SpaceController@index')->name('api_admin.space.index');
    Route::get('/create', 'SpaceController@create')->name('api_admin.space.create');
    Route::get('/edit/{id}', 'SpaceController@edit')->name('api_admin.space.edit');
    Route::post('/store/{id}', 'SpaceController@store')->name('api_admin.space.store');
    Route::post('/bulkEdit', 'SpaceController@bulkEdit')->name('api_admin.space.bulkEdit');
    Route::get('/recovery', 'SpaceController@recovery')->name('api_admin.space.recovery');
    Route::get('/getForSelect2', 'SpaceController@getForSelect2')->name('api_admin.space.getForSelect2');

    Route::group(['prefix' => 'attribute'], function () {
        Route::get('/', 'AttributeController@index')->name('api_admin.space.attribute.index');
        Route::get('/edit/{id}', 'AttributeController@edit')->name('api_admin.space.attribute.edit');
        Route::post('/store/{id}', 'AttributeController@store')->name('api_admin.space.attribute.store');
        Route::post('/editAttrBulk', 'AttributeController@editAttrBulk')->name('api_admin.space.attribute.editAttrBulk');

        Route::get('/terms/{id}', 'AttributeController@terms')->name('api_admin.space.attribute.term.index');
        Route::get('/term_edit/{id}', 'AttributeController@term_edit')->name('api_admin.space.attribute.term.edit');
        Route::post('/term_store', 'AttributeController@term_store')->name('api_admin.space.attribute.term.store');
        Route::post('/editTermBulk', 'AttributeController@editTermBulk')->name('api_admin.space.attribute.term.editTermBulk');

        Route::get('/getForSelect2', 'AttributeController@getForSelect2')->name('api_admin.space.attribute.term.getForSelect2');
    });

    Route::group(['prefix' => 'availability'], function () {
        Route::get('/', 'AvailabilityController@index')->name('api_admin.space.availability.index');
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('api_admin.space.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('api_admin.space.availability.store');
    });
});
