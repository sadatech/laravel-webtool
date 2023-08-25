<?php
namespace Sadatech\Webtool\Traits;

use Sadatech\Webtool\Helpers\Webtool as WebtoolHelper;

trait ExtendedModel
{
    public static function boot()
    {
        parent::boot();

        // self::created(function($model){
        //     WebtoolHelper::DoCommand(['nohup', '/usr/local/bin/webtool', 'app', 'exec', request()->getHost(), 'worker']);
        // });

    }
}