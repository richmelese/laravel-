<?php

use Illuminate\Support\Facades\Route;
use Modules\Visa\Admin\VisaController;

Route::group(['prefix' => 'visa'], function () {
    Route::get('/', [VisaController::class, 'index'])->name('api_admin.visa.index');
});
