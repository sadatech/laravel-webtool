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
    protected $signature = 'webtool:cli {cmd_args}';

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cmd_name = $this->argument('com_name');

        if ($cmd_name == 'worker')
        {
            // 
        }
        elseif ($cmd_name == 'jobs')
        {
            // 
        }
        elseif ($cmd_name == 'reset-dump')
        {
            // 
        }
        else
        {
            $this->line("Undefined type commands.");
        }
    }
}