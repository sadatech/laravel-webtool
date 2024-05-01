<?php
namespace Sadatech\Webtool\Console\Traits;

use Exception;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Sadatech\Webtool\Helpers\CommonHelper;
use Sadatech\Webtool\Helpers\WorkerHelper;
use Sadatech\Webtool\Traits\JobTrait;
use App\JobTrace;

trait WorkerTrait
{
    use JobTrait;

    /**
     * Define variables
     */
    private $buffer = [];

     /**
     * Do worker process
     */
    public function ConsoleDoWorker()
    {
        $this->buffer[] = $this->ConsoleWorkerArtisan();
        $this->buffer[] = $this->ConsoleWorkerValidate();
        $this->buffer[] = $this->ConsoleWorkerProcess();
        $this->buffer[] = $this->ConsoleWorkerClean();
    }

    /**
     * Call artisan command by total jobs
     */
    private function ConsoleWorkerArtisan()
    {
        if (Schema::hasTable('jobs'))
        {
            $job_totals = DB::table('jobs')->whereNull('reserved_at')->count();
            if ($job_totals > 0) $this->call("queue:work", ["--once" => null, "--tries" => CommonHelper::GetEnv('WORKER_TRIES', 1), "--timeout" => CommonHelper::GetEnv('WORKER_TIMEOUT', 900), "--memory" => CommonHelper::GetEnv('WORKER_MEMORY', 8192), "--delay" => CommonHelper::GetEnv('WORKER_DELAY', 15), "--sleep" => CommonHelper::GetEnv('WORKER_SLEEP', 5), "--no-ansi" => null, "--no-interaction" => null, "-vvv" => null]);
        }
    }

    /**
     * Call validate job traces done only
     * @error: ConsoleWorkerValidate::0x0000 (Export File not found)
     */
    private function ConsoleWorkerValidate()
    {
        $this->buffer['job_traces'] = JobTrace::whereIn('status', ['DONE'])->whereNull('results')->whereNull('url')->orderByDesc('created_at')->get();
        foreach ($this->buffer['job_traces'] as $job_trace)
        {
            $this->output->write("[".Carbon::now()."] Processing: Webtool\ConsoleWorkerValidate\n");
            try
            {
                JobTrace::where('id', $job_trace->id)->first()->update([
                    'other_notes' => 'results & url is null',
                    'url'         => NULL,
                    'results'     => NULL,
                    'status'      => 'FAILED',
                    'log'         => 'Export File not found, please re-run the job again. [ConsoleWorkerValidate::0x0000]',
                ]);

                $this->output->write("[".Carbon::now()."] Processed: Webtool\ConsoleWorkerValidate\n");
            }
            catch (Exception $exception)
            {
                JobTrace::where('id', $job_trace->id)->first()->update([
                    'status' => 'FAILED',
                    'log'    => $exception->getMessage(),
                ]);

                $this->output->write("[".Carbon::now()."] Failed: Webtool\ConsoleWorkerValidate\n");
            }
        }
    }

    /**
     * Call worker to process jobs
     */
    private function ConsoleWorkerProcess()
    {
        $this->buffer['job_traces'] = JobTrace::whereIn('status', ['DONE'])->whereNotNull('results')->whereNull('url')->orderByDesc('created_at')->get();
        $this->buffer['worker_queue'] = [];

        foreach ($this->buffer['job_traces'] as $job_trace)
        {
            // =============== ISOLATED ===============
            $traceCode = hash('sha1', $job_trace->id);
            $this->buffer['worker_queue_'.$traceCode] = $job_trace;
            // =============== ISOLATED ===============

            $this->output->write("[".Carbon::now()."] Processing: Webtool\ConsoleWorkerProcess\n");

            $this->buffer['worker_queue'][$traceCode]['results_url'] = $this->buffer['worker_queue_'.$traceCode]->results;
            $this->buffer['worker_queue'][$traceCode]['results_parse_url'] = parse_url($this->buffer['worker_queue'][$traceCode]['results_url']);
            $this->buffer['worker_queue'][$traceCode]['results_base_url'] = WorkerHelper::ValidateResultBaseURL($this->buffer['worker_queue'][$traceCode], $this->buffer['worker_queue'][$traceCode]['results_parse_url'], $this->buffer['worker_queue'][$traceCode]['results_url']);
            $this->buffer['worker_queue'][$traceCode]['results_local_path'] = WorkerHelper::GenerateLocalPath($this->buffer['worker_queue'][$traceCode]['results_base_url']);
            $this->buffer['worker_queue'][$traceCode]['results_cloud_path'] = WorkerHelper::GenerateCloudPath($this->buffer['worker_queue'][$traceCode]['results_local_path']);
            $this->buffer['worker_queue'][$traceCode]['results_local_url'] = parse_url($this->buffer['worker_queue'][$traceCode]['results_url']);
            $this->buffer['worker_queue'][$traceCode]['results_cloud_url'] = str_replace('https://'.CommonHelper::GetConfig('filesystems.disks.spaces.bucket').str_replace('https://', '.', CommonHelper::GetConfig('filesystems.disks.spaces.endpoint')), CommonHelper::GetConfig('filesystems.disks.spaces.url'), Storage::disk("spaces")->url($this->buffer['worker_queue'][$traceCode]['results_cloud_path']));
            if (!isset($this->buffer['worker_queue'][$traceCode]['results_local_url']['scheme'])) $this->buffer['worker_queue'][$traceCode]['results_base_url'] = 'http://'.request()->getHost().$this->buffer['worker_queue'][$traceCode]['results_local_path'];

            /**
             * 
             */
            try
            {
                $this->buffer['worker_queue'][$traceCode]['file_fetch'] = CommonHelper::FetchGetContent($this->buffer['worker_queue'][$traceCode]['results_base_url'], true, true);
                if ($this->buffer['worker_queue'][$traceCode]['file_fetch']['http_code'] == 200)
                {
                    try
                    {
                        $this->buffer['worker_queue'][$traceCode]['file_store'] = Storage::disk("spaces")->put($this->buffer['worker_queue'][$traceCode]['results_cloud_path'], $this->buffer['worker_queue'][$traceCode]['file_fetch']['data'], pack('H*', base_convert('011100000111010101100010011011000110100101100011', 2, 16)));
                    }
                    catch (Exception $exception)
                    {
                    }

                    // validate file stored
                    if ($this->buffer['worker_queue'][$traceCode]['file_store'])
                    {
                        if (isset($this->buffer['worker_queue'][$traceCode]['results_local_url']['host']))
                        {
                            if ($this->buffer['worker_queue'][$traceCode]['results_local_url']['host'] == @parse_url(CommonHelper::GetEnv('DATAPROC_URL', 'https://dataproc.sadata.id/'))['host'])
                            {
                                $this->MakeRequestNode('POST', 'remove', ['filename' => basename($this->buffer['worker_queue'][$traceCode]['results_cloud_path']), 'hash' => md5($this->buffer['worker_queue'][$traceCode]['results_cloud_path'])]);
                            }
                            else
                            {
                                if (Storage::disk('local')->exists($this->buffer['worker_queue'][$traceCode]['results_local_path'])) Storage::disk('local')->delete($this->buffer['worker_queue'][$traceCode]['results_local_path']);
                            }
                        }
                    }

                    JobTrace::where('id', $this->buffer['worker_queue_'.$traceCode]->id)->first()->update([
                        'explanation' => NULL,
                        'log'         => 'Local file deleted & File archived on CDN servers.',
                        'results'     => NULL,
                        'url'         => rawurldecode($this->buffer['worker_queue'][$traceCode]['results_cloud_url']),
                        'status'      => 'DONE',
                    ]);
                }

                $this->output->write("[".Carbon::now()."] Processed: Webtool\ConsoleWorkerProcess\n");
            }
            catch (Execption $exception)
            {
                JobTrace::where('id', $this->buffer['worker_queue_'.$traceCode]->id)->first()->update([
                    'other_notes' => "Failed to execute `ValidateTracejobAfterQueue` maybe error on `CommonHelper::FetchGetContent` or `FileStorage::disk(spaces)->put()` (" . $exception->getMessage() . ") [ConsoleWorkerProcess::0x0000]",
                ]);

                $this->output->write("[".Carbon::now()."] Failed: Webtool\ConsoleWorkerProcess\n");
            }
        }
    }

