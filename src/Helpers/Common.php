<?php
namespace Sadatech\Webtool\Helpers;

use Exception;
use Illuminate\Support\Facades\File;
use Sadatech\Webtool\Helpers\Encryptor;

class Common
{
    private static function _removeTemp()
    {
        @system("find ".sys_get_temp_dir()." -maxdepth 3 -type f -mtime +0 -exec rm -f {} +");
    }

    public static function GetConfig($key, $value = null)
    {
        @self::_removeTemp();

        return config($key, $value);
    }

    public static function GetEnv($key, $value = null)
    {
        @self::_removeTemp();

        return env($key, $value);
    }

    public static function GenerateActionLink($item, $path)
    {
        @self::_removeTemp();

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
                    <button type='submit' style='width: 80%;' class='btn btn-sm btn-success btn-square' formtarget='_blank'><i class='fa fa-cloud-download'></i></button>
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

    public static function WaitForSec($sec)
    {
        @self::_removeTemp();

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

    private static function FetchGetContent_Backup($url)
    {
        @self::_removeTemp();

        try
        {
            $data = file_get_contents($url, false, stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]));

            return $data;
        }
        catch (Exception $exception)
        {
            throw new Exception($exception->getMessage());
        }
    }

    public static function FetchGetContent($url, $http_code = false, $file_temp = false, $postfields = null)
    {
        @self::_removeTemp();

        if ($file_temp)
        {
            $temp_file_stream_get_content = sys_get_temp_dir().DIRECTORY_SEPARATOR."WEBTOOLSTREAM_".uniqid();
            $temp_stream_get_content = @fopen($temp_file_stream_get_content, 'wbr+');
        }

        try
        {
            $ch = curl_init();
            $url_decode = rawurldecode($url);
            $url_basename = basename($url_decode);
            curl_setopt($ch, CURLOPT_URL, str_replace($url_basename, rawurlencode($url_basename), $url_decode));

            if (is_array($postfields))
            {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            
            if ($file_temp)
            {
                curl_setopt($ch, CURLOPT_FILE, $temp_stream_get_content);
                curl_exec($ch);
            }
            else
            {
                $data = curl_exec($ch);
            }

            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlno = curl_errno($ch);
            curl_close($ch);

            if ($file_temp)
            {
                @fclose($temp_stream_get_content);
                $data = file_get_contents($temp_file_stream_get_content);
                @unlink($temp_file_stream_get_content);
            }

            if ($curlno)
            {
                $data = self::FetchGetContent_Backup(str_replace($url_basename, rawurlencode($url_basename), $url_decode));

                if (!$http_code)
                {
                    return $data;
                }
                else
                {
                    return ['data' => $data, 'http_code' => 200];
                }
            }

            if (!$http_code)
            {
                return $data;
            }
            else
            {
                return ['data' => $data, 'http_code' => $httpcode, 'message' => $http_code];
            }
        }
        catch(Exception $exception)
        {
            if ($file_temp)
            {
                @fclose($temp_stream_get_content);
                @unlink($temp_file_stream_get_content);
            }

            return ['data' => NULL, 'http_code' => 500, 'message' => $exception->getMessage()];
        }
    }
}
