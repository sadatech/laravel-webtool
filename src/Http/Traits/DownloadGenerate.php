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
        $download['pkg'] = json_decode((new Encryptor)->Disassemble($uid));
        $download['id']  = $download['pkg']->id;
        $download['url'] = $download['pkg']->location;

        // get status from job_trace table
        $download['trace'] = JobTrace::whereId($download['id'])->first();

        // set validate expired time
        $download['time_start'] = Carbon::parse($download['trace']->created_at)->addDays(5)->timestamp;
        $download['time_end']   = Carbon::now()->timestamp;

        // validate expired time
        if ($download['time_start'] < $download['time_end'])
        {
            $download['s3filename'] = "export-data/".str_replace('//', '/', str_replace('_', '-', Common::GetConfig("database.connections.mysql.database"))."/".$download['url']);

            if (file_exists(public_path($download['url'])))
            {
                File::delete(public_path($download['url']));
            }

            if (FileStorage::disk("spaces")->exists($download['s3filename']))
            {
                FileStorage::disk("spaces")->delete($download['s3filename']);
            }

            //
            $download['trace']->update([
                'status' => 'DELETED',
                'log' => 'File may no longer be available due to an export error or the file has expired.',
            ]);

            return response()->json(['message' => 'File may no longer be available due to an export error or the file has expired.']);
        }

        // validate exists file
        if (file_exists(public_path($download['url'])))
        { 
            // Upload S3 export data
            $download['s3filename'] = "export-data/".str_replace('//', '/', str_replace('_', '-', Common::GetConfig("database.connections.mysql.database"))."/".$download['url']);
            if (!FileStorage::disk("spaces")->exists($download['s3filename']))
            {
                $download['trace']->update([
                    'explanation' => 'File is uncer sync to CDN servers.',
                    'log' => 'File is under sync to CDN servers.',
                    'status' => 'PROCESSING',
                ]);
                $download['s3filedata'] = FileStorage::disk("spaces")->put($download['s3filename'], File::get(public_path($download['url'])), "public");
                if ($download['s3filedata'])
                {
                    $download['s3filedata'] = FileStorage::disk("spaces")->url($download['s3filename']);
                    $download['s3fileurl'] = str_replace('https://'.config('filesystems.disks.spaces.bucket').str_replace('https://', '.', config('filesystems.disks.spaces.endpoint')), config('filesystems.disks.spaces.url'), $download['s3filedata']);
                    $download['trace']->update([
                        'explanation' => 'File archived on CDN servers.',
                        'log' => 'File archived on CDN servers.',
                        'url' => $download['s3fileurl'],
                        'status' => 'DONE',
                    ]);
                    File::delete(public_path($download['url']));
                }
            }
            else
            {
                $download['s3filedata'] = FileStorage::disk("spaces")->url($download['s3filename']);
                $download['s3fileurl'] = str_replace('https://'.config('filesystems.disks.spaces.bucket').str_replace('https://', '.', config('filesystems.disks.spaces.endpoint')), config('filesystems.disks.spaces.url'), $download['s3filedata']);
                File::delete(public_path($download['url']));
            }
            
            if (isset($download['s3fileurl']))
            {
                return $this->returnDownloadSecure($download['s3fileurl'], $download['id']);
            }
            else
            {
                return $this->returnDownloadSecure($download['trace']->url, $download['id']);
            }
        }
        else
        {
            if ($download['trace']->url)
            {
                return $this->returnDownloadSecure($download['trace']->url, $download['id']);
            }
        }
    }

    private function returnDownloadSecure($url, $id)
    {
        $download['url'] = $url;
        $download['s3url'] = str_replace(config('filesystems.disks.spaces.url'), '', urldecode($download['url']));
        $download['trace'] = JobTrace::whereId($id)->first();

        # Validate file exists
        if (FileStorage::disk("spaces")->exists($download['s3url']))
        {
            $download['s3name'] = basename($download['s3url']);
            $download['s3size'] = FileStorage::disk("spaces")->size($download['s3url']);
            $download['s3mime'] = FileStorage::disk("spaces")->mimeType($download['s3url']);

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

                return FileStorage::disk("spaces")->get($download['s3url']);
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