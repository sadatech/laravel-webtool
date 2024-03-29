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
    public $commands = [
        \Sadatech\Webtool\Console\Commands\Webtool_CLI::class
    ];
}