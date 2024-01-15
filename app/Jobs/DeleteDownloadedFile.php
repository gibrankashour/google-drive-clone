<?php

namespace App\Jobs;

use App\Models\Download;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteDownloadedFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Download $download)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $type = pathinfo($this->download->public_file_name, PATHINFO_EXTENSION) == 'zip'?'zip/':'';
        $filePath = 'downloads/'.$type . $this->download->public_file_name;
        if(Storage::exists($filePath)) {
            if(Storage::delete($filePath)) {
                $this->download->delete();
            }else {
                $this->fail($this->download->toJson());
            }
        }
    }
}
