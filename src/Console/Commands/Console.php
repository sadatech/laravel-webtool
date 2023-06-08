<?php
namespace Sadatech\Webtool\Console\Commands;

use Illuminate\Console\Command;
use Sadatech\Webtool\Traits\ConsoleCommand;

class Console extends Command
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
    protected $signature = 'webtool:fetch {--type=} {--args=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Webtool Connector';

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
        $args = @$this->option('args') ?? NULL;
        
        if ($type == "total-job")
        {
            $this->line($this->WebtoolTotalJob());
        } 
        elseif ($type == "env")
        {
            $this->line($this->WebtoolEnv($args));
        }
        elseif ($type == "export-sync-files")
        {
            $this->line($this->WebtoolExportSyncFiles());
        }
        else
        {
            $this->line("undefined type commands ( env | total-job | export-sync-files | do-command ).");
        }
    }
}
