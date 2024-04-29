<?php
namespace Sadatech\Webtool\Console;

use Illuminate\Console\Scheduling\Schedule;
use Sadatech\Webtool\Console\Commands\WebtoolCommand;

trait ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        WebtoolCommand::class,
    ];

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function PackageMapConsole($app)
    {
        // $app->commands($this->commands);
        $this->load(__DIR__.'/Commands');
    }
}