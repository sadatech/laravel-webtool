<?php
namespace Sadatech\Webtool\Helpers;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Webtool
{
    public static function DoCommand($command)
    {
        if (file_exists($command[0]))
        {
            array_unshift($command, 'nohup'); // fix timeout command
            $process = new Process($command, null, [
                'SYNC_USE_WEBUI' => 'yes',
                'SYNC_FORCE_FETCH' => 'yes',
            ]);
            $process->run();

            if (!$process->isSuccessful())
            {
                throw new ProcessFailedException($process);
            }

            return $process->getOutput();
        }
    }
}
