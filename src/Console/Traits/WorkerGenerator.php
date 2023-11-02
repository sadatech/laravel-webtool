<?php
namespace Sadatech\Webtool\Console\Traits;

use Exception;
use App\JobTrace;
use Carbon\Carbon;
use Sadatech\Webtool\Helpers\Common;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage as FileStorage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Sadatech\Webtool\Traits\ExtendedJob;

trait WorkerGenerator
{
    use ExtendedJob;

    /**
     * Generate Export Files
     * 
     * @return void
     */
    public function WebtoolDoWorker()
    {
        $_[] = $this->ExecuteArtisanQueue();
        $_[] = $this->ValidateTracejobDoneOnly();
        $_[] = $this->ValidateTracejobAfterQueue();
    }

    /**
     * Call artisan command by total jobs
     * 
     * @return void
     */
    private function ExecuteArtisanQueue()
    {
        if (Schema::hasTable('jobs'))
        {
            $job_totals = DB::table('jobs')->whereNull('reserved_at')->count();

            if ($job_totals > 0)
            {
                $this->call("queue:work", ["--once" => null, "--tries" => Common::GetEnv('WORKER_TRIES', 1), "--timeout" => Common::GetEnv('WORKER_TIMEOUT', 900), "--memory" => Common::GetEnv('WORKER_MEMORY', 8192), "--delay" => Common::GetEnv('WORKER_DELAY', 15), "--sleep" => Common::GetEnv('WORKER_SLEEP', 5), "--no-ansi" => null, "--no-interaction" => null, "-vvv" => null]);
            }
        }
    }

    /**
     * Validate TraceJob Done Only
     * 
     * @return void
     */
    private function ValidateTracejobDoneOnly()
    {
        $job_traces = JobTrace::whereIn('status', ['DONE'])->whereNull('results')->whereNull('url')->orderByDesc('created_at')->get();

        foreach ($job_traces as $job_trace)
        {
            try
            {
                JobTrace::where('id', $tracejob->id)->first()->update([
                    'other_notes' => 'results & url is null',
                    'url'         => NULL,
                    'results'     => NULL,
                    'status'      => 'FAILED',
                    'log'         => 'Failed to generate export file.',
                ]);
            }
            catch (Exception $exception)
            {
                JobTrace::where('id', $job_trace->id)->first()->update([
                    'status' => 'FAILED',
                    'log'    => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Validate Tracejob After Queue
     * 
     * @return void
     */
    private function ValidateTracejobAfterQueue()
    {
        $job_traces = JobTrace::whereIn('status', ['DONE'])->whereNotNull('results')->whereNull('url')->orderByDesc('created_at')->get();

        foreach ($job_traces as $job_trace)
        {
            try
            {
                // define variables
                $stream_export_file = Common::FetchGetContent($job_trace->results);
                $stream_local_path  = str_replace('https://'.request()->getHost().'/', '/', $job_trace->results);
                $stream_local_path  = str_replace(public_path(''), null, $stream_local_path);
                $stream_local_path  = str_replace('https://dataproc.sadata.id/', '/', $stream_local_path);
                $stream_cloud_path  = "export-data/".str_replace('//', '/', str_replace('_', '-', Common::GetConfig("database.connections.mysql.database"))."/".$stream_local_path);

                // upload to spaces
                if (FileStorage::disk("spaces")->put($stream_cloud_path, $stream_export_file, "public"))
                {
                    $stream_cloud_url = str_replace('https://'.Common::GetConfig('filesystems.disks.spaces.bucket').str_replace('https://', '.', Common::GetConfig('filesystems.disks.spaces.endpoint')), Common::GetConfig('filesystems.disks.spaces.url'), FileStorage::disk("spaces")->url($stream_cloud_path));

                    // update job traces
                    JobTrace::where('id', $job_trace->id)->first()->update([
                        'explanation' => NULL,
                        'log'         => NULL,
                        'results'     => NULL,
                        'url'         => $stream_cloud_url,
                        'other_notes' => 'File archived on CDN servers.',
                        'status'      => 'DONE',
                    ]);

                    // remove from node exporter
                    $this->MakeRequestNode('POST', 'remove', ['filename' => basename($stream_cloud_path), 'hash' => md5($stream_cloud_path)]);
                }
                else
                {
                    JobTrace::where('id', $tracejob->id)->first()->update([
                        'status' => 'FAILED',
                        'log'    => 'Failed sync to CDN servers.',
                    ]);
                }
            }
            catch (Exception $exception)
            {
                JobTrace::where('id', $job_trace->id)->first()->update([
                    'status' => 'FAILED',
                    'log'    => $exception->getMessage(),
                ]);
            }
        }
    }

}