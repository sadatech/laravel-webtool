<?php
namespace Sadatech\Webtool\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
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
            array_shift($this->buffer['file_path']);
            $this->buffer['file_path'] = trim(implode('/', $this->buffer['file_path']));

            if (empty($this->buffer['job_trace']))
            {
                if (Storage::disk('spaces')->exists($this->buffer['file_path']))
                {
                    $this->buffer['download_cloud_name'] = basename($this->buffer['file_path']);
                    $this->buffer['download_cloud_size'] = Storage::disk('spaces')->size($this->buffer['file_path']);
                    $this->buffer['download_cloud_mime'] = Storage::disk('spaces')->mimeType($this->buffer['file_path']);
                    $this->buffer['download_cloud_url']  = str_replace("https://sadata-cdn.sgp1.digitaloceanspaces.com", CommonHelper::GetConfig('filesystems.disks.spaces.url'), Storage::disk('spaces')->url($this->buffer['file_path']))

                    try
                    {
                        $this->buffer['download_global_url']  = str_rot13(base64_encode(Storage::disk('spaces')->url($this->buffer['file_path'])));
                        $this->buffer['download_global_path'] = CommonHelper::FetchGetContent('https://global-mirror.sadata.id', true, false, ['url' => $this->buffer['download_global_url']]);
                    }
                }
            }
        }

        dd($this->buffer);
    }
}