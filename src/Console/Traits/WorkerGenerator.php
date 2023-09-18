<?php
namespace Sadatech\Webtool\Console\Traits;

use Sadatech\Webtool\Helpers\Common;

trait WorkerGenerator
{
    /**
     * Generate worker
     * 
     * @return void
     */
    public function WebtoolDoWorker()
    {
        $this->line("Generating worker...");

        $_[] = $this->call("queue:work", ["--once" => null, "--tries" => Common::GetEnv('WORKER_TRIES', 3), "--timeout" => Common::GetEnv('WORKER_TIMEOUT', 1200), "--memory" => Common::GetEnv('WORKER_MEMORY', 4096), "--delay" => Common::GetEnv('WORKER_DELAY', 3), "--sleep" => Common::GetEnv('WORKER_SLEEP', 3), "--no-ansi" => null, "--no-interaction" => null, "-vvv" => null]);
        // $this->call("webtool:fetch", ["--type" => "export-sync-files", "-vvv" => null]);
        
        Common::WaitForSec(5);
    }
}