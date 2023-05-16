<?php
namespace Sadatech\Webtool\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

trait Kernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \Sadatech\Webtool\Console\Commands\Console::class
    ];

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}