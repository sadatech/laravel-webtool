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
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use League\Flysystem\Adapter\Local as AdapterLocal;

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
        $_[] = $this->ValidateTracejobExpire();
    }

    /**
     * Call artisan command by total jobs
     * 
     * @return void
     */
    private function ExecuteArtisanQueue()
    {
        if (Common::GetEnv('QUEUE_DRIVER') == 'redis')
        {
            $this->call("queue:work", ["--once" => null, "--tries" => Common::GetEnv('WORKER_TRIES', 1), "--timeout" => Common::GetEnv('WORKER_TIMEOUT', 3600), "--memory" => Common::GetEnv('WORKER_MEMORY', 16384), "--delay" => Common::GetEnv('WORKER_DELAY', 15), "--sleep" => Common::GetEnv('WORKER_SLEEP', 5), "--no-ansi" => null, "--no-interaction" => null, "-vvv" => null]);
        }
        else
        {
            if (Schema::hasTable('jobs'))
            {
                $job_totals = DB::table('jobs')->whereNull('reserved_at')->count();
    
                if ($job_totals > 0)
                {
                    $this->call("queue:work", ["--once" => null, "--tries" => Common::GetEnv('WORKER_TRIES', 1), "--timeout" => Common::GetEnv('WORKER_TIMEOUT', 3600), "--memory" => Common::GetEnv('WORKER_MEMORY', 16384), "--delay" => Common::GetEnv('WORKER_DELAY', 15), "--sleep" => Common::GetEnv('WORKER_SLEEP', 5), "--no-ansi" => null, "--no-interaction" => null, "-vvv" => null]);
                }
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
            $stream_base_url    = $job_trace->results;
            $this->output->write("[".Carbon::now()."] Processing: Webtool\ValidateTracejobAfterQueue\n");
            $stream_parse_url   = parse_url($stream_base_url);
            if (isset($stream_parse_url['scheme']))
            {
                if (Common::GetEnv('DATAPROC_URL') == '' || is_null(Common::GetEnv('DATAPROC_URL')))
                {
                    if ($stream_parse_url['host'] !== @parse_url(Common::GetEnv('DATAPROC_URL', 'https://dataproc.sadata.id/'))['host'])
                    {
                        $stream_base_url = str_replace($stream_parse_url['host'], request()->getHost(), $job_trace->results);
                    }
                }
                else
                {
                    $stream_base_url = $stream_base_url;
                }
            }
            else
            {
                $stream_base_url = $stream_base_url;
            }
            $stream_local_path  = str_replace('https://'.request()->getHost().'/', '/', $stream_base_url);
            $stream_local_path  = str_replace(public_path(''), null, $stream_local_path);
            $stream_local_path  = str_replace('https://dataproc.sadata.id/', '/', $stream_local_path);
            $stream_cloud_path  = "export-data/".str_replace('//', '/', str_replace('_', '-', Common::GetConfig("database.connections.mysql.database"))."/".$stream_local_path);
            $stream_local_url   = parse_url($stream_base_url);
            if (!isset($stream_local_url['scheme']))
            {
                $stream_base_url = 'http://'.request()->getHost().$stream_local_path;
            }

            try
            {
                $tmpfilename = storage_path().DIRECTORY_SEPARATOR.rand(0, time());
                $stream_export_file = Common::FetchGetContent($stream_base_url, true, true);
                
                if ($stream_export_file['http_code'] == 200)
                {
                    try
                    {
                        file_put_contents($tmpfilename, $stream_export_file['data']);
                        $mountManager = new MountManager([
                            's3' => FileStorage::disk('spaces')->getDriver(),
                            'local' => new Filesystem(new AdapterLocal(storage_path())),
                        ]);
                        if (!FileStorage::disk('spaces')->exists($stream_cloud_path)) $mountManager->copy('local://' . basename($tmpfilename), 's3://' . $stream_cloud_path);
                        unlink($tmpfilename);
                    }
                    catch (\Exception $except)
                    {
                        unlink($tmpfilename);
                        throw new \Error($except);
                    }

                    if (FileStorage::disk('spaces')->setVisibility($stream_cloud_path, 'public'))
                    {
                        $stream_cloud_url = str_replace('https://'.Common::GetConfig('filesystems.disks.spaces.bucket').str_replace('https://', '.', Common::GetConfig('filesystems.disks.spaces.endpoint')), Common::GetConfig('filesystems.disks.spaces.url'), FileStorage::disk("spaces")->url($stream_cloud_path));
                        $stream_cloud_url = str_replace('https://'.str_replace('https://', '.', Common::GetConfig('filesystems.disks.spaces.endpoint')), Common::GetConfig('filesystems.disks.spaces.url'), FileStorage::disk("spaces")->url($stream_cloud_path));

                        if (isset($stream_local_url['host']))
                        {
                            if ($stream_local_url['host'] == @parse_url(Common::GetEnv('DATAPROC_URL', 'https://dataproc.sadata.id/'))['host'])
                            {
                                $this->MakeRequestNode('POST', 'remove', ['filename' => basename($stream_cloud_path), 'hash' => md5($stream_cloud_path)]);
                            }
                            else
                            {
                                if (File::exists(public_path($stream_local_path)))
                                {
                                    File::delete(public_path($stream_local_path));
                                }
                            }
                        }

                        JobTrace::where('id', $job_trace->id)->first()->update([
                            'explanation' => NULL,
                            'log'         => 'Local file deleted & File archived on CDN servers.',
                            'results'     => NULL,
                            'url'         => rawurldecode($stream_cloud_url),
                            'status'      => 'DONE',
                        ]);

                        $this->output->write("[".Carbon::now()."] Processed: Webtool\ValidateTracejobAfterQueue\n");
                    }
                }
                else
                {
                    JobTrace::where('id', $job_trace->id)->first()->update([
                        'status'  => 'FAILED',
                        'log'     => "Worker `stream_export_file` return error (" . $stream_export_file['message'] . ") maybe error on `Common::FetchGetContent(".$stream_base_url.")` or `FileStorage::disk(spaces)->put()`",
                    ]);

                    $this->output->write("[".Carbon::now()."] Failed: Webtool\ValidateTracejobAfterQueue\n");
                }

            }
            catch (Exeception $exception)
            {
                JobTrace::where('id', $job_trace->id)->first()->update([
                    'log' => "Failed to execute `ValidateTracejobAfterQueue` maybe error on `Common::FetchGetContent` or `FileStorage::disk(spaces)->put()` (" . $exception->getMessage() . ")",
                ]);

                $this->output->write("[".Carbon::now()."] Failed: Webtool\ValidateTracejobAfterQueue\n");
            }
        }
    }

    /**
     * Validate TraceJob after 3 day (expired)
     * 
     * @return void
     */
    private function ValidateTracejobExpire()
    {
        $job_traces = JobTrace::whereIn('status', ['DONE'])->whereNull('results')->whereNotNull('url')->orderByDesc('created_at')->get();

        foreach ($job_traces as $job_trace)
        {
            $stream_date_now  = Carbon::now()->timestamp;
            $stream_date_file = Carbon::parse($job_trace->created_at)->addDays(Common::GetEnv('EXPORT_EXPIRED_DAYS', 3))->timestamp;

            try
            {
                if ($stream_date_file < $stream_date_now)
                {
                    $this->output->write("[".Carbon::now()."] Processing: Webtool\ValidateTracejobExpire\n");
                    $stream_base_url   = urldecode($job_trace->url);
                    $stream_parse_url  = parse_url($job_trace->url);
                    $stream_cloud_path = str_replace($stream_parse_url['scheme'].'://'.$stream_parse_url['host'].'/', '/', $stream_base_url);
                    $stream_local_path = str_replace('/export-data/'.str_replace('_', '-', Common::GetConfig('database.connections.mysql.database')), '', $stream_cloud_path);

                    // validate exists file
                    if (FileStorage::disk('spaces')->exists($stream_cloud_path))
                    {
                        FileStorage::disk('spaces')->delete($stream_cloud_path);
                    }
                    if (File::exists(public_path($stream_local_path)))
                    {
                        File::delete(public_path($stream_local_path));
                    }

                    JobTrace::where('id', $job_trace->id)->first()->update([
                        'status' => 'DELETED',
                        'log'    => 'File may no longer be available due file has expired.',
                    ]);

                    $this->output->write("[".Carbon::now()."] Processed: Webtool\ValidateTracejobExpire\n");
                }
            }
            catch (Exception $exception)
            {
                JobTrace::where('id', $job_trace->id)->first()->update([
                    'log' => $exception->getMessage(),
                ]);

                $this->output->write("[".Carbon::now()."] Failed: Webtool\ValidateTracejobExpire\n");
            }
        }
    }

    /**
     * Remove Cache Files
     */
    private function RemoveCacheFiles()
    {
        $this->output->write("[".Carbon::now()."] Processing: Webtool\RemoveCacheFiles\n");
        Common::tempRemoveCache(sys_get_temp_dir().DIRECTORY_SEPARATOR.'.wtenc'.DIRECTORY_SEPARATOR);
        Common::tempRemoveCache(sys_get_temp_dir().DIRECTORY_SEPARATOR.'.wtval'.DIRECTORY_SEPARATOR, "-not -newermt '-30 seconds'");
        $this->output->write("[".Carbon::now()."] Processed: Webtool\RemoveCacheFiles\n");
    }
}