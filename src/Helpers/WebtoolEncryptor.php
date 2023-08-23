<?php
namespace Sadatech\Webtool\Helpers;

class WebtoolEncryptor
{
    private $local_file_prefix = 'wtenc_';
    private $local_file_path;

    public function __construct()
    {
        $this->local_file_path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->local_file_prefix.DIRECTORY_SEPARATOR;

        if (!is_dir($this->local_file_path))
        {
            mkdir($this->local_file_path);
        }
    }

    public function Make($string, $salt = '')
    {
        $keyName = hash('sha256', md5(sha1(base64_encode($string))).md5($salt).sha1(time()));
        $keyData = gzcompress($string, 9);

        if (!file_exists($this->local_file_path.$keyName))
        {
            file_put_contents($this->local_file_path.$keyName, $keyData);
        }

        return $keyName;
    }

    public function Disassemble($keyName, $removeLink = false)
    {
        if (file_exists($this->local_file_path.$keyName))
        {
            $keyData = file_get_contents($this->local_file_path.$keyName);
            $keyData = gzuncompress($keyData);

            if ($removeLink)
            {
                unlink($this->local_file_path.$keyName);
            }
            return $keyData;
        }
        
        throw new \Exception("Error Processing Request: Failed to Disassemble key, not registered on system.", 1);
    }
}