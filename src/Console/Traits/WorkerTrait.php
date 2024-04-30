<?php
namespace Sadatech\Webtool\Console\Traits;

use Illuminate\Support\Facades\Schema;
use Sadatech\Webtool\Helpers\CommonHelper;

trait WorkerTrait
{
    /**
     * Define variables
     */
    private $buffer = [];

     /**
     * Do worker process
     */
    public function consoleDoWorker()
    {
        $this->buffer[] = $this->consoleDoWorkerArtisan();
    }

    /**
     * Call artisan command by total jobs
     */
    private function consoleDoWorkerArtisan()
    {
        if (Schema::hasTable('jobs'))
        {
            $job_totals = DB::table('jobs')->whereNull('reserved_at')->count();
            if ($job_totals > 0) $this->call("queue:work", ["--once" => null, "--tries" => CommonHelper::GetEnv('WORKER_TRIES', 1), "--timeout" => CommonHelper::GetEnv('WORKER_TIMEOUT', 900), "--memory" => CommonHelper::GetEnv('WORKER_MEMORY', 8192), "--delay" => CommonHelper::GetEnv('WORKER_DELAY', 15), "--sleep" => CommonHelper::GetEnv('WORKER_SLEEP', 5), "--no-ansi" => null, "--no-interaction" => null, "-vvv" => null]);
        }
    }
}