<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'report/enquiries'], function () {
    Route::get('/', 'EnquiryController@apiIndex')->name('api_admin.report.enquiries.index');
    Route::post('/bulkEdit', 'EnquiryController@apiBulkEdit')->name('api_admin.report.enquiries.bulk_edit');
    Route::get('/{enquiry}/reply', 'EnquiryController@apiReplies')->name('api_admin.report.enquiries.replies');
    Route::post('/{enquiry}/reply/store', 'EnquiryController@apiReplyStore')->name('api_admin.report.enquiries.reply_store');
});

Route::group(['prefix' => 'report/statistic'], function () {
    Route::get('/', 'StatisticController@apiIndex')->name('api_admin.report.statistic.index');
    Route::match(['get', 'post'], '/reloadChart', 'StatisticController@apiReloadChart')->name('api_admin.report.statistic.reload_chart');
});
