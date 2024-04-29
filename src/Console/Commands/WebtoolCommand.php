<?php
namespace Sadatech\Webtool\Console\Commands;

use Illuminate\Console\Command;

class WebtoolCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webtool:cmd {cmd_args}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Webtool Command Line Interface';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
}