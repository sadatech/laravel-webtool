<?php
namespace Sadatech\Webtool\Console\Commands;

use Illuminate\Console\Command;
use Sadatech\Webtool\Console\Traits\WorkerTrait;
use Sadatech\Webtool\Console\Traits\JobTrait;

class WebtoolCommand extends Command
{
    /**
     * Use trait
     */
    use WorkerTrait, JobTrait;

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
            $this->consoleDoWorker();
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