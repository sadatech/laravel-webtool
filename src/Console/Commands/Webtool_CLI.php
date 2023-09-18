<?php
namespace Sadatech\Webtool\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Webtool_CLI extends Command
{
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
    }
}
