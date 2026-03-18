<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('news.news_route_prefix', 'news')], function () {
    Route::get('/', 'NewsController@index')->name('api.news.index');
    Route::get('/{slug}', 'NewsController@detail')->name('api.news.detail');

    Route::get('/' . config('news.news_category_route_prefix', 'category') . '/{slug}', 'CategoryNewsController@index')
        ->name('api.news.category.index');
    Route::get('/' . config('news.news_tag_route_prefix', 'tag') . '/{slug}', 'TagNewsController@index')
        ->name('api.news.tag.index');
});

