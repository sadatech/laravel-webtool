<?php
namespace Sadatech\Webtool\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Sadatech\Webtool\Console\Traits\WorkerGenerator;
use Sadatech\Webtool\Console\Traits\CommonCLI;

class Webtool_CLI extends Command
{
    use WorkerGenerator, CommonCLI;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webtool:cli {com_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Webtool CLI';

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
        $_command_name = $this->argument('com_name');

        if ($_command_name == "worker")
        {
            $this->WebtoolDoWorker();
        }
        elseif ($_command_name == "jobs")
        {
            $this->WebtoolJobList();
        }
        else
        {
            $this->line("Undefined type commands.");
        }
    }
}
