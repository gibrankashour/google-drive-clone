<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ParentIdBaseRequest extends FormRequest
{
    public ?File $parent = null;
    // File mean that :prperty must be File type
    // ? mean that property can has null value

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /*
        ما معنى وجود هذا الكود ؟
        اذا هو ضمن الرولز يتحقق من نفس الفكرة
        */
        $this->parent = File::query()->whereId($this->input('parent_id'))->first();
        if($this->parent && !$this->parent->isOwnedBy(Auth::id())){
            return false;
        }
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
            'parent_id' => [
                Rule::exists(File::class, 'id')->where(function (Builder $query){
                    return $query->where('is_folder', '=', '1')
                            ->where('created_by', auth()->id());
                }),
            ]
        ];
    }
}
