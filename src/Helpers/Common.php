<?php
namespace Sadatech\Webtool\Helpers;

use Illuminate\Support\Facades\File;
use Sadatech\Webtool\Helpers\Encryptor;

class Common
{

    public static function GetConfig($key, $value = null)
    {
        return config($key, $value);
    }

    public static function GetEnv($key, $value = null)
    {
        return env($key, $value);
    }

    public static function GenerateActionLink($item, $path)
    {
        $action['url']  = str_replace('http://localhost/', asset(''), $item->results);
        $action['path'] = is_null($path) ? $item->results : ("/export/report/". $path . basename(str_replace('---123---', 'https', str_replace('https','---123---', str_replace('http','---123---', $action['url'])))));
        $action['url']  = (new Encryptor)->Make(json_encode(['id' => $item->id, 'location' => $action['path']]));
        $action['html'] = '';

        if (File::exists(public_path($action['path'])))
        {
            $action['html'] .= "
            <form method='post' action='".route('webtool.download.generate', $action['url'])."?reqid=".hash('sha256', $action['url'].time())."'><input type='hidden' name='_token' value='".csrf_token()."'>
                <button type='button' style='width: 80%;' class='btn btn-sm btn-success btn-square disabled' disabled ><i class='fa fa-spinner fa-spin'></i></button>
            </form>
            ";
        }
        else
        {
            if ($item->url)
            {
                $action['html'] .= "
                <form method='post' action='".route('webtool.download.generate', $action['url'])."?reqid=".hash('sha256', $action['url'].time())."'><input type='hidden' name='_token' value='".csrf_token()."'>
                    <button type='submit' class='btn btn-sm btn-success btn-square'><i class='fa fa-cloud-download' style='width: 80%;'></i></button>
                </form>
                ";
            }
            else
            {
                $action['html'] .= "
                <form method='post' action='".route('webtool.download.generate', $action['url'])."?reqid=".hash('sha256', $action['url'].time())."'><input type='hidden' name='_token' value='".csrf_token()."'>
                    <button type='button' class='btn btn-sm btn-success btn-square' style='width: 80%;'><i class='fa fa-spinner fa-spin'></i></button>
                </form>
                ";
            }
        }

        return $action['html'];
    }

    public static function WaitForSec($sec)
    {
        $i = 1;
        $last_time = $_SERVER['REQUEST_TIME'];
        while($i > 0){
            $total = $_SERVER['REQUEST_TIME'] - $last_time;
            if($total >= 2){
                return 1;
                $i = -1;
            }
        }
    }
}