<?php

use Illuminate\Support\Facades\Route;

Route::get('/dashboard', 'DashboardController@apiDashboard')->name('api_admin.dashboard');
