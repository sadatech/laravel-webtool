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
    public static function CatchRequestData(string $uname)
    {
        /**
         * 
         */
        self::$catch['dump_name'] = $uname;
        self::$catch['dump_date'] = date('Y-m-d H:i:s');
        self::$catch['dump_hash'] = hash('md5', json_encode(self::$catch['dump_name']));
        self::$catch['dump_file'] = "webtool".DIRECTORY_SEPARATOR."dump".DIRECTORY_SEPARATOR."webtool_dump_".self::$catch['dump_hash'].".yml";
        
        /**
         * 
         */
        try
        {
            self::$catch['dump_user'] = JWTAuth::parseToken()->authenticate();
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
         * Validate Raw or Body
         */
        self::$catch['request']   = request();
        if (self::$catch['request']->getContent() == "" || self::$catch['request']->getContent() == null)
        {
            self::$catch['dump_data'] = self::$catch['request']->all();
        }
        else
        {
            self::$catch['dump_data'] = json_decode(self::$catch['request']->getContent());
        }
        unset(self::$catch['request']);

        /**
         * 
         */
        // file_put_contents(sys_get_temp_dir().DIRECTORY_SEPARATOR.self::$catch['dump_file'], Yaml::dump(self::$catch, Yaml::PARSE_OBJECT));
        Storage::disk('local')->exists(self::$catch['dump_file']) ? Storage::disk('local')->delete(self::$catch['dump_file']) : null;

        return Storage::disk('local')->put(self::$catch['dump_file'], Yaml::dump(self::$catch, Yaml::PARSE_OBJECT));
    }
}