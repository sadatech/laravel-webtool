<?php
namespace Sadatech\Webtool\Traits;

use Exception;
use Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage as FileStorage;
use Illuminate\Support\Facades\File;
use Sadatech\Webtool\Helpers\Webtool as WebtoolHelper;
use Carbon\Carbon;
use App\JobTrace;
use App\Helper\ConfigHelper;

trait ConsoleCommand
{

    /**
     * Public function
     */
    public function WebtoolDoWorker()
    {
        $this->call("webtool:fetch", ["--type" => "do-command"]);
        $this->call("queue:work", ["--once" => null, "--tries" => $this->WebtoolEnv('WORKER_TRIES', 1), "--timeout" => $this->WebtoolEnv('WORKER_TIMEOUT', 1200), "--memory" => $this->WebtoolEnv('WORKER_MEMORY', 2048), "--delay" => $this->WebtoolEnv('WORKER_DELAY', 3), "--sleep" => $this->WebtoolEnv('WORKER_SLEEP', 3), "--no-ansi" => null, "--no-interaction" => null, "-vvv" => null]);
        $this->call("webtool:fetch", ["--type" => "export-sync-files", "-vvv" => null]);
        sleep(15);
    }

    /**
     * Private functions
     */
    public function WebtoolTotalJob()
    {
        return DB::table('jobs')->whereNull('reserved_at')->count();
    }

    public function WebtoolEnv($env_arg, $env_opt = null)
    {
        return env($env_arg, $env_opt);
    }

    public function WebtoolValidateSyncFiles()
    {
        $jobtraces = JobTrace::whereIn('status', ['FAILED'])->where('explanation', 'LIKE', '%Permission denied%')->orderByDesc('created_at')->get();

        foreach ($jobtraces as $tracejob)
        {
            $ndate = Carbon::now()->timestamp;
            $mdate = Carbon::parse($tracejob->created_at)->addDays(env('EXPORT_EXPIRED_DAYS', 3))->timestamp;
            $localfile = str_replace('https://'.request()->getHost().'/', '/', $tracejob->results);
            $localfile = str_replace('https','---123---', str_replace('http','---123---', $localfile));
            $localfile = str_replace('---123---', 'https', $localfile);
            $localfile = str_replace(public_path(''), null, $localfile);
            $cloudfile = "export-data/".str_replace('//', '/', str_replace('_', '-', ConfigHelper::GetConfig("DB_DATABASE"))."/".$localfile);
            $hashfile  = hash('md5', $tracejob->results);

            if ($mdate < $ndate)
            {
                if (File::exists(public_path($localfile)))
                {
                    File::delete(public_path($localfile));
                }

                if (FileStorage::disk("spaces")->exists($cloudfile))
                {
                    FileStorage::disk("spaces")->delete($cloudfile);
                }

                JobTrace::where('id', $tracejob->id)->first()->update([
                    'status' => 'DELETED',
                    'log' => 'File may no longer be available due to an export error or the file has expired.',
                ]);
            }
            else
            {
                if (File::exists(public_path($localfile)))
                {
                    if (!FileStorage::disk("spaces")->exists($cloudfile))
                    {
                        JobTrace::where('id', $tracejob->id)->first()->update([
                            'status' => 'PROCESSING',
                        ]);
                    }
                }
                else
                {
                    if (!$tracejob->url)
                    {
                        JobTrace::where('id', $tracejob->id)->first()->update([
                            'status' => 'DELETED',
                            'log' => 'File may no longer be available due to an export error or the file has expired.',
                        ]);
                    }
                }
            }
        }
    }

    public function WebtoolExportSyncFiles()
    {
        $jobtraces = JobTrace::whereIn('status', ['DONE'])->orderByDesc('created_at')->get();
        $jobfilter = [];

        foreach ($jobtraces as $tracejob)
        {
            $ndate = Carbon::now()->timestamp;
            $mdate = Carbon::parse($tracejob->created_at)->addDays(env('EXPORT_EXPIRED_DAYS', 3))->timestamp;
            $localfile = str_replace('https://'.request()->getHost().'/', '/', $tracejob->results);
            $localfile = str_replace('https','---123---', str_replace('http','---123---', $localfile));
            $localfile = str_replace('---123---', 'https', $localfile);
            $localfile = str_replace(public_path(''), null, $localfile);
            $cloudfile = "export-data/".str_replace('//', '/', str_replace('_', '-', ConfigHelper::GetConfig("DB_DATABASE"))."/".$localfile);
            $hashfile  = hash('md5', $tracejob->results);

            if ($mdate < $ndate)
            {
                if (File::exists(public_path($localfile)))
                {
                    File::delete(public_path($localfile));
                }

                if (FileStorage::disk("spaces")->exists($cloudfile))
                {
                    FileStorage::disk("spaces")->delete($cloudfile);
                }

                JobTrace::where('id', $tracejob->id)->first()->update([
                    'status' => 'DELETED',
                    'log' => 'File may no longer be available due to an export error or the file has expired.',
                ]);
            }
            else
            {
                if (File::exists(public_path($localfile)))
                {
                    if (!FileStorage::disk("spaces")->exists($cloudfile))
                    {
                        JobTrace::where('id', $tracejob->id)->first()->update([
                            'explanation' => 'Please wait a moment, file is under sync to CDN servers.',
                            'log' => 'Please wait a moment, file is under sync to CDN servers.',
                            'status' => 'PROCESSING',
                        ]);

                        // handler read file
                        try
                        {
                            $filereader = fopen(public_path($localfile), 'r+');
                            if (FileStorage::disk("spaces")->put($cloudfile, $filereader, "public"))
                            {
                                File::delete(public_path($localfile));
                                $cloudurl = str_replace('https://'.config('filesystems.disks.spaces.bucket').str_replace('https://', '.', config('filesystems.disks.spaces.endpoint')), config('filesystems.disks.spaces.url'), FileStorage::disk("spaces")->url($cloudfile));
                                JobTrace::where('id', $tracejob->id)->first()->update([
                                    'explanation' => 'File archived on CDN servers.',
                                    'log' => 'File archived on CDN servers.',
                                    'url' => $cloudurl,
                                    'status' => 'DONE',
                                ]);
                            }
                            else
                            {
                                JobTrace::where('id', $tracejob->id)->first()->update([
                                    'explanation' => 'Failed sync to CDN servers.',
                                    'log' => 'Failed sync to CDN servers.',
                                    'status' => 'DONE',
                                ]);
                            }
                        }
                        catch (Exception $ex)
                        {
                            JobTrace::where('id', $tracejob->id)->first()->update([
                                'explanation' => $ex->getMessage(),
                                'log' => $ex->getMessage(),
                                'status' => 'FAILED',
                            ]);
                        }
                    }
                    else
                    {
                        File::delete(public_path($localfile));
                        $cloudurl = str_replace('https://'.config('filesystems.disks.spaces.bucket').str_replace('https://', '.', config('filesystems.disks.spaces.endpoint')), config('filesystems.disks.spaces.url'), FileStorage::disk("spaces")->url($cloudfile));
                        JobTrace::where('id', $tracejob->id)->first()->update([
                            'explanation' => 'File archived on CDN servers.',
                            'log' => 'File archived on CDN servers.',
                            'url' => $cloudurl,
                            'status' => 'DONE',
                        ]);
                    }
                }
                else
                {
                    if (!$tracejob->url)
                    {
                        JobTrace::where('id', $tracejob->id)->first()->update([
                            'status' => 'DELETED',
                            'log' => 'File may no longer be available due to an export error or the file has expired.',
                        ]);
                    }
                }
            }
        }
    }

