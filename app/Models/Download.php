<?php

namespace App\Models;

use App\Jobs\DeleteDownloadedFile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Download extends Model
{
    use HasFactory;
    protected $fillable = ['public_file_name', 'user_id', 'save_as_name', 'created_at', 'updated_at'];

    protected static function boot() {
        parent::boot();

        static::created( function ($model) {

            DeleteDownloadedFile::dispatch($model)->delay(now()->addMinutes(3));
        });
    }
}
