<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'contact'], function () {
    Route::get('/', 'ContactController@index')->name('api_admin.contact.index');
    Route::post('/bulkEdit', 'ContactController@bulkEdit')->name('api_admin.contact.bulkEdit');
});
