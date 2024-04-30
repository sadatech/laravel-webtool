<?php
namespace Sadatech\Webtool\Http\Controllers;

use Sadatech\Webtool\Http\Controllers\Controller;
use Sadatech\Webtool\Package as WebtoolPackage;

class HealthcheckController extends Controller
{
    public function HttpResponse()
    {
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => ucfirst(WebtoolPackage::PACKAGE_NAME) . ' is running',
            'namespace' => WebtoolPackage::PACKAGE_NAMESPACE,
        ]);
    }
}