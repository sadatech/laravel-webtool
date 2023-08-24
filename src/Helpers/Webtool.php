<?php
namespace Sadatech\Webtool\Helpers;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Webtool
{
    public static function DoCommand($command)
    {
        $process = new Process($command, null, [
            'SYNC_USE_WEBUI' => 'yes',
            'SYNC_FORCE_FETCH' => 'yes',
            'COMPOSER_HOME' => '/home/sadatech/.config/composer'
        ]);
        $process->run();

        if (!$process->isSuccessful())
        {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
}
