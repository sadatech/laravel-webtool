<?php
/*
|--------------------------------------------------------------------------
| Webtool Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are related to webtool methods
|
*/

/**
 * Download Generate URL
 */
Route::post('/download/generate/{uid?}', 'WebtoolController@downloadGenerate')->name('download.generate');