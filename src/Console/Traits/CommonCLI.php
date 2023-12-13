<?php
namespace Sadatech\Webtool\Console\Traits;

use App\JobTrace;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

trait CommonCLI
{
    public function WebtoolJobList()
    {
        $this->line(json_encode(DB::table('jobs')->whereNull('reserved_at')->get()));
    }

    public function WebtoolResetDump()
    {
        $this->output->write("[".Carbon::now()."] Processing: Webtool\WebtoolResetDump\n");
        $files = glob(Storage::disk('local')->path('webtool'.DIRECTORY_SEPARATOR.'dump') . DIRECTORY_SEPARATOR . '*');
        $threshold = strtotime('-1 second');

        foreach ($files as $file)
        {
            if (is_file($file))
            {
                if ($threshold >= filemtime($file))
                {
                    Storage::disk('local')->delete('webtool'.DIRECTORY_SEPARATOR.'dump'.DIRECTORY_SEPARATOR.basename($file));
                }
            }
        }
        $this->output->write("[".Carbon::now()."] Processed: Webtool\WebtoolResetDump\n");
    }
}