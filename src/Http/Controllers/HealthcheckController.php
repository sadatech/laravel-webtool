<?php
namespace Sadatech\Webtool\Http\Controllers;

use Sadatech\Webtool\Http\Controllers\Controller;

class HealthcheckController extends Controller
{
    public function HttpResponse()
    {
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Webtool is running'
        ]);
    }
}