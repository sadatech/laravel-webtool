<?php
namespace Sadatech\Webtool\Http\Traits;

use Carbon\Carbon;

trait Healthcheck
{
    public function healthcheckResponse()
    {
        return response()->json([
            'status' => 'OK',
            'message' => 'Webtool is running.',
            'timestamp' => Carbon::now()->timestamp,
        ]);
    }
}