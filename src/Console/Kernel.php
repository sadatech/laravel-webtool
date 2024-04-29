<?php
namespace Sadatech\Webtool\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Sadatech\Webtool\Console\Commands\WebtoolCommand;

trait Kernel 
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
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    // protected function schedule(Schedule $schedule)
    // {
    //     // $schedule->command('inspire')
    //     //          ->hourly();
    // }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function PackageMapConsole($app, $namespace)
    {
        // $app->commands($this->commands);
        $this->load(__DIR__.'/Commands');
    }
}