<?php
namespace Sadatech\Webtool\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Sadatech\Webtool\Http\Traits\DownloadGenerate;

class WebtoolController extends Controller
{
    use DownloadGenerate;
}