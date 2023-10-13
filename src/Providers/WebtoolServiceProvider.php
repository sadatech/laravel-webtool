<?php
namespace Sadatech\Webtool\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Sadatech\Webtool\Console\Kernel as WebtoolConsoleKernel;
use Sadatech\Webtool\Http\Middleware\WebtoolMiddleware;

class WebtoolServiceProvider extends ServiceProvider
{
    use WebtoolConsoleKernel;

    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = \Sadatech\Webtool\Application::LARAVEL_WEBTOOL_NAMESPACE;

    /**
     * 
     */
    protected function basepath($location)
    {
        return realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.$location);
    }

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        // parent::boot();

        if ($this->app->runningInConsole())
        {
            $this->webtoolMapConsole();
        }
        else
        {
            // register middleware
            $router->middlewareGroup('WebtoolMiddleware', [WebtoolMiddleware::class]);

            $this->loadViewsFrom($this->basepath('/dist/resources/views'), $this->namespace);
            $this->webtoolMapRoutes();
        }

    }

    /**
     * Define the console for the application.
     *
     * @return void
     */
    protected function webtoolMapConsole()
    {
        $this->commands($this->commands);
    }

    /**
     * Define the "webtool" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function webtoolMapRoutes()
    {
        Route::prefix('webtool')
             ->middleware(['web', 'WebtoolMiddleware'])
             ->namespace($this->namespace)
             ->as('webtool.')
             ->group($this->basepath('/dist/routes/webtool.php'));
    }
}