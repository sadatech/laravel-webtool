<?php
namespace Sadatech\Webtool\Traits;

trait DownloadGenerate
{
    public function downloadGenerate()
    {
        return response()->json(['download' => 'generate']);
    }
}