<?php

namespace App\Http\Requests;

use App\Models\File;
use App\Models\FileShare;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class DownloadSharedFilesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'all' => 'nullable|bool',
            'ids.*' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        // $file = File::query()
                        //     ->where('id', $value)
                        //     ->where(function (Builder $query) {
                        //         return $query->where('created_by', Auth::id())
                        //                         ->orHas('sharedWithMe');
                        //     })
                        //     ->first();
                        if(Route::currentRouteName() == "file.download.sharedByMe" || Route::currentRouteName() == "file.unshare") {
                            $file = FileShare::where('id', $value)->has('userFiles')->first();
                        }else {
                            $file = File::where('created_by', Auth::id())->where('id', $value)->first();
                        }
                        if (!$file) {
                            $fail('Something went wrong');
                        }
                    }
                }
            ]
        ];
    }
}
