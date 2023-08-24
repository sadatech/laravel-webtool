<?php
namespace Sadatech\Webtool\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Sadatech\Webtool\Helpers\Webtool as WebtoolHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class WebtoolController extends Controller
{
    public function index(Request $request)
    {
        return view(\Sadatech\Webtool\Application::LARAVEL_WEBTOOL_NAMESPACE . '::welcome');
    }

    public function liveSync(Request $request)
    {
        return view(\Sadatech\Webtool\Application::LARAVEL_WEBTOOL_NAMESPACE . '::live_sync');
    }

    public function liveSyncAction()
    {
        $process = "<style>code{color:white;}</style><pre><code>";
        $process .= WebtoolHelper::DoCommand(['gb', 'app_sync', request()->getHost()]);
        $process .= "</code></pre>";

        return $process;
    }
}
