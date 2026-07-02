<?php

use Illuminate\Support\Facades\Route;
use Modules\Visa\Admin\VisaController;
use Modules\Visa\Admin\Type\TypeController;

Route::group(['prefix' => 'visa'], function () {
    Route::get('/', [VisaController::class, 'index'])->name('api_admin.visa.index');

    Route::group(['prefix' => 'type'], function () {
        Route::get('/', [TypeController::class, 'index'])->name('api_admin.visa.type.index');
        Route::get('/getForSelect2', [TypeController::class, 'getForSelect2'])->name('api_admin.visa.type.getForSelect2');
        Route::get('/edit/{id}', [TypeController::class, 'edit'])->name('api_admin.visa.type.edit')->where('id', '[0-9]+');
        Route::post('/store/{id?}', [TypeController::class, 'store'])->name('api_admin.visa.type.store')->where('id', '[0-9]+');
        Route::delete('/{id}', [TypeController::class, 'destroy'])->name('api_admin.visa.type.destroy')->where('id', '[0-9]+');
        Route::post('/bulkEdit', [TypeController::class, 'bulkEdit'])->name('api_admin.visa.type.bulkEdit');
    });
});
