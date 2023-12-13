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
    protected $catch = [];

    /**
     * 
     */
    public function CatchRequestData(string $uname)
    {
        /**
         * 
         */
        $this->catch['dump_name'] = $uname;
        $this->catch['dump_date'] = date('Y-m-d H:i:s');
        $this->catch['dump_hash'] = hash('md5', json_encode($this->catch['dump_name']));
        $this->catch['dump_file'] = "webtool".DIRECTORY_SEPARATOR."dump".DIRECTORY_SEPARATOR."webtool_dump_".$this->catch['dump_hash'].".yml";
        
        /**
         * 
         */
        try
        {
            $this->catch['dump_user'] = JWTAuth::parseToken()->authenticate();
        }
        catch (TokenExpiredException $e)
        {
            $this->catch['dump_user'] = ['message' => 'jwt_token_expired', 'status' => $e->getStatusCode(), 'error' => $e->getMessage()];
        }
        catch (TokenInvalidException $e)
        {
            $this->catch['dump_user'] = ['message' => 'jwt_token_invalid', 'status' => $e->getStatusCode(), 'error' => $e->getMessage()];
        }
        catch (JWTException $e)
        {
            $this->catch['dump_user'] = ['message' => 'jwt_token_absent', 'status' => $e->getStatusCode(), 'error' => $e->getMessage()];
        }
        catch(Exception $e)
        {
            $this->catch['dump_user'] = ['message' => 'php_token_invalid', 'status' => $e->getStatusCode(), 'error' => $e->getMessage()];
        }

        /**
         * Validate Raw or Body
         */
        $this->catch['request']   = request();
        if ($this->catch['request']->getContent() == "" || $this->catch['request']->getContent() == null)
        {
            $this->catch['dump_data'] = $this->catch['request']->all();
        }
        else
        {
            $this->catch['dump_data'] = json_decode($this->catch['request']->getContent());
        }
        unset($this->catch['request']);

        /**
         * 
         */
        // file_put_contents(sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->catch['dump_file'], Yaml::dump($this->catch, Yaml::PARSE_OBJECT));
        Storage::disk('local')->exists($this->catch['dump_file']) ? Storage::disk('local')->delete($this->catch['dump_file']) : null;

        return Storage::disk('local')->put($this->catch['dump_file'], Yaml::dump($this->catch, Yaml::PARSE_OBJECT));
    }
}