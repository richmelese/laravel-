<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'news'], function () {
    Route::get('/', 'NewsController@index')->name('api_admin.news.index');
    Route::get('/create', 'NewsController@create')->name('api_admin.news.create');
    Route::get('/edit/{id}', 'NewsController@edit')->name('api_admin.news.edit');
    Route::post('/store/{id}', 'NewsController@store')->name('api_admin.news.store');
    Route::post('/bulkEdit', 'NewsController@bulkEdit')->name('api_admin.news.bulkEdit');

    Route::get('/category', 'CategoryController@index')->name('api_admin.news.category.index');
    Route::get('/category/getForSelect2', 'CategoryController@getForSelect2')->name('api_admin.news.category.getForSelect2');
    Route::get('/category/edit/{id}', 'CategoryController@edit')->name('api_admin.news.category.edit');
    Route::post('/category/store/{id}', 'CategoryController@store')->name('api_admin.news.category.store');
    Route::post('/category/bulkEdit', 'CategoryController@bulkEdit')->name('api_admin.news.category.bulkEdit');

    Route::get('/tag', 'TagController@index')->name('api_admin.news.tag.index');
    Route::get('/tag/getForSelect2', 'TagController@getForSelect2')->name('api_admin.news.tag.getForSelect2');
    Route::get('/tag/edit/{id}', 'TagController@edit')->name('api_admin.news.tag.edit')->where('id', '[0-9]+');
    Route::post('/tag/store/{id}', 'TagController@store')->name('api_admin.news.tag.store')->where('id', '[0-9]+');
    Route::post('/tag/bulkEdit', 'TagController@bulkEdit')->name('api_admin.news.tag.bulkEdit');
});

