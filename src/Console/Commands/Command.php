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
            $cmd["exporter"] = Artisan::call("queue:work", ["--once" => null, "--tries" => 1, "--timeout" => 1200, "--memory" => 4096, "--memory" => 3, "--sleep" => "3", "-vvv" => null]);
            $cmd["syncfile"] = Artisan::call("queue:work", ["--type" => "export-sync-files", "-vvv" => null]);
            $this->line($cmd["exporter"]);
            $this->line($cmd["syncfile"]);
        }
        else
        {
            $this->line("Undefined type commands.");
        }
    }
}
