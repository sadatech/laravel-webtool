<?php
namespace Sadatech\Webtool\Traits;

use Sadatech\Webtool\Helpers\Common;
use GuzzleHttp\Client as HTTP_Client;

trait ExtendedJob
{
    public function MakeRequestNode($scheme, $endpoint, $build_query)
    {
        $http_scheme = strtoupper($scheme);
        $http_endpoint = 'https://dataproc.sadata.id/'.$endpoint;
        $http_build_query = $build_query;
        $http_client = new HTTP_Client;

        $http_response = $http_client->request($http_scheme, $http_endpoint, ['form_params' => $http_build_query]);

        return $http_response;
    }
}