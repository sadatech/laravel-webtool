<?php
namespace Sadatech\Webtool\Helpers;

use Exception;
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
        $action['html'] = '';
        $action['url']  = (new Encryptor)->Make(json_encode(['id' => $item->id, 'location' => $item->url]));

        // validate if done status
        if ($item->status == "DONE")
        {
            // validate if empty results & url
            if (empty($item->results) && empty($item->url))
            {
                $item->status = 'FAILED';
                $item->log    = 'Failed to generate export file.';

                $action['html'] .= "
                <form method='post' action=''></form>
                ";
            }
            else
            // validate if empty url
            if (empty($item->url))
            {
                $action['html'] .= "
                <form method='post' action=''>
                    <button type='button' style='width: 80%;' class='btn btn-sm btn-success btn-square disabled' disabled ><i class='fa fa-spinner fa-spin'></i></button>
                </form>
                ";
            }
            else
            {
                $action['html'] .= "
                <form method='post' action='".route('webtool.download.generate', $action['url'])."?reqid=".hash('sha256', $action['url'].time())."'><input type='hidden' name='_token' value='".csrf_token()."'>
                    <button type='submit' style='width: 80%;' class='btn btn-sm btn-success btn-square'><i class='fa fa-cloud-download'></i></button>
                </form>
                ";
            }
        }
        else
        {
            $action['html'] .= "";
        }

        return $action['html'];
    }

    public static function _GenerateActionLink($item, $path)
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

    public static function FetchGetContent($url, $http_code = false)
    {
        if(!$url || !is_string($url) || ! preg_match('/^http(s)?:\/\/[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(\/.*)?$/i', $url))
        {
            return false;
        }
        else
        {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $data = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (curl_errno($ch))
            {
                throw new Exception('Failed to execute CURL operations.');
            }
            curl_close($ch);
    
            if (!$http_code)
            {
                return $data;
            }
            else
            {
                return ['data' => $data, 'http_code' => $httpcode];
            }
        }

    }
}