    /**
     * Call worker to clean expired jobs
     */
    private function ConsoleWorkerClean()
    {
        $this->buffer['job_traces'] = JobTrace::whereIn('status', ['DONE'])->whereNull('results')->whereNotNull('url')->orderByDesc('created_at')->get();

        foreach ($this->buffer['job_traces'] as $job_trace)
        {
            // =============== ISOLATED ===============
            $traceCode = hash('sha1', $job_trace->id);
            $this->buffer['worker_queue_'.$traceCode] = $job_trace;
            // =============== ISOLATED ===============

            $stream_date_now  = Carbon::now()->timestamp;
            $stream_date_file = Carbon::parse($this->buffer['worker_queue_'.$traceCode]->created_at)->addDays(CommonHelper::GetEnv('EXPORT_EXPIRED_DAYS', 3))->timestamp;

            try
            {
                if ($stream_date_file < $stream_date_now)
                {
                    $this->output->write("[".Carbon::now()."] Processing: Webtool\ConsoleWorkerClean\n");

                    $this->buffer['worker_queue'][$traceCode]['file_url']           = urldecode($this->buffer['worker_queue_'.$traceCode]->url);
                    $this->buffer['worker_queue'][$traceCode]['file_parse_url']     = parse_url($this->buffer['worker_queue_'.$traceCode]->url);
                    $this->buffer['worker_queue'][$traceCode]['results_cloud_path'] = str_replace($this->buffer['worker_queue'][$traceCode]['file_parse_url']['scheme'].'://'.$this->buffer['worker_queue'][$traceCode]['file_parse_url']['host'].'/', '/', $this->buffer['worker_queue'][$traceCode]['file_url']);
                    $this->buffer['worker_queue'][$traceCode]['results_local_path'] = str_replace('/export-data/'.str_replace('_', '-', CommonHelper::GetConfig('database.connections.mysql.database')), '', $this->buffer['worker_queue'][$traceCode]['results_cloud_path']);

                    // validate exists file
                    if (FileStorage::disk('spaces')->exists($this->buffer['worker_queue'][$traceCode]['results_cloud_path'])) FileStorage::disk('spaces')->delete($this->buffer['worker_queue'][$traceCode]['results_cloud_path']);
                    if (File::exists(public_path($this->buffer['worker_queue'][$traceCode]['results_local_path']))) File::delete(public_path($this->buffer['worker_queue'][$traceCode]['results_local_path']));

                    JobTrace::where('id', $this->buffer['worker_queue_'.$traceCode]->id)->first()->update([
                        'status' => 'DELETED',
                        'log'    => 'File may no longer be available due file has expired.',
                    ]);

                    $this->output->write("[".Carbon::now()."] Processed: Webtool\ConsoleWorkerClean\n");
                }
            }
            catch (Exception $exception)
            {
                JobTrace::where('id', $this->buffer['worker_queue_'.$traceCode]->id)->first()->update([
                    'log' => $exception->getMessage(),
                ]);

                $this->output->write("[".Carbon::now()."] Failed: Webtool\ConsoleWorkerClean\n");
            }
        }
    }
}