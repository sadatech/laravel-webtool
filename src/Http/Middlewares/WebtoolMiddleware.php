<?php
namespace Sadatech\Webtool\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;

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
        $response->header('X-Laravel-Webtool-Version', LARAVEL_WEBTOOL_VERSION);
        $response->header('X-Laravel-Webtool-Dummy', '%%%');
        return $response;
    }
}