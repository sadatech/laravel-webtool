<?php
namespace Sadatech\Webtool\Console\Traits;

use Illuminate\Support\Facades\DB;

trait JobTrait
{
    /**
     * Get the job list
     */
    public function ConsoleJobList()
    {
        $this->line(json_encode(DB::table('jobs')->whereNull('reserved_at')->get()));
    }

    /**
     * 
     */
}