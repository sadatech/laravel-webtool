<?php
namespace Sadatech\Webtool\Helpers;

use Sadatech\Webtool\Helpers\CommonHelper;

class WorkerHelper
{
    public static function ValidateResultBaseURL($job_trace, $parse_url, $base_url)
    {
        if (isset($parse_url['scheme']))
        {
            if (CommonHelper::GetEnv('DATAPROC_URL') == '' || is_null(CommonHelper::GetEnv('DATAPROC_URL')))
            {
                if ($parse_url['host'] !== @parse_url(CommonHelper::GetEnv('DATAPROC_URL', 'https://dataproc.sadata.id/'))['host']) return str_replace($parse_url['host'], request()->getHost(), $job_trace->results);
            }
        }
        return $base_url;
    }
}