<?php
namespace Sadatech\Webtool\Console\Traits;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Sadatech\Webtool\Helpers\CommonHelper;
use App\JobTrace;

trait WorkerTrait
{
    /**
     * Define variables
     */
    private $buffer = [];

     /**
     * Do worker process
     */
    public function ConsoleDoWorker()
    {
        $this->buffer[] = $this->ConsoleDoWorkerArtisan();
        $this->buffer[] = $this->ConsoleDoWorkerValidate();
    }

    /**
     * Call artisan command by total jobs
     */
    private function ConsoleDoWorkerArtisan()
    {
        if (Schema::hasTable('jobs'))
        {
            $job_totals = DB::table('jobs')->whereNull('reserved_at')->count();
            if ($job_totals > 0) $this->output->write("[".Carbon::now()."] Processing: Webtool\ConsoleDoWorkerArtisan\n");
            if ($job_totals > 0) $this->call("queue:work", ["--once" => null, "--tries" => CommonHelper::GetEnv('WORKER_TRIES', 1), "--timeout" => CommonHelper::GetEnv('WORKER_TIMEOUT', 900), "--memory" => CommonHelper::GetEnv('WORKER_MEMORY', 8192), "--delay" => CommonHelper::GetEnv('WORKER_DELAY', 15), "--sleep" => CommonHelper::GetEnv('WORKER_SLEEP', 5), "--no-ansi" => null, "--no-interaction" => null, "-vvv" => null]);
            if ($job_totals > 0) $this->output->write("[".Carbon::now()."] Processed: Webtool\ConsoleDoWorkerArtisan\n");
        }
    }

    /**
     * Call validate job traces done only
     */
    private function ConsoleDoWorkerValidate()
    {
        $this->buffer['job_traces'] = JobTrace::whereIn('status', ['DONE'])->whereNull('results')->whereNull('url')->orderByDesc('created_at')->get();
        foreach ($this->buffer['job_traces'] as $job_trace)
        {
            $this->output->write("[".Carbon::now()."] Processing: Webtool\ValidateTracejobDoneOnly\n");
            try
            {
                JobTrace::where('id', $job_trace->id)->first()->update([
                    'other_notes' => 'results & url is null',
                    'url'         => NULL,
                    'results'     => NULL,
                    'status'      => 'FAILED',
                    'log'         => 'Failed to generate export file.',
                ]);

                $this->output->write("[".Carbon::now()."] Processed: Webtool\ValidateTracejobDoneOnly\n");
            }
            catch (Exception $exception)
            {
                JobTrace::where('id', $job_trace->id)->first()->update([
                    'status' => 'FAILED',
                    'log'    => $exception->getMessage(),
                ]);

                $this->output->write("[".Carbon::now()."] Failed: Webtool\ValidateTracejobDoneOnly\n");
            }
        }
    }
}