<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'tour'], function () {
    Route::get('/', 'TourController@index')->name('api_admin.tour.index');
    Route::get('/create', 'TourController@create')->name('api_admin.tour.create');
    Route::get('/edit/{id}', 'TourController@edit')->name('api_admin.tour.edit');
    Route::post('/store/{id}', 'TourController@store')->name('api_admin.tour.store');
    Route::get('/getForSelect2', 'TourController@getForSelect2')->name('api_admin.tour.getForSelect2');
    Route::post('/bulkEdit', 'TourController@bulkEdit')->name('api_admin.tour.bulkEdit');
    Route::get('/recovery', 'TourController@recovery')->name('api_admin.tour.recovery');

    Route::get('/category', 'CategoryController@index')->name('api_admin.tour.category.index');
    Route::get('/category/edit/{id}', 'CategoryController@edit')->name('api_admin.tour.category.edit');
    Route::post('/category/store/{id}', 'CategoryController@store')->name('api_admin.tour.category.store');
    Route::get('/category/getForSelect2', 'CategoryController@getForSelect2')->name('api_admin.tour.category.getForSelect2');
    Route::post('/category/bulkEdit', 'CategoryController@bulkEdit')->name('api_admin.tour.category.bulkEdit');

    Route::group(['prefix' => 'attribute'], function () {
        Route::get('/', 'AttributeController@index')->name('api_admin.tour.attribute.index');
        Route::get('/edit/{id}', 'AttributeController@edit')->name('api_admin.tour.attribute.edit');
        Route::post('/store/{id}', 'AttributeController@store')->name('api_admin.tour.attribute.store');
        Route::post('/editAttrBulk', 'AttributeController@editAttrBulk')->name('api_admin.tour.attribute.editAttrBulk');

        Route::get('/terms/{attr_id}', 'AttributeController@terms')->name('api_admin.tour.attribute.term.index');
        Route::get('/term_edit/{id}', 'AttributeController@term_edit')->name('api_admin.tour.attribute.term.edit');
        Route::post('/term_store/{id}', 'AttributeController@term_store')->name('api_admin.tour.attribute.term.store');
        Route::post('/editTermBulk', 'AttributeController@editTermBulk')->name('api_admin.tour.attribute.term.editTermBulk');
    });

    Route::group(['prefix' => 'availability'], function () {
        Route::get('/', 'AvailabilityController@index')->name('api_admin.tour.availability.index');
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('api_admin.tour.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('api_admin.tour.availability.store');
    });
});
