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
 * Debug Route List
 */
Route::group(['prefix' => 'debug', 'as' => 'debug.'], function(){});

/**
 * Download Generate URL
 */
Route::group(['prefix' => 'download', 'as' => 'download.'], function(){});