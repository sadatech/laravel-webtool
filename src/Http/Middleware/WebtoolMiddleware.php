<?php
namespace Sadatech\Webtool\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Sadatech\Webtool\Application;

class WebtoolMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        // $response->header('X-Laravel-Webtool-Version', Application::LARAVEL_WEBTOOL_VERSION);
        // $response->header('X-Laravel-Webtool-Dummy', '%%%');
        return $response;
    }
}