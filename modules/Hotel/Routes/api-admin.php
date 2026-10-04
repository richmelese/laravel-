<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'hotel'], function () {
    Route::get('/', 'HotelController@index')->name('api_admin.hotel.index');
    Route::get('/create', 'HotelController@create')->name('api_admin.hotel.create');
    Route::get('/edit/{id}', 'HotelController@edit')->name('api_admin.hotel.edit');
    Route::post('/store/{id}', 'HotelController@store')->name('api_admin.hotel.store');
    Route::post('/bulkEdit', 'HotelController@bulkEdit')->name('api_admin.hotel.bulkEdit');
    Route::get('/recovery', 'HotelController@recovery')->name('api_admin.hotel.recovery');
    Route::get('/getForSelect2', 'HotelController@getForSelect2')->name('api_admin.hotel.getForSelect2');

    Route::group(['prefix' => 'attribute'], function () {
        Route::get('/', 'AttributeController@index')->name('api_admin.hotel.attribute.index');
        Route::get('/edit/{id}', 'AttributeController@edit')->name('api_admin.hotel.attribute.edit');
        Route::post('/store/{id}', 'AttributeController@store')->name('api_admin.hotel.attribute.store');
        Route::post('/editAttrBulk', 'AttributeController@editAttrBulk')->name('api_admin.hotel.attribute.bulkEdit');

        Route::get('/terms/{id}', 'AttributeController@terms')->name('api_admin.hotel.attribute.term.index');
        Route::get('/term_edit/{id}', 'AttributeController@term_edit')->name('api_admin.hotel.attribute.term.edit');
        Route::post('/term_store', 'AttributeController@term_store')->name('api_admin.hotel.attribute.term.store');
        Route::post('/editTermBulk', 'AttributeController@editTermBulk')->name('api_admin.hotel.attribute.term.bulkEdit');
        Route::post('/term_edit_bulk', 'AttributeController@editTermBulk')->name('api_admin.hotel.attribute.term.edit_bulk_alias');

        Route::get('/getForSelect2', 'AttributeController@getForSelect2')->name('api_admin.hotel.attribute.term.getForSelect2');
        Route::get('/getAttributeForSelect2', 'AttributeController@getAttributeForSelect2')->name('api_admin.hotel.attribute.getForSelect2');
    });

    Route::group(['prefix' => 'room'], function () {
        Route::group(['prefix' => 'attribute'], function () {
            Route::get('/', 'RoomAttributeController@index')->name('api_admin.hotel.room.attribute.index');
            Route::get('/edit/{id}', 'RoomAttributeController@edit')->name('api_admin.hotel.room.attribute.edit');
            Route::post('/store/{id}', 'RoomAttributeController@store')->name('api_admin.hotel.room.attribute.store');
            Route::post('/editAttrBulk', 'RoomAttributeController@editAttrBulk')->name('api_admin.hotel.room.attribute.editAttrBulk');

            Route::get('/terms/{id}', 'RoomAttributeController@terms')->name('api_admin.hotel.room.attribute.term.index');
            Route::get('/term_edit/{id}', 'RoomAttributeController@term_edit')->name('api_admin.hotel.room.attribute.term.edit');
            Route::post('/term_store', 'RoomAttributeController@term_store')->name('api_admin.hotel.room.attribute.term.store');
            Route::post('/editTermBulk', 'RoomAttributeController@editTermBulk')->name('api_admin.hotel.room.attribute.term.bulkEdit');
            Route::post('/term_edit_bulk', 'RoomAttributeController@editTermBulk')->name('api_admin.hotel.room.attribute.term.edit_bulk_alias');
            Route::get('/getForSelect2', 'RoomAttributeController@getForSelect2')->name('api_admin.hotel.room.attribute.term.getForSelect2');
        });

        Route::get('/{hotel_id}/index', 'RoomController@index')->name('api_admin.hotel.room.index');
        Route::get('/{hotel_id}/create', 'RoomController@create')->name('api_admin.hotel.room.create');
        Route::get('/{hotel_id}/edit/{id}', 'RoomController@edit')->name('api_admin.hotel.room.edit');
        Route::post('/{hotel_id}/store/{id}', 'RoomController@store')->name('api_admin.hotel.room.store');
        Route::post('/bulkEdit', 'RoomController@bulkEdit')->name('api_admin.hotel.room.bulkEdit');
    });

    Route::group(['prefix' => '{hotel_id}/availability'], function () {
        Route::get('/', 'AvailabilityController@index')->name('api_admin.hotel.room.availability.index');
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('api_admin.hotel.room.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('api_admin.hotel.room.availability.store');
    });

    Route::group(['prefix' => 'availability'], function () {
        Route::get('/', 'AvailabilityController@index')->name('api_admin.hotel.availability.index');
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('api_admin.hotel.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('api_admin.hotel.availability.store');
    });
});
