<?php
namespace Sadatech\Webtool\Console\Commands;

use Illuminate\Console\Command as IlluminateCommand;
use Illuminate\Support\Facades\Artisan;
use Sadatech\Webtool\Traits\ConsoleCommand;

class Command extends IlluminateCommand
{
    /**
     * Use trait
     */
    use ConsoleCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webtool:com {--type=} {--args=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Webtool Commander';

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
        $type = @$this->option('type') ?? NULL;
        
        if ($type == "worker")
        {
            $this->WebtoolDoWorker();
        }
        else
        {
            $this->line("Undefined type commands.");
        }
    }
}
