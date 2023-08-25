<?php
namespace Sadatech\Webtool\Helpers;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Sadatech\Webtool\Helpers\WebtoolEncryptor;

class Webtool
{
    public static function DoCommand($command)
    {
        $process = new Process($command, null, [
            'SYNC_USE_WEBUI' => 'yes',
            'SYNC_FORCE_FETCH' => 'yes',
            'COMPOSER_HOME' => '/home/sadatech/.config/composer',
            'HOME' => '/home/sadatech',
        ]);
        $process->run();

        if (!$process->isSuccessful())
        {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public static function GetConfig($key, $value = null)
    {
        return config($key, $value);
    }

    public static function GenerateActionLink($item, $path)
    {
        $action['url']  = str_replace('http://localhost/', asset(''), $item->results);
        $action['path'] = is_null($path) ? $item->results : ("/export/report/". $path . basename(str_replace('---123---', 'https', str_replace('https','---123---', str_replace('http','---123---', $action['url'])))));
        $action['url']  = (new WebtoolEncryptor)->Make(json_encode(['id' => $item->id, 'location' => $action['path']]));
        $action['html'] = '';

        if (File::exists(public_path($action['path'])))
        {
            $action['html'] .= "
            <form method='post' action='".route('export-download-get-temp')."?reqid=".hash('sha256', $action['url'].time())."'><input type='hidden' name='_token' value='".csrf_token()."'><input type='hidden' name='temp' value='".$action['url']."'>
                <button type='button' style='width: 80%;' class='btn btn-sm btn-success btn-square disabled' disabled ><i class='fa fa-spinner fa-spin'></i></button>
            </form>
            ";
        }
        else
        {
            if ($item->url)
            {
                $action['html'] .= "
                <form method='post' action='".route('export-download-get-temp')."?reqid=".hash('sha256', $action['url'].time())."'><input type='hidden' name='_token' value='".csrf_token()."'><input type='hidden' name='temp' value='".$action['url']."'>
                    <button type='submit' formtarget='_blank' class='btn btn-sm btn-success btn-square'><i class='fa fa-cloud-download' style='width: 80%;'></i></button>
                </form>
                ";
            }
            else
            {
                $action['html'] .= "
                <form method='post' action='".route('export-download-get-temp')."?reqid=".hash('sha256', $action['url'].time())."'><input type='hidden' name='_token' value='".csrf_token()."'><input type='hidden' name='temp' value='".$action['url']."'>
                    <button type='button' class='btn btn-sm btn-success btn-square' style='width: 80%;'><i class='fa fa-spinner fa-spin'></i></button>
                </form>
                ";
            }
        }

        return $action['html'];
    }
}
