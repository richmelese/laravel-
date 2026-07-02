<?php

use Illuminate\Support\Facades\Route;

Route::group([], function () {
    Route::get('/', 'AnnouncementController@index')->name('announcement.admin.index');
    Route::get('/create', 'AnnouncementController@create')->name('announcement.admin.create');
    Route::get('/edit/{id}', 'AnnouncementController@edit')->name('announcement.admin.edit');
    Route::post('/store/{id}', 'AnnouncementController@store')->name('announcement.admin.store');
    Route::post('/bulkEdit', 'AnnouncementController@bulkEdit')->name('announcement.admin.bulkEdit');
});
