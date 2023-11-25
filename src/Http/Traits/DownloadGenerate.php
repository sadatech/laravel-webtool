<?php
namespace Sadatech\Webtool\Http\Traits;

use Exception;
use App\JobTrace;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage as FileStorage;
use Illuminate\Support\Facades\Response;
use Sadatech\Webtool\Helpers\Common;
use Sadatech\Webtool\Helpers\Encryptor;

trait DownloadGenerate
{
    public function downloadGenerate($uid)
    {
        // get token uid
        $download['uid'] = $uid;
        $download['pkg'] = json_decode((new Encryptor)->Disassemble($download['uid']));

        // validate link exists
        if (isset($download['pkg']->id))
        {
            $download['trace'] = JobTrace::whereId($download['pkg']->id)->first();
            $download['path'] = str_replace(Common::GetConfig('filesystems.disks.spaces.url'), null, urldecode($download['trace']->url));
            $download['path'] = explode('/', $download['path']);
            array_shift($download['path']);
            $download['path'] = trim(implode('/', $download['path']));
    
            // validate url not empty
            if (!empty($download['trace']->url))
            {
                // set validate expired time
                $download['time_start'] = Carbon::parse($download['trace']->created_at)->addDays(5)->timestamp;
                $download['time_end']   = Carbon::now()->timestamp;
    
                // validate expired time
                if ($download['time_start'] < $download['time_end'])
                {
                    if (FileStorage::disk("spaces")->exists($download['path']))
                    {
                        // delete from spaces
                        FileStorage::disk("spaces")->delete($download['path']);
    
                        // update tracejob
                        $download['trace']->update([
                            'status'      => 'DELETED',
                            'url'         => NULL,
                            'results'     => NULL,
                            'other_notes' => 'File has expired.',
                            'log'         => 'File may no longer be available due file has expired.',
                        ]);        
                    }
                }
                else
                {
                    // download approved
                    if (FileStorage::disk("spaces")->exists($download['path']))
                    {
                        $download['s3name'] = basename($download['path']);
                        $download['s3size'] = FileStorage::disk("spaces")->size($download['path']);
                        $download['s3mime'] = FileStorage::disk("spaces")->mimeType($download['path']);
    
                        $send_global_url  = FileStorage::disk("spaces")->url($download['path']);
                        $send_global_url  = base64_encode($send_global_url);
                        $send_global_url  = str_rot13($send_global_url);
                        $cloud_url_real   = str_replace("https://sadata-cdn.sgp1.digitaloceanspaces.com", Common::GetConfig('filesystems.disks.spaces.url'), $download['path']);

                        try
                        {
                            $send_global_data = Common::FetchGetContent("https://global-mirror.sadata.id", true, false, ["url" => $send_global_url]);

                            if ($send_global_data['http_code'] !== 200)
                            {
                                return redirect()->to($cloud_url_real);
                            }
                            else
                            {
                                $send_data = json_decode($send_global_data['data']);

                                if (isset($send_data->data->preview_url))
                                {
                                    return redirect()->to($send_data->data->preview_url);
                                }
                                else
                                {
                                    return redirect()->to($cloud_url_real);
                                }
                            }
                        }
                        catch (Exception $ex)
                        {
                            return redirect()->to($cloud_url_real);
                        }
                    }
                    else
                    {
                        if (!empty($download['trace']->results))
                        {
                            return redirect()->to($download['trace']->results);
                        }
                        else
                        {
                            $download['trace']->update([
                                'status' => 'DELETED',
                                'log' => 'File may no longer be available due to an export error or the file has expired.',
                            ]);
                        }
                    }
                }
            }
        }
        else
        {
            return redirect()->back()->withErrors(['message' => 'Failed to download file, download link is invalid/expired.']);
        }

        return redirect()->back()->withErrors(['message' => 'File may no longer be available due file has expired.']);
    }
}