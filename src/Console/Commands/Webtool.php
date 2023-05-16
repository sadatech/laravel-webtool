<?php
namespace Sadatech\Webtool\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage as FileStorage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\JobTrace;
use App\Helper\ConfigHelper;

class Webtool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webtool:fetch {--type=} {--args=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Webtool Connector';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = @$this->option('type') ?? NULL;
        $args = @$this->option('args') ?? NULL;
        
        if ($type == "total-job")
        {
            $this->line(DB::table('jobs')->whereNull('reserved_at')->count());
        } else
        if ($type == "env")
        {
            if ($args)
            {
                $this->line(env($args));
            }
        } else
        if ($type == "export-sync-files")
        {
            $jobtraces = JobTrace::whereIn('status', ['DONE'])->orderByDesc('created_at')->get();
            $jobfilter = [];

            foreach ($jobtraces as $tracejob)
            {
                $ndate = Carbon::now()->timestamp;
                $mdate = Carbon::parse($tracejob->created_at)->addDays(env('EXPORT_EXPIRED_DAYS', 3))->timestamp;
                $localfile = str_replace('https://'.request()->getHost().'/', '/', $tracejob->results);
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
        else
        {
            $this->line("undefined type commands ( env | total-job | export-sync-files ).");
        }
    }
}
