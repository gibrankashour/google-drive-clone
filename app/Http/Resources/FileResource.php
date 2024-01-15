<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public static $wrap = false;
    /*
    هيك فهمت
    هاد الخيار public static $wrap = false;
    بيخلي الريسورس الذي يتألف من عنصر واحد فقظ وليس كولكشن غير مغلف ضمن العنصر داتا
    اي ضمن الجافاسكريبت يظهر العنصر المكون من كولكشن من هذا الريسورس كالتالي
    Proxy(Object) {data: Array(3), links: {…}, meta: {…}}
    اما اذا كان هناك عنصر واحد فقظ فانه يظهر كالتالي
    Proxy(Object) {id: 1, name: 'gibran654@gmail.com', path: null, parent_id: null, is_folder: 1, …}
    */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "path" => $this->path,
            "parent_id" => $this->parent_id,
            "is_folder" => $this->is_folder,
            "mime" => $this->mime,
            "size" => $this->get_file_size($this->size) == '0.00 B'?'' : $this->get_file_size($this->size),
            'owner' => $this->owner,
            'is_favourite' => $this->starred == null ? false :true,
            "created_at" => $this->created_at->diffForHumans(),
            "updated_at" => $this->updated_at->diffForHumans(),
            "created_by" => $this->created_by,
            "updated_by" => $this->updated_by,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
