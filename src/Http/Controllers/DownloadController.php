<?php
namespace Sadatech\Webtool\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Sadatech\Webtool\Http\Controllers\Controller;
use Sadatech\Webtool\Helpers\EncryptorHelper;
use Sadatech\Webtool\Helpers\CommonHelper;
use App\JobTrace;

class DownloadController extends Controller
{
    private $buffer = [];

    public function GeneralDownloadCloud($uid)
    {
        $this->buffer['uid'] = $uid;
        $this->buffer['pkg'] = json_decode((new EncryptorHelper)->Disassemble($this->buffer['uid']));

        if (isset($this->buffer['pkg']->id))
        {
            $this->buffer['job_trace'] = JobTrace::where('id', $this->buffer['pkg']->id)->first();
            $this->buffer['file_path'] = explode('/', str_replace(CommonHelper::GetConfig('filesystems.disks.spaces.url'), null, urldecode($this->buffer['job_trace']->url)));
        }

        dd($this->buffer);
    }
}