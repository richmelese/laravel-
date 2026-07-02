<?php

use Illuminate\Support\Facades\Route;

// ── Alert Requests (Help Button) ──────────────────────────────────────────────
Route::group(['prefix' => 'alert'], function () {
    Route::get('/',                   'AlertAdminController@index')->name('api_admin.alert.index');
    Route::get('/{id}',               'AlertAdminController@show')->name('api_admin.alert.show')->where('id', '[0-9]+');
    Route::put('/{id}',               'AlertAdminController@update')->name('api_admin.alert.update')->where('id', '[0-9]+');
    Route::patch('/{id}',             'AlertAdminController@update')->name('api_admin.alert.patch')->where('id', '[0-9]+');
    Route::delete('/{id}',            'AlertAdminController@destroy')->name('api_admin.alert.destroy')->where('id', '[0-9]+');
    Route::post('/bulk-action',       'AlertAdminController@bulkAction')->name('api_admin.alert.bulk_action');
});

// ── Emergency Hotline ─────────────────────────────────────────────────────────
Route::group(['prefix' => 'emergency-hotline'], function () {
    Route::get('/',             'EmergencyHotlineController@index')->name('api_admin.emergency_hotline.index');
    Route::get('/create',       'EmergencyHotlineController@create')->name('api_admin.emergency_hotline.create');
    Route::post('/store/{id}',  'EmergencyHotlineController@store')->name('api_admin.emergency_hotline.store')->where('id', '[0-9]+');
    Route::post('/bulk-action', 'EmergencyHotlineController@bulkEdit')->name('api_admin.emergency_hotline.bulk_action');
    Route::get('/{id}',         'EmergencyHotlineController@show')->name('api_admin.emergency_hotline.show')->where('id', '[0-9]+');
    Route::get('/{id}/edit',    'EmergencyHotlineController@edit')->name('api_admin.emergency_hotline.edit')->where('id', '[0-9]+');
    Route::delete('/{id}',      'EmergencyHotlineController@destroy')->name('api_admin.emergency_hotline.destroy')->where('id', '[0-9]+');
});

// ── Emergency Numbers ─────────────────────────────────────────────────────────
Route::group(['prefix' => 'emergency-number'], function () {
    Route::get('/',             'EmergencyNumberController@index')->name('api_admin.emergency_number.index');
    Route::get('/create',       'EmergencyNumberController@create')->name('api_admin.emergency_number.create');
    Route::post('/store/{id}',  'EmergencyNumberController@store')->name('api_admin.emergency_number.store')->where('id', '[0-9]+');
    Route::post('/bulk-action', 'EmergencyNumberController@bulkEdit')->name('api_admin.emergency_number.bulk_action');
    Route::get('/{id}',         'EmergencyNumberController@show')->name('api_admin.emergency_number.show')->where('id', '[0-9]+');
    Route::get('/{id}/edit',    'EmergencyNumberController@edit')->name('api_admin.emergency_number.edit')->where('id', '[0-9]+');
    Route::delete('/{id}',      'EmergencyNumberController@destroy')->name('api_admin.emergency_number.destroy')->where('id', '[0-9]+');
});

// ── Medical Centres ───────────────────────────────────────────────────────────
Route::group(['prefix' => 'emergency-medical'], function () {
    Route::get('/',             'EmergencyCentreController@index')->name('api_admin.emergency_medical.index');
    Route::get('/create',       'EmergencyCentreController@create')->name('api_admin.emergency_medical.create');
    Route::post('/store/{id}',  'EmergencyCentreController@store')->name('api_admin.emergency_medical.store')->where('id', '[0-9]+');
    Route::post('/bulk-action', 'EmergencyCentreController@bulkEdit')->name('api_admin.emergency_medical.bulk_action');
    Route::get('/{id}',         'EmergencyCentreController@show')->name('api_admin.emergency_medical.show')->where('id', '[0-9]+');
    Route::get('/{id}/edit',    'EmergencyCentreController@edit')->name('api_admin.emergency_medical.edit')->where('id', '[0-9]+');
    Route::delete('/{id}',      'EmergencyCentreController@destroy')->name('api_admin.emergency_medical.destroy')->where('id', '[0-9]+');
});

// ── COVID & Health ────────────────────────────────────────────────────────────
Route::group(['prefix' => 'emergency-health'], function () {
    Route::get('/',             'EmergencyCovidController@index')->name('api_admin.emergency_health.index');
    Route::get('/create',       'EmergencyCovidController@create')->name('api_admin.emergency_health.create');
    Route::post('/store/{id}',  'EmergencyCovidController@store')->name('api_admin.emergency_health.store')->where('id', '[0-9]+');
    Route::post('/bulk-action', 'EmergencyCovidController@bulkEdit')->name('api_admin.emergency_health.bulk_action');
    Route::get('/{id}',         'EmergencyCovidController@show')->name('api_admin.emergency_health.show')->where('id', '[0-9]+');
    Route::get('/{id}/edit',    'EmergencyCovidController@edit')->name('api_admin.emergency_health.edit')->where('id', '[0-9]+');
    Route::delete('/{id}',      'EmergencyCovidController@destroy')->name('api_admin.emergency_health.destroy')->where('id', '[0-9]+');
});

// ── Travel Support ────────────────────────────────────────────────────────────
Route::group(['prefix' => 'emergency-travel'], function () {
    Route::get('/',             'EmergencyTravelController@index')->name('api_admin.emergency_travel.index');
    Route::get('/create',       'EmergencyTravelController@create')->name('api_admin.emergency_travel.create');
    Route::post('/store/{id}',  'EmergencyTravelController@store')->name('api_admin.emergency_travel.store')->where('id', '[0-9]+');
    Route::post('/bulk-action', 'EmergencyTravelController@bulkEdit')->name('api_admin.emergency_travel.bulk_action');
    Route::get('/{id}',         'EmergencyTravelController@show')->name('api_admin.emergency_travel.show')->where('id', '[0-9]+');
    Route::get('/{id}/edit',    'EmergencyTravelController@edit')->name('api_admin.emergency_travel.edit')->where('id', '[0-9]+');
    Route::delete('/{id}',      'EmergencyTravelController@destroy')->name('api_admin.emergency_travel.destroy')->where('id', '[0-9]+');
});

// ── Quick Contacts ────────────────────────────────────────────────────────────
Route::group(['prefix' => 'emergency-contacts'], function () {
    Route::get('/',             'EmergencyContactController@index')->name('api_admin.emergency_contacts.index');
    Route::get('/create',       'EmergencyContactController@create')->name('api_admin.emergency_contacts.create');
    Route::post('/store/{id}',  'EmergencyContactController@store')->name('api_admin.emergency_contacts.store')->where('id', '[0-9]+');
    Route::post('/bulk-action', 'EmergencyContactController@bulkEdit')->name('api_admin.emergency_contacts.bulk_action');
    Route::get('/{id}',         'EmergencyContactController@show')->name('api_admin.emergency_contacts.show')->where('id', '[0-9]+');
    Route::get('/{id}/edit',    'EmergencyContactController@edit')->name('api_admin.emergency_contacts.edit')->where('id', '[0-9]+');
    Route::delete('/{id}',      'EmergencyContactController@destroy')->name('api_admin.emergency_contacts.destroy')->where('id', '[0-9]+');
});
