<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'property'], function () {
    Route::get('/', 'PropertyController@index')->name('api_admin.property.index');
    Route::get('/create', 'PropertyController@create')->name('api_admin.property.create');
    Route::get('/edit/{id}', 'PropertyController@edit')->name('api_admin.property.edit');
    Route::post('/store/{id}', 'PropertyController@store')->name('api_admin.property.store');
    Route::post('/bulkEdit', 'PropertyController@bulkEdit')->name('api_admin.property.bulkEdit');

    Route::get('/getForSelect2', 'PropertyController@getForSelect2')->name('api_admin.property.getForSelect2');
});

