<?php
namespace Sadatech\Webtool\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Carbon\Carbon;
use Exception;
use Config;

class WebtoolController extends Controller
{
    public function __construct()
    {
        //
    }

    public static function __webtool_com($command)
    {
        if (file_exists($command[0]))
        {
            $process = new Process($command, null, [
                'SYNC_FORCE_FETCH' => 'yes',
            ]);
            $process->run();

            if (!$process->isSuccessful())
            {
                throw new ProcessFailedException($process);
            }

            return $process->getOutput();
        }
    }

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
        $process = self::__webtool_com(['/usr/local/bin/webtool', 'app', 'sync', request()->getHost()]);

        return "<style>code{color:white;}</style><pre><code>".strip_tags($process)."</code></pre>";
    }
}
