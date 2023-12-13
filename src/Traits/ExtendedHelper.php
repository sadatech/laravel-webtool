<?php
namespace Sadatech\Webtool\Traits;

use Sadatech\Webtool\Helpers\Common;
use Symfony\Component\Yaml\Yaml;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Storage;
use Exception;

trait ExtendedHelper
{
    /**
     * 
     */
    protected static $catch = [];

    /**
     * 
     */
    public static function CatchRequestData(string $uname, $type = 'json')
    {
        /**
         * Remove Old Dump
         */
        self::RemoveOldDump();

        /**
         * 
         */
        self::$catch['dump_name'] = $uname;
        self::$catch['dump_date'] = date('Y-m-d H:i:s');
        self::$catch['dump_hash'] = strtoupper(hash('crc32', self::$catch['dump_name'].self::$catch['dump_date']));
        self::$catch['dump_file'] = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', self::$catch['dump_name']);
        self::$catch['dump_file'] = mb_ereg_replace("([\.]{2,})", '', self::$catch['dump_file']);
        self::$catch['dump_file'] = "webtool".DIRECTORY_SEPARATOR."dump".DIRECTORY_SEPARATOR."DUMP(".self::$catch['dump_hash'].")_".self::$catch['dump_file'];

        /**
         * 
         */
        try
        {
            self::$catch['dump_auth'] = JWTAuth::parseToken()->authenticate();
            self::$catch['dump_user']['id'] = self::$catch['dump_auth']->id;
            self::$catch['dump_user']['name'] = self::$catch['dump_auth']->name;
        }
        catch (TokenExpiredException $e)
        {
            self::$catch['dump_user'] = ['message' => 'jwt_token_expired', 'status' => $e->getStatusCode(), 'error' => $e->getMessage()];
        }
        catch (TokenInvalidException $e)
        {
            self::$catch['dump_user'] = ['message' => 'jwt_token_invalid', 'status' => $e->getStatusCode(), 'error' => $e->getMessage()];
        }
        catch (JWTException $e)
        {
            self::$catch['dump_user'] = ['message' => 'jwt_token_absent', 'status' => $e->getStatusCode(), 'error' => $e->getMessage()];
        }
        catch(Exception $e)
        {
            self::$catch['dump_user'] = ['message' => 'php_token_invalid', 'status' => $e->getStatusCode(), 'error' => $e->getMessage()];
        }

        /**
         * Header dump
         */
        self::$catch['dump_header'] = request()->header();

        /**
         * Validate Raw or Body
         */
        self::$catch['dump_request'] = request();
        if (self::$catch['dump_request']->getContent() == "")
        {
            self::$catch['dump_request'] = self::$catch['dump_request']->all();
        }
        else
        {
            self::$catch['dump_request'] = json_decode(self::$catch['dump_request']->getContent(), true);
        }

        /**
         * 
         */
        unset(self::$catch['dump_auth']);
        unset(self::$catch['dump_hash']);

        /**
         * 
         */
        if ($type == "json")
        {
            self::$catch['dump_file'] = self::$catch['dump_file'].".json";
            self::$catch['dump_raw']  = json_encode(self::$catch, JSON_PRETTY_PRINT);
        }
        elseif ($type == "yaml")
        {
            self::$catch['dump_file'] = self::$catch['dump_file'].".yaml";
            self::$catch['dump_raw']  = Yaml::dump(self::$catch);
        }
        
        Storage::disk('local')->exists(self::$catch['dump_file']) ? Storage::disk('local')->delete(self::$catch['dump_file']) : null;
        return Storage::disk('local')->put(self::$catch['dump_file'], self::$catch['dump_raw']);
    }

    /**
     * 
     */
    private static function RemoveOldDump()
    {
        $files = glob(Storage::disk('local')->path('webtool'.DIRECTORY_SEPARATOR.'dump') . DIRECTORY_SEPARATOR . '*');
        $threshold = strtotime(Common::GetEnv('DUMP_DATA_EXPIRED', '-3 day'));

        foreach ($files as $file)
        {
            if (is_file($file))
            {
                if ($threshold >= filemtime($file))
                {
                    Storage::disk('local')->delete('webtool'.DIRECTORY_SEPARATOR.'dump'.DIRECTORY_SEPARATOR.basename($file));
                }
            }
        }
    }
}