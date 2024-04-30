<?php
namespace Sadatech\Webtool\Traits;

use Sadatech\Webtool\Helpers\CommonHelper;
use GuzzleHttp\Client as HTTP_Client;

trait JobTrait
{
    public function MakeRequestNode($scheme, $endpoint, $build_query, $job_trace = NULL)
    {
        $http_scheme = strtoupper($scheme);
        $http_endpoint = CommonHelper::GetEnv('DATAPROC_URL', 'https://dataproc.sadata.id').'/'.$endpoint;
        $http_build_query = $build_query;
        $http_client = new HTTP_Client(['defaults' => [
            'verify' => false
        ], 'verify' => false]);

        $http_response = $http_client->request($http_scheme, $http_endpoint, ['form_params' => $http_build_query]);

        if (!is_null($job_trace))
        {
            if ($http_response->getStatusCode() == 200)
            {
                $http_response = json_decode($http_response->getBody()->getContents());
                $result        = @$http_response->path;

                $job_trace->update([
                    'log' => @$http_response->hash,
                ]);
            }
            else
            {
                $result = "No data found.";

                $job_trace->update([
                    'status' => 'FAILED',
                    'log'    => 'Failed to export data (from node)',
                ]);
            }

            return $result;
        }
        else
        {
            return $http_response;
        }

    }
}