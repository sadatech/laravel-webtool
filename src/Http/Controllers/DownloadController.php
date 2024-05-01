<?php
namespace Sadatech\Webtool\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Sadatech\Webtool\Http\Controllers\Controller;
use Sadatech\Webtool\Helpers\EncryptorHelper;

class DownloadController extends Controller
{
    private $buffer = [];

    public function GeneralDownloadCloud($uid)
    {
        $this->buffer['uid'] = $uid;
        $this->buffer['pkg'] = json_decode((new EncryptorHelper)->Disassemble($this->buffer['uid']));

        dd($this->buffer);
    }
}