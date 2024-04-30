<?php
namespace Sadatech\Webtool\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Sadatech\Webtool\Package as WebtoolPackage;

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
        $response->header('X-Laravel-Webtool-Version', WebtoolPackage::PACKAGE_VERSION);
        $response->header('X-Laravel-Webtool-Namespace', WebtoolPackage::PACKAGE_NAMESPACE);
        $response->header('X-Laravel-Webtool-Dummy', '%%%');
        return $response;
    }
}