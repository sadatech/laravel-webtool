<?php
namespace Sadatech\Webtool\Http;

use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;
use Sadatech\Webtool\Package as WebtoolPackage;
use Sadatech\Webtool\Http\Middleware\ResponseHeader;

trait HttpKernel 
{
    /**
     * This namespace is applied to your controller routes.
     */
    private $namespace_middleware;

    /**
     * Register middleware for the application.
     */
    private function PackageMapHttpMiddleware(Router $router)
    {
        $router->middlewareGroup($this->namespace_middleware, [
            ResponseHeader::class,
        ]);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function PackageMapHttp($app, Router $router)
    {
        // Set namespace middleware
        $this->namespace_middleware = ucfirst(WebtoolPackage::PACKAGE_NAMESPACE).'Middleware';

        // Register middleware
        $this->PackageMapHttpMiddleware($router);

        Route::prefix('webtool')
            ->middleware(['web', $this->namespace_middleware])
            ->namespace($app->namespace_http)
            ->as('webtool.')
            ->group($app->basepath('/Http/HttpRoute.php'));
    }
}