<?php
namespace Sadatech\Webtool\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Sadatech\Webtool\Http\Controllers\Controller;

class DebugController extends Controller
{
    public function DebugRouteList()
    {
        return response()->json(Artisan::class('route:list', ['-vvv' => null]));
    }
}