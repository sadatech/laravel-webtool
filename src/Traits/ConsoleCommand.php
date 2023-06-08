<?php
namespace Sadatech\Webtool\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage as FileStorage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\JobTrace;
use App\Helper\ConfigHelper;

trait ConsoleCommand
{
    /**
     * Private functions
     */
    public function WebtoolTotalJob()
    {
        return DB::table('jobs')->whereNull('reserved_at')->count();
    }

    public function WebtoolEnv($env_arg)
    {
        return env($env_arg, '');
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
                        if (FileStorage::disk("spaces")->put($cloudfile, fopen(public_path($localfile), 'r+'), "public"))
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

    }
}