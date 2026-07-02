<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'announcement'], function () {
    // List all
    Route::get('/', 'AnnouncementController@index')->name('api_admin.announcement.index');
    // Get form defaults for a new item
    Route::get('/create', 'AnnouncementController@create')->name('api_admin.announcement.create');
    // Create (id=0) or Update (id>0)
    Route::post('/store/{id}', 'AnnouncementController@store')->name('api_admin.announcement.store')->where('id', '[0-9]+');
    // Get single
    Route::get('/{id}', 'AnnouncementController@show')->name('api_admin.announcement.show')->where('id', '[0-9]+');
    // Get for editing
    Route::get('/{id}/edit', 'AnnouncementController@edit')->name('api_admin.announcement.edit')->where('id', '[0-9]+');
    // Delete single
    Route::delete('/{id}', 'AnnouncementController@destroy')->name('api_admin.announcement.destroy')->where('id', '[0-9]+');
    // Bulk publish/draft/delete
    Route::post('/bulk-action', 'AnnouncementController@bulkEdit')->name('api_admin.announcement.bulkEdit');
});
