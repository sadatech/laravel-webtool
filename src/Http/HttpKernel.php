<?php
namespace Sadatech\Webtool\Http;

use Illuminate\Support\Facades\Route;

trait HttpKernel 
{
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function PackageMapHttp($app)
    {
        Route::prefix('webtool')
            ->middleware(['web'])
            ->namespace($app->namespace_http)
            ->as('webtool.')
            ->group($app->basepath('/Http/HttpRoute.php'));
    }
}