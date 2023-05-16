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
        return response()->json(["webtool_connector" => "0.1-beta"]);
    }
}
