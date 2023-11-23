<?php
namespace Sadatech\Webtool\Helpers;

class Validator
{
    private $local_file_prefix = '.wtval';
    private $local_file_path;

    /**
     * 
     */
    public function __construct()
    {
        $this->local_file_path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->local_file_prefix.DIRECTORY_SEPARATOR;

        if (!is_dir($this->local_file_path))
        {
            mkdir($this->local_file_path);
        }

        // Remove cache files
        Common::tempRemoveCache($this->local_file_path, "-not -newermt '-30 seconds'");
    }

    /**
     * Validate hash date range
     */
    public function ValidateHashDateRange($hash, $range_seconds = 10)
    {
        $date_second_now = time();
        $date_second_now_hash = strtoupper(hash('sha1', json_encode(md5(json_encode($hash))).$date_second_now));
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
                $date_second_hash = strtoupper(hash('sha1', json_encode(md5(json_encode($hash))).$date_second_adj));
    
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