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
    }

    /**
     * Validate hash date range
     */
    public static function ValidateHashDateRange($hash, $range_seconds = 5)
    {
        //
    }
}