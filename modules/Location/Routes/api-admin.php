<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'location'], function () {
    Route::get('/', 'LocationController@index')->name('api_admin.location.index');
    Route::get('/create', 'LocationController@create')->name('api_admin.location.create');
    Route::get('/edit/{id}', 'LocationController@edit')->name('api_admin.location.edit');
    Route::post('/store/{id}', 'LocationController@store')->name('api_admin.location.store');
    Route::get('/getForSelect2', 'LocationController@getForSelect2')->name('api_admin.location.getForSelect2');
    Route::post('/bulkEdit', 'LocationController@bulkEdit')->name('api_admin.location.bulkEdit');

    Route::group(['prefix' => 'category'], function () {
        Route::get('/', 'CategoryController@index')->name('api_admin.location.category.index');
        Route::get('/edit/{id}', 'CategoryController@edit')->name('api_admin.location.category.edit');
        Route::post('/store/{id}', 'CategoryController@store')->name('api_admin.location.category.store');
        Route::post('/bulkEdit', 'CategoryController@bulkEdit')->name('api_admin.location.category.bulkEdit');
        Route::get('/getForSelect2', 'CategoryController@getForSelect2')->name('api_admin.location.category.getForSelect2');
    });
});
