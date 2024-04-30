<?php
namespace Sadatech\Webtool\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Sadatech\Webtool\Http\Controllers\Controller;

class DebugController extends Controller
{
    public function DebugRouteList()
    {
        return response()->json(Artisan::call('route:list', ['-vvv' => null]));
    }
}