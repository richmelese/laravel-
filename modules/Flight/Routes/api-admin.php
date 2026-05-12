<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'flight'], function () {
    Route::get('/', 'FlightController@index')->name('api_admin.flight.index');
    Route::get('/recovery', 'FlightController@recovery')->name('api_admin.flight.recovery');
    Route::get('/create', 'FlightController@create')->name('api_admin.flight.create');
    Route::get('/edit/{id}', 'FlightController@edit')->name('api_admin.flight.edit')->where('id', '[0-9]+');
    Route::post('/store/{id}', 'FlightController@store')->name('api_admin.flight.store')->where('id', '[0-9]+');
    Route::post('/bulkEdit', 'FlightController@bulkEdit')->name('api_admin.flight.bulkEdit');

    Route::group(['prefix' => '{flight_id}/flight-seat'], function () {
        Route::get('/', 'FlightSeatController@index')->name('api_admin.flight.seat.index')->where('flight_id', '[0-9]+');
        Route::get('edit/{id}', 'FlightSeatController@edit')->name('api_admin.flight.seat.edit')->where(['flight_id' => '[0-9]+', 'id' => '[0-9]+']);
        Route::post('store/{id}', 'FlightSeatController@store')->name('api_admin.flight.seat.store')->where(['flight_id' => '[0-9]+', 'id' => '[0-9]+']);
        Route::post('/bulkEdit', 'FlightSeatController@bulkEdit')->name('api_admin.flight.seat.bulkEdit')->where('flight_id', '[0-9]+');
    });

    Route::group(['prefix' => 'airline'], function () {
        Route::get('/', 'AirlineController@index')->name('api_admin.flight.airline.index');
        Route::get('edit/{id}', 'AirlineController@edit')->name('api_admin.flight.airline.edit')->where('id', '[0-9]+');
        Route::post('store/{id}', 'AirlineController@store')->name('api_admin.flight.airline.store')->where('id', '[0-9]+');
        Route::post('/bulkEdit', 'AirlineController@bulkEdit')->name('api_admin.flight.airline.bulkEdit');
        Route::get('/getForSelect2', 'AirlineController@getForSelect2')->name('api_admin.flight.airline.getForSelect2');
    });

    Route::group(['prefix' => 'airport'], function () {
        Route::get('/', 'AirportController@index')->name('api_admin.flight.airport.index');
        Route::get('edit/{id}', 'AirportController@edit')->name('api_admin.flight.airport.edit')->where('id', '[0-9]+');
        Route::post('store/{id}', 'AirportController@store')->name('api_admin.flight.airport.store')->where('id', '[0-9]+');
        Route::post('/bulkEdit', 'AirportController@bulkEdit')->name('api_admin.flight.airport.bulkEdit');
        Route::get('/getForSelect2', 'AirportController@getForSelect2')->name('api_admin.flight.airport.getForSelect2');
    });

    Route::group(['prefix' => 'seat-type'], function () {
        Route::get('/', 'SeatTypeController@index')->name('api_admin.flight.seat_type.index');
        Route::get('edit/{id}', 'SeatTypeController@edit')->name('api_admin.flight.seat_type.edit')->where('id', '[0-9]+');
        Route::post('store/{id}', 'SeatTypeController@store')->name('api_admin.flight.seat_type.store')->where('id', '[0-9]+');
        Route::post('/bulkEdit', 'SeatTypeController@bulkEdit')->name('api_admin.flight.seat_type.bulkEdit');
        Route::get('/getForSelect2', 'SeatTypeController@getForSelect2')->name('api_admin.flight.seat_type.getForSelect2');
    });

    Route::group(['prefix' => 'attribute'], function () {
        Route::get('/', 'AttributeController@index')->name('api_admin.flight.attribute.index');
        Route::get('edit/{id}', 'AttributeController@edit')->name('api_admin.flight.attribute.edit')->where('id', '[0-9]+');
        Route::post('/store', 'AttributeController@store')->name('api_admin.flight.attribute.store');
        Route::post('/editAttrBulk', 'AttributeController@editAttrBulk')->name('api_admin.flight.attribute.editAttrBulk');
        Route::get('terms/{id}', 'AttributeController@terms')->name('api_admin.flight.attribute.term.index')->where('id', '[0-9]+');
        Route::get('term_edit/{id}', 'AttributeController@term_edit')->name('api_admin.flight.attribute.term.edit')->where('id', '[0-9]+');
        Route::match(['get', 'post'], 'term_store', 'AttributeController@term_store')->name('api_admin.flight.attribute.term.store');
        Route::post('/editTermBulk', 'AttributeController@editTermBulk')->name('api_admin.flight.attribute.editTermBulk');
        Route::get('/getForSelect2', 'AttributeController@getForSelect2')->name('api_admin.flight.attribute.term.getForSelect2');
    });
});
