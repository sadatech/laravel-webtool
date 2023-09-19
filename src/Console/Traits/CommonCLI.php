<?php
namespace Sadatech\Webtool\Console\Traits;

use App\JobTrace;
use Carbon\Carbon;

trait CommonCLI
{
    public function WebtoolJobList()
    {
        $this->line(DB::table('jobs')->whereNull('reserved_at'));
    }
}