<?php

use Illuminate\Support\Facades\Route;

Route::get('/reviews', 'ReviewController@index')->name('api_admin.reviews.index');
Route::post('/reviews/bulk-edit', 'ReviewController@apiBulkEdit')->name('api_admin.reviews.bulk_edit');
