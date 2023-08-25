<?php
namespace Sadatech\Webtool\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Sadatech\Webtool\Helpers\Webtool as WebtoolHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

use Sadatech\Webtool\Traits\DownloadGenerate;

class WebtoolController extends Controller
{
    use DownloadGenerate;

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
        $process .= "[#] Starting live synchronize... <br>";
        $process .= WebtoolHelper::DoCommand(['git', '-C', "/data/".request()->getHost()."/sadata-reporting", "status"]);
        $process .= WebtoolHelper::DoCommand(['git', '-C', "/data/".request()->getHost()."/sadata-reporting", "restore", "/data/".request()->getHost()."/sadata-reporting"]);
        $process .= WebtoolHelper::DoCommand(['git', '-C', "/data/".request()->getHost()."/sadata-reporting", "pull", "origin", "master"]);
        $process .= WebtoolHelper::DoCommand(['php', "/data/".request()->getHost()."/sadata-reporting/artisan", "clear-compiled", "-vvv"]);
        $process .= WebtoolHelper::DoCommand(['php', "/data/".request()->getHost()."/sadata-reporting/artisan", "cache:clear", "--no-interaction", "-vvv"]);
        $process .= WebtoolHelper::DoCommand(['php', "/data/".request()->getHost()."/sadata-reporting/artisan", "view:clear", "--no-interaction", "-vvv"]);
        $process .= WebtoolHelper::DoCommand(['php', "/data/".request()->getHost()."/sadata-reporting/artisan", "migrate", "--no-interaction", "--force", "-vvv"]);
        $process .= WebtoolHelper::DoCommand(['php', "/usr/bin/composer", "--working-dir=/data/".request()->getHost()."/sadata-reporting", "-n", "update", "--ignore-platform-req=ext-mongodb", "--no-plugins", "--no-interaction"]);
        $process .= WebtoolHelper::DoCommand(['php', "/usr/bin/composer", "--working-dir=/data/".request()->getHost()."/sadata-reporting", "-n", "dumpa", "--no-plugins", "-o", "-a"]);
        $process .= "</code></pre>";

        return $process;
    }
}
