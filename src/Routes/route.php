<?php

/*
|--------------------------------------------------------------------------
| Webtool Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are related to webtool methods
|
*/

Route::get('/', 'WebtoolController@index')->name('index');

Route::get('/live/sync', 'WebtoolController@liveSync')->name('live-sync');
Route::get('/live/sync/action', 'WebtoolController@liveSyncAction')->name('live-sync.action');