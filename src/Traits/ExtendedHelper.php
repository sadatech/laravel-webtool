<?php
namespace Sadatech\Webtool\Traits;

use Sadatech\Webtool\Helpers\Common;

trait ExtendedHelper
{
    /**
     * 
     */
    protected $catch = [];

    /**
     * 
     */
    public function CatchRequestData(string &$uname)
    {
        $this->catch['data']  = request()->all();
        $this->catch['uname'] = $uname;

        return $this->catch;
    }
}