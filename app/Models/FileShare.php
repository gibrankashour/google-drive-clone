<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FileShare extends Model
{
    use HasFactory;
    public $fillable = ['file_id', 'user_id', 'created_at', 'updated_at'];

    public function file() {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    public function user() {
        // the user who the file creator shared the file with
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function userFiles() {
        return $this->belongsTo(File::class, 'file_id', 'id')->where('files.created_by', Auth::id());
    }
}
