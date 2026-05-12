<?php

use Illuminate\Support\Facades\Route;

Route::get('/reviews', 'ReviewController@index')->name('api_admin.reviews.index');
