<?php
namespace Sadatech\Webtool\Console\Traits;

trait WorkerTrait
{
    /**
     * Define variables
     */
    private $buffer = [];

     /**
     * Do worker process
     */
    public function consoleDoWorker()
    {
        $this->buffer[] = 'Worker process started';

        print_r($this->buffer);
    }
}