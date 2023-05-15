<?php
namespace Sadatech\Webtool\Providers;

use Illuminate\Support\ServiceProvider;

class WebtoolServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->routeMiddleware([
            'access' => ServiceAccessMiddleware::class,
        ]);
    }
    
}