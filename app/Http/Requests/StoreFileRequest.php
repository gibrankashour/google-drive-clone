<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreFileRequest extends ParentIdBaseRequest
{

    protected function prepareForValidation() // هذا التابع موجود ضمن الكلاس الاب بس مابعرف وين بالتحديد
        {
        // كما قلنا سابقا في ملف AuthenticatedLayout.vue
        // من الممكن ان تكون قيمة المتغير $this->relative_paths
        // فارغة وذلك اذا كنا نرفع ملفات فقظ
        $paths = array_filter($this->relative_paths ?? [], fn($f) => $f != null);
        /*
        This code in PHP is using the null coalescing operator to check if
        the variable $this->relative_paths is set or not. If it is set,
        then it will return its value, otherwise, it will return an empty array.

        The null coalescing operator (??) is used to provide
        a default value when a variable is null or undefined. In this case,
        if $this->relative_paths is null or undefined,
        then the code will return an empty array ([]).
        */
        $this->merge([
            'file_paths' => $paths,
            'folder_name' => $this->detectFolderName($paths)
        ]);
    }

    protected function passedValidation()
    {
        $data = $this->validated();

        $this->replace([
            'file_tree' => $this->buildFileTree($this->file_paths, $data['files'])
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(),[
            'files.*' => [
                'required',
                'max:2048',
                'file',
                function ($attribute, $value, $fail) {
                    if (!$this->folder_name) {
                        // اذا كان لا يوجد فولدر نيم هذا يعني اننا نرفع ملفات فقظ ولا نرفع مجلد
                        // لأنه لو كنا نرفع مجلد لا يوجد حاجة للتأكد من أن المفات داخله لها اسماء مميزة
                        /** @var $value \Illuminate\Http\UploadedFile */
                        $file = File::query()->where('name', $value->getClientOriginalName())
                            ->where('created_by', Auth::id())
                            ->where('parent_id', $this->parent_id)
                            ->whereNull('deleted_at')
                            ->exists();

                        if ($file) {
                            $fail('File "' . $value->getClientOriginalName() . '" already exists.');
                        }
                    }
                }
            ],
            'folder_name' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        // في حال كنا نرفع مجلد نحتاج فقظ أن ننتاكد من أن المسار الحالي ا يحوي ملف او مجلد له نفس الاسم
                        /** @var $value \Illuminate\Http\UploadedFile */
                        $file = File::query()->where('name', $value)
                            ->where('created_by', Auth::id())
                            ->where('parent_id', $this->parent_id)
                            ->whereNull('deleted_at')
                            ->exists();

                        if ($file) {
                            $fail('Folder "' . $value . '" already exists.');
                        }
                    }
                }
            ]
        ]);
    }

    public function detectFolderName($paths)
    {
        if (!$paths) {
            return null;
        }
        $parts = explode("/", $paths[0]);
        return $parts[0];
    }


    private function buildFileTree($filePaths, $files)
    {
        /*
        بما انو السيرفر له عدد محدد من الملفات التي يستظيع رفعها
        مثلا اذا كان الحد هو 20 ملف فقظ وانا قمت برفع 50 ملف مثلا عندئذ سيكون
        عدد الملفات هو 20 فقظ اما حجم المتغير
        $filePaths
        هو 50 لذلك يجب اولا جعل المتغير $filePaths
        مساوي للمتغير $files
        */
        $filePaths = array_slice($filePaths, 0, count($files));
        $filePaths = array_filter($filePaths, fn($f) => $f != null);

        $tree = [];

        foreach ($filePaths as $ind => $filePath) {
            /*
            المتغير $filePaths
            هو عبارة عن مصفوفة تحمل اسماء الملفات مع مساراتها.
            ‌ مثلا اذا رفعنا مجلد صور وضمنه مجلد وضمن المجلد الثاني يوجد صورة سيكون اسمها كالتالي
            Pictures/cars/bmw.jpg
            */
            $parts = explode('/', $filePath);

            $currentNode = &$tree;
            /*
            &$var explanation:
            In PHP, the "&" symbol before a variable name is used to pass that variable
            by reference instead of by value.

            So, "&$var" means that the variable $var is passed as a reference to a function or method,
            rather than as a copy of its value.
            This allows the function or method to modify the original value of $var,
            which can be useful in certain situations.
            */
            // الشرح بالفديو بالدقيقة 3:45:26
            foreach ($parts as $i => $part) {
                if (!isset($currentNode[$part])) {
                    $currentNode[$part] = [];
                }

                if ($i === count($parts) - 1) {
                // اذا وصلنا لهذا الشرظ فأنه يعني اننا الان في اخر عنصر في المصفوفة
                // اي اذا كان اسم الصورة كالتالي
                // Pictures/cars/bmw.jpg
                // فأنناا وصلنا الى bmw.jpg
                // وعندها نضيف الملف نفسه الى المصفوفة
                    $currentNode[$part] = $files[$ind];
                } else {
                    $currentNode = &$currentNode[$part];
                }

            }
        }

        return $tree;
    }


    public function messages()
    {
        return [
            'files.*.max' => 'File size must be less than 2 MB'
        ];
    }

}
