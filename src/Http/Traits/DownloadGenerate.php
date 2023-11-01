<?php
namespace Sadatech\Webtool\Http\Traits;

use App\JobTrace;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage as FileStorage;
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
    
                        if ($download['s3size'] > 512606337)
                        {
                            return redirect()->away($download['url']);
                        }
                        else
                        {
                            header("Content-disposition: attachment; filename=\"".$download['s3name']."\"");
                            header("Content-Length: ".$download['s3size']);
                            header("Content-Type: ".$download['s3mime']);
                            header("Pragma: public");
                            header("Expires: 0");
    
                            return FileStorage::disk("spaces")->get($download['path']);
                        }
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
        else
        {
            return redirect()->back()->withErrors(['message' => 'Failed to download file, download link is invalid/expired.']);
        }

        return redirect()->back()->withErrors(['message' => 'File may no longer be available due file has expired.']);
    }
}