<?php

use Illuminate\Support\Facades\Route;

// Placeholder for future Blade admin views.
Route::get('/', function () {
    return redirect(route('announcement.admin.index'));
})->name('emergency.admin.index');
