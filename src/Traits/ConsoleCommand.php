<?php
namespace Sadatech\Webtool\Traits;
use Illuminate\Support\Facades\DB;

trait ConsoleCommand
{
    /**
     * Private functions
     */
    public function WebtoolTotalJob()
    {
        return DB::table('jobs')->whereNull('reserved_at')->count();
    }

    public function WebtoolEnv()
    {}

    public function WebtoolExportSyncFiles()
    {}
}