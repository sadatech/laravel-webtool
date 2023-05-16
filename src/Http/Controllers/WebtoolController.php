<?php
namespace Sadatech\Webtool\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Config;

class WebtoolController extends Controller
{
    public function __construct()
    {
        //
    }

    public function index(Request $request)
    {
        return view(\Sadatech\Webtool\Application::LARAVEL_WEBTOOL_NAMESPACE . '::welcome');
    }

    public function liveSync(Request $request)
    {
        return view(\Sadatech\Webtool\Application::LARAVEL_WEBTOOL_NAMESPACE . '::live_sync');
    }
}