    public function WebtoolDoCommand()
    {
        $baseconf = base_path('webtool'.DIRECTORY_SEPARATOR.'schedule.php');

        /**
         * Validate
         */
        if (!is_dir(base_path('webtool'))) mkdir(base_path('webtool'));
        if (!is_dir(base_path('app'.DIRECTORY_SEPARATOR.'Webtool'))) mkdir(base_path('app'.DIRECTORY_SEPARATOR.'Webtool'));
        if (!is_dir(base_path('app'.DIRECTORY_SEPARATOR.'Webtool'.DIRECTORY_SEPARATOR.'Commands'))) mkdir(base_path('app'.DIRECTORY_SEPARATOR.'Webtool'.DIRECTORY_SEPARATOR.'Commands'));
        if (!is_dir(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'))) mkdir(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'));
        if (!is_dir(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'.DIRECTORY_SEPARATOR.'data'))) mkdir(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'.DIRECTORY_SEPARATOR.'data'));
        if (!is_file($baseconf))
        {
            $default_conf = "";
            $default_conf .= "<?php" . "\n";
            $default_conf .= "/**" . "\n";
            $default_conf .= " *" . "\n";
            $default_conf .= " * Default config file webtool command schedule" . "\n";
            $default_conf .= " *" . "\n";
            $default_conf .= " */" . "\n";
            $default_conf .= "use App\Webtool\Commands\ExampleCommand;" . "\n";
            $default_conf .= "" . "\n";
            $default_conf .= "return [" . "\n";
            $default_conf .= "\t" . "// [null, 'reporting', 'generate:facing'], # Run artisan after uploaded, not trying if success running" . "\n";
            $default_conf .= "\t" . "// ['2020-01-01 00:00', 'reporting', 'generate:facing'], # Run artisan by date format, not trying if success running" . "\n";
            $default_conf .= "\t" . "// ['2020-01-01 00:00', 'reporting', ExampleCommand::class], # Run objec class by date format, not trying if success running" . "\n";
            $default_conf .= "];" . "\n";

            @file_put_contents($baseconf, $default_conf);
        }
        if (!is_file(base_path('app'.DIRECTORY_SEPARATOR.'Webtool'.DIRECTORY_SEPARATOR.'Commands'.DIRECTORY_SEPARATOR.'ExampleCommand.php')))
        {
            $default_com = "";
            $default_com .= "<?php" . "\n";
            $default_com .= "namespace App\Webtool\Commands;" . "\n";
            $default_com .= "/**" . "\n";
            $default_com .= " *" . "\n";
            $default_com .= " * Default Example Command Webtool" . "\n";
            $default_com .= " *" . "\n";
            $default_com .= " */" . "\n";
            $default_com .= "" . "\n";
            $default_com .= "class ExampleCommand" . "\n";
            $default_com .= "{" . "\n";
            $default_com .= "\t" . "public function handler()" . "\n";
            $default_com .= "\t" . "{" . "\n";
            $default_com .= "\t\t" . "return \"Webtool Command Work!\";" . "\n";
            $default_com .= "\t" . "}" . "\n";
            $default_com .= "}" . "\n";

            @file_put_contents(base_path('app'.DIRECTORY_SEPARATOR.'Webtool'.DIRECTORY_SEPARATOR.'Commands'.DIRECTORY_SEPARATOR.'ExampleCommand.php'), $default_com);
        }

        /**
         * 
         */
        $confObj = require($baseconf);
        foreach ($confObj as $confData)
        {
            $confObjId = hash('md5', $confData[0].$confData[1].$confData[2]);

            // write running process
            if (!file_exists(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'proc_'.$confObjId)))
            {
                file_put_contents(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'proc_'.$confObjId), 'run');

                if (!file_exists(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$confObjId)))
                {
                    /**
                     * Validate is now or later
                     */
                    if (is_null($confData[0]))
                    {
                        $confAllowRun = 1;
                    }
                    else
                    {
                        if (Carbon::now()->format('Y-m-d H:i') == $confData[0])
                        {
                            $confAllowRun = 1;
                        }
                        else
                        {
                            $confAllowRun = 0;
                        }
                    }
    
                    if ($confAllowRun)
                    {
                        /**
                         * Validate host
                         */
                        if ($confData[1] == $this->WebtoolEnv('DB_DATABASE'))
                        {
                            /**
                             * Validate command
                             */
                            if (class_exists($confData[2]))
                            {
                                try
                                {
                                    $confClassLoader = (new $confData[2]);
                                    if (method_exists($confClassLoader, 'handler'))
                                    {
                                        $confClassReturn = eval("return '".$confClassLoader->handler()."';");
                                        $this->line('['.Carbon::now().']['.$confObjId.']['.$confData[0].']['.$confData[1].']['.$confData[2].'] return: '.$confClassReturn);
                                        file_put_contents(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$confObjId), $confClassReturn);
                                    }
                                    else
                                    {
                                        file_put_contents(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$confObjId.'.err_log'), 'return error: no valid method `handler()` on `'.basename($confData[2]).'`');
                                        $this->error('['.Carbon::now().']['.$confObjId.']['.$confData[0].']['.$confData[1].']['.$confData[2].'] return error: no valid method `handler()` on `'.basename($confData[2]).'`');
                                    }
                                }
                                catch (Exception $th)
                                {
                                    file_put_contents(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$confObjId.'.err_log'), 'return error: ' . $th->getMessage() . ' on ' . basename($th->getFile()) . ' line ' . $th->getLine());
                                    $this->error('['.Carbon::now().']['.$confObjId.']['.$confData[0].']['.$confData[1].']['.$confData[2].'] return error: ' . $th->getMessage() . ' on ' . basename($th->getFile()) . ' line ' . $th->getLine());
                                }
                            }
                            else
                            {
                                try
                                {
                                    $confClassReturn = WebtoolHelper::DoCommand(['/usr/local/bin/webtool', 'app', 'exec', request()->getHost(), 'artisan', $confData[2]]);
                                    $this->line('['.Carbon::now().']['.$confObjId.']['.$confData[0].']['.$confData[1].']['.$confData[2].'] return: '.$confClassReturn);
                                    file_put_contents(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$confObjId), $confClassReturn);
                                }
                                catch(Exception $th)
                                {
                                    file_put_contents(storage_path('app'.DIRECTORY_SEPARATOR.'webtool'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$confObjId.'.err_log'), 'return error: artisan error code ' . $th->getCode());
                                    $this->error('['.Carbon::now().']['.$confObjId.']['.$confData[0].']['.$confData[1].']['.$confData[2].'] return error: artisan error code ' . $th->getCode());
                                }
                
                            }
                        }
                        else
                        {
                            $this->error('['.Carbon::now().']['.$confObjId.']['.$confData[0].']['.$confData[1].']['.$confData[2].'] return error: no valid project selected.');
                        }
                    }
                    else
                    {
                        $this->error('['.Carbon::now().']['.$confObjId.']['.$confData[0].']['.$confData[1].']['.$confData[2].'] return error: no valid argument started.');
                    }
                }
                else
                {
                    $this->error('['.Carbon::now().']['.$confObjId.']['.$confData[0].']['.$confData[1].']['.$confData[2].'] return error: already started.');
                }
            }
        }
    }
}
