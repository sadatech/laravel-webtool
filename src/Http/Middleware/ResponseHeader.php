<?php
namespace Sadatech\Webtool\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Sadatech\Webtool\Package;

class ResponseHeader
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
        $response->header('X-Laravel-Webtool-Version', Package::PACKAGE_VERSION);
        $response->header('X-Laravel-Webtool-Namespace', Package::PACKAGE_NAMESPACE);
        $response->header('X-Laravel-Webtool-Dummy', '%%%');
        return $response;
    }
}