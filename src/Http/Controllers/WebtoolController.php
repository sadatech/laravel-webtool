<?php
namespace Sadatech\Webtool\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Package trait
 */
use Sadatech\Webtool\Http\Traits\DownloadGenerate;
use Sadatech\Webtool\Http\Traits\Healthcheck;

class WebtoolController extends Controller
{
    use DownloadGenerate, Healthcheck;
}