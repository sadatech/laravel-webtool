<?php
namespace Sadatech\Webtool\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Sadatech\Webtool\Package as WebtoolPackage;
use Sadatech\Webtool\Console\Kernel as WebtoolConsoleKernel;
use Sadatech\Webtool\Http\Kernel as WebtoolHttpKernel;

class WebtoolServiceProvider extends ServiceProvider
{
    use WebtoolConsoleKernel, WebtoolHttpKernel;

    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = WebtoolPackage::PACKAGE_NAMESPACE;
    protected $namespace_http = WebtoolPackage::PACKAGE_NAMESPACE.'\Http\Controllers';

    /**
     * Define base path for the package
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
        if ($this->app->runningInConsole())
        {
            $this->PackageMapConsole($this, $namespace);
        }
        else
        {
            $this->PackageMapHttp($this, $namespace_http);
        }
    }
}