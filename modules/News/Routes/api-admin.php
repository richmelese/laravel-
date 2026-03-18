<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'news'], function () {
    Route::get('/', 'NewsController@index')->name('api_admin.news.index');
    Route::get('/create', 'NewsController@create')->name('api_admin.news.create');
    Route::get('/edit/{id}', 'NewsController@edit')->name('api_admin.news.edit');
    Route::post('/store/{id}', 'NewsController@store')->name('api_admin.news.store');
    Route::post('/bulkEdit', 'NewsController@bulkEdit')->name('api_admin.news.bulkEdit');
});

