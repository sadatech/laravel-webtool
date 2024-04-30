<?php
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webtool Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are related to webtool methods
|
*/

/**
 * Healthcheck URL
 */
Route::get('/healthcheck', 'HealthcheckController@HttpResponse')->name('healthcheck');

/**
 * Download Generate URL
 */
// Route::post('/download/generate/{uid?}', 'WebtoolController@downloadGenerate')->name('download.generate');