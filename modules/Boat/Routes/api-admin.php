<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'boat'], function () {
    Route::get('/', 'BoatController@index')->name('api_admin.boat.index');
    Route::get('/recovery', 'BoatController@recovery')->name('api_admin.boat.recovery');
    Route::get('/create', 'BoatController@create')->name('api_admin.boat.create');
    Route::get('/edit/{id}', 'BoatController@edit')->name('api_admin.boat.edit');
    Route::post('/store/{id}', 'BoatController@store')->name('api_admin.boat.store');
    Route::post('/bulkEdit', 'BoatController@bulkEdit')->name('api_admin.boat.bulkEdit');

    Route::get('/getForSelect2', 'BoatController@getForSelect2')->name('api_admin.boat.getForSelect2');

    Route::group(['prefix' => 'attribute'], function () {
        Route::get('/', 'AttributeController@index')->name('api_admin.boat.attribute.index');
        Route::get('/edit/{id}', 'AttributeController@edit')->name('api_admin.boat.attribute.edit');
        Route::post('/store/{id}', 'AttributeController@store')->name('api_admin.boat.attribute.store');
        Route::post('/editAttrBulk', 'AttributeController@editAttrBulk')->name('api_admin.boat.attribute.editAttrBulk');

        Route::get('/terms/{id}', 'AttributeController@terms')->name('api_admin.boat.attribute.term.index');
        Route::get('/term_edit/{id}', 'AttributeController@term_edit')->name('api_admin.boat.attribute.term.edit');
        Route::post('/term_store', 'AttributeController@term_store')->name('api_admin.boat.attribute.term.store');
        Route::post('/editTermBulk', 'AttributeController@editTermBulk')->name('api_admin.boat.attribute.term.editTermBulk');
        Route::post('/term_edit_bulk', 'AttributeController@editTermBulk')->name('api_admin.boat.attribute.term.edit_bulk_alias');

        Route::get('/getForSelect2', 'AttributeController@getForSelect2')->name('api_admin.boat.attribute.term.getForSelect2');
    });

    Route::group(['prefix' => 'availability'], function () {
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('api_admin.boat.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('api_admin.boat.availability.store');
    });
});

// Dedicated bus endpoints (stored in bc_buses table).
Route::group(['prefix' => 'bus'], function () {
    Route::get('/', 'BusController@index')->name('api_admin.bus.index');
    Route::get('/{id}/seat-availability', 'BusBookingController@busSeatAvailability')->name('api_admin.bus.seat_availability')->where('id', '[0-9]+');
    Route::post('/', 'BusController@store')->defaults('id', 0)->name('api_admin.bus.create');
    Route::get('/recovery', 'BusController@recovery')->name('api_admin.bus.recovery');
    Route::get('/create', 'BusController@create')->name('api_admin.bus.create_form');
    Route::get('/edit/{id}', 'BusController@edit')->name('api_admin.bus.edit');

    // Legacy style still used by existing panels/clients
    Route::post('/store/{id}', 'BusController@store')->name('api_admin.bus.store');
    // REST-friendly aliases
    Route::post('/store', 'BusController@store')->defaults('id', 0)->name('api_admin.bus.store.create');
    Route::put('/store/{id}', 'BusController@store')->name('api_admin.bus.store.put');
    Route::patch('/store/{id}', 'BusController@store')->name('api_admin.bus.store.patch');
    Route::put('/{id}', 'BusController@store')->name('api_admin.bus.update');
    Route::patch('/{id}', 'BusController@store')->name('api_admin.bus.partial_update');

    Route::post('/bulkEdit', 'BusController@bulkEdit')->name('api_admin.bus.bulkEdit');
});

Route::group(['prefix' => 'buses'], function () {
    Route::get('/', 'BusController@index')->name('api_admin.buses.index');
    Route::get('/{id}/seat-availability', 'BusBookingController@busSeatAvailability')->name('api_admin.buses.seat_availability')->where('id', '[0-9]+');
    Route::post('/', 'BusController@store')->defaults('id', 0)->name('api_admin.buses.create');
    Route::get('/recovery', 'BusController@recovery')->name('api_admin.buses.recovery');
    Route::get('/create', 'BusController@create')->name('api_admin.buses.create_form');
    Route::get('/edit/{id}', 'BusController@edit')->name('api_admin.buses.edit');

    Route::post('/store/{id}', 'BusController@store')->name('api_admin.buses.store');
    Route::post('/store', 'BusController@store')->defaults('id', 0)->name('api_admin.buses.store.create');
    Route::put('/store/{id}', 'BusController@store')->name('api_admin.buses.store.put');
    Route::patch('/store/{id}', 'BusController@store')->name('api_admin.buses.store.patch');
    Route::put('/{id}', 'BusController@store')->name('api_admin.buses.update');
    Route::patch('/{id}', 'BusController@store')->name('api_admin.buses.partial_update');

    Route::post('/bulkEdit', 'BusController@bulkEdit')->name('api_admin.buses.bulkEdit');
});

Route::group(['prefix' => 'bus-routes'], function () {
    Route::get('/', 'BusRouteController@index')->name('api_admin.bus_routes.index');
    Route::post('/', 'BusRouteController@store')->defaults('id', 0)->name('api_admin.bus_routes.create');
    Route::post('/store/{id}', 'BusRouteController@store')->name('api_admin.bus_routes.store');
    Route::put('/{id}', 'BusRouteController@store')->name('api_admin.bus_routes.update');
    Route::patch('/{id}', 'BusRouteController@store')->name('api_admin.bus_routes.partial_update');
});

Route::group(['prefix' => 'bus-schedules'], function () {
    Route::get('/', 'BusScheduleController@index')->name('api_admin.bus_schedules.index');
    Route::post('/', 'BusScheduleController@store')->defaults('id', 0)->name('api_admin.bus_schedules.create');
    Route::post('/store/{id}', 'BusScheduleController@store')->name('api_admin.bus_schedules.store');
    Route::put('/{id}', 'BusScheduleController@store')->name('api_admin.bus_schedules.update');
    Route::patch('/{id}', 'BusScheduleController@store')->name('api_admin.bus_schedules.partial_update');
});

Route::group(['prefix' => 'bus-bookings'], function () {
    Route::get('/', 'BusBookingController@index')->name('api_admin.bus_bookings.index');
    Route::get('/{id}', 'BusBookingController@show')->name('api_admin.bus_bookings.show')->where('id', '[0-9]+');
    Route::patch('/{id}/status', 'BusBookingController@updateStatus')->name('api_admin.bus_bookings.status')->where('id', '[0-9]+');
});

