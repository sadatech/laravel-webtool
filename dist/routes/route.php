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
Route::post('/live/sync/action', 'WebtoolController@liveSyncAction')->name('live-sync.action');

/**
 * Download Generate URL
 */
Route::get('/download/generate/{uid?}', 'WebtoolController@downloadGenerate')->name('download.generate');