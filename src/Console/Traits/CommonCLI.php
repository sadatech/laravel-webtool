<?php
namespace Sadatech\Webtool\Console\Traits;

use App\JobTrace;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

trait CommonCLI
{
    public function WebtoolJobList()
    {
        $this->line(json_encode(DB::table('jobs')->whereNull('reserved_at')));
    }
}