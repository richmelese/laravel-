<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => env('COURSE_ROUTE_PREFIX', 'course')], function () {
    Route::get('/', 'CourseController@index')->name('api.course.search');
    Route::get('/{slug}', 'CourseController@detail')->name('api.course.detail');
    Route::get('/{slug}/learn', 'CourseController@learn')->name('api.course.learn');
    Route::get('/scorm-player/{id}', 'ScormPlayerController@player')->name('api.course.scorm_player');
    Route::post('/study-log', 'CourseController@studyLog')->name('api.course.study-log')->middleware('auth:sanctum');
});

Route::group(['prefix' => 'user/' . env('COURSE_ROUTE_PREFIX', 'course')], function () {
    Route::match(['get','post'], '/', 'ManageCourseController@manageCar')->name('api.course.teacher.index');
    Route::match(['get','post'], '/create', 'ManageCourseController@createCar')->name('api.course.teacher.create');
    Route::match(['get','post'], '/edit/{slug}', 'ManageCourseController@editCar')->name('api.course.teacher.edit');
    Route::match(['get','post'], '/del/{slug}', 'ManageCourseController@deleteCar')->name('api.course.teacher.delete');
    Route::match(['post'], '/store/{slug}', 'ManageCourseController@store')->name('api.course.teacher.store');
    Route::get('bulkEdit/{id}', 'ManageCourseController@bulkEditCar')->name("api.course.teacher.bulk_edit");
});
