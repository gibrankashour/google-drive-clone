<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

class ShareFileResource extends JsonResource
{
    // public static $wrap = false;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // dd(Route::currentRouteName(), $this->user->email);
        return [
            // "id" => $this->id,
            "id" => Route::currentRouteName() == "file.sharedWithMe"? $this->file->id : $this->id,
            "name" => $this->file->name,
            "email" => Route::currentRouteName() == "file.sharedWithMe"? $this->file->user->email : $this->user->email,
            "path" => $this->file->path,
            "mime" => $this->file->mime ,
            "is_folder" => $this->file->is_folder,
        ];
    }
}
