<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'announcements'], function () {
    Route::get('/', 'AnnouncementController@index')->name('api.announcement.index');
});
