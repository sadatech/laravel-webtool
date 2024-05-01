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

            if (isset($this->buffer['job_trace']->id))
            {
                if (Storage::disk('spaces')->exists($this->buffer['file_path']))
                {
                    $this->buffer['download_cloud_name'] = basename($this->buffer['file_path']);
                    $this->buffer['download_cloud_size'] = Storage::disk('spaces')->size($this->buffer['file_path']);
                    $this->buffer['download_cloud_mime'] = Storage::disk('spaces')->mimeType($this->buffer['file_path']);
                    $this->buffer['download_cloud_url']  = str_replace("https://sadata-cdn.sgp1.digitaloceanspaces.com", CommonHelper::GetConfig('filesystems.disks.spaces.url'), Storage::disk('spaces')->url($this->buffer['file_path']));

                    try
                    {
                        $this->buffer['download_global_url']  = str_rot13(base64_encode(Storage::disk('spaces')->url($this->buffer['file_path'])));
                        $this->buffer['download_global_fetch'] = CommonHelper::FetchGetContent('https://global-mirror.sadata.id', true, false, ['url' => $this->buffer['download_global_url']]);

                        if ($this->buffer['download_global_fetch']['http_code'] !== 200)
                        {
                            $this->buffer['download_global_scheme'] = parse_url($this->buffer['download_cloud_url']);
                            if (isset($this->buffer['download_global_scheme']['scheme'])) return redirect()->to($this->buffer['download_cloud_url']);
                            return response()->json($this->buffer['download_global_fetch']);
                        }
                        else
                        {
                            $this->buffer['download_global_data'] = json_decode($this->buffer['download_global_fetch']['data']);

                            if (isset($this->buffer['download_global_data']->data->preview_url)) return redirect()->to($this->buffer['download_global_data']->data->preview_url);
                            return redirect()->to($this->buffer['download_cloud_url']);
                        }
                    }
                    catch (Exception $exception)
                    {
                        return redirect()->to($this->buffer['download_cloud_url']);
                    }
                }
                else
                {
                    if (!empty($this->buffer['job_trace']->results))
                    {
                        $this->buffer['download_global_url'] = str_rot13(base64_encode(Storage::disk("spaces")->url($this->buffer['job_trace']->results)));
                        $this->buffer['download_cloud_url']  = $this->buffer['job_trace']->results;

                        try
                        {
                            $this->buffer['download_global_fetch'] = CommonHelper::FetchGetContent("https://global-mirror.sadata.id", true, false, ["url" => $this->buffer['download_global_url']]);

                            if ($this->buffer['download_global_fetch']['http_code'] !== 200) return redirect()->to($this->buffer['download_cloud_url']);
                            else
                            {
                                $this->buffer['download_global_data'] = json_decode($this->buffer['download_global_fetch']['data']);

                                if (isset($this->buffer['download_global_data']->data->preview_url)) return redirect()->to($this->buffer['download_global_data']->data->preview_url);
                                return redirect()->to($this->buffer['download_cloud_url']);
                            }
                        }
                        catch (Exception $ex)
                        {
                            return redirect()->to($this->buffer['download_cloud_url']);
                        }
                    }
                    else
                    {
                        $this->buffer['job_trace']->update([
                            'status' => 'DELETED',
                            'log' => 'File may no longer be available due to an export error or the file has expired.',
                        ]);
                    }
                }
            }
        }
    }
}