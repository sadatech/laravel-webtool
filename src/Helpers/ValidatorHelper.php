<?php
namespace Sadatech\Webtool\Helpers;

class ValidatorHelper
{
    private $local_file_prefix = 'webtool_validator_';
    private $local_file_path;

    /**
     * 
     */
    public function __construct()
    {
        $this->local_file_path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->local_file_prefix;
    }

    /**
     * Validate hash date range
     */
    public function ValidateHashDateRange($hash, $range_seconds = 10)
    {
        $date_second_now = time();
        $date_second_now_hash = strtoupper(hash('md5', json_encode(md5(json_encode($hash))).$date_second_now));
        $date_second_raw = [];
        $date_second_adj = $date_second_now;

        if (file_exists($this->local_file_path.$date_second_now_hash))
        {
            return false;
        }
        else
        {
            for ($i=0; $i < $range_seconds; $i++)
            {
                $date_second_hash = strtoupper(hash('md5', json_encode(md5(json_encode($hash))).$date_second_adj));
    
                if (!file_exists($this->local_file_path.$date_second_hash))
                {
                    file_put_contents($this->local_file_path.$date_second_hash, 1);
                }
    
                $date_second_raw[] = $date_second_hash;
                $date_second_adj++;
            }
            return true;
        }
    }
}