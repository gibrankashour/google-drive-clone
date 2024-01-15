<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToFavouritesRequest;
use App\Http\Requests\DownloadSharedFilesRequest;
use App\Http\Requests\FilesActionRequest;
use App\Http\Requests\ShareFilesRequest;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\TrashFilesRequest;
use App\Http\Resources\FileResource;
use App\Http\Resources\ShareFileResource;
use App\Models\Download;
use App\Models\File;
use App\Models\FileShare;
use App\Models\StarredFile;
use App\Models\User;
// use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function myFiles(Request $request, $folder = null) {
        if($folder){
            $folder = File::query()->Where('created_by', Auth::id())->where('path', $folder)->firstOrFail();
        }else{
            $folder = $this->getRoot();
        }

        $favourites = $request->favourites != null && $request->favourites == 'show' ? 'show': 'notShow';
        $search = $request->search ?? '';

        $files = File::query()
            ->with('starred');
        if($favourites == 'show') {
            $files = $files->has('starred');
        }
        if($search) {
            $files = $files->where('name','like','%'.$search.'%');
        }
        $files = $files->where('parent_id', $folder->id)
            ->where('created_by', Auth::id())
            ->orderBy('is_folder','desc')
            ->orderBy('created_at','desc')
            ->orderBy('id','desc')
            ->paginate(10);

        $files = FileResource::collection($files);
        if($request->wantsJson()) {
            return $files;
        }
        // dd($files);
        $folder = new FileResource($folder);
        $ancestors = FileResource::collection([...$folder->ancestors, $folder]);
        // ثلاث نقاظ يعني اني أريد  عناصر المتغير الأول عنصر عنصر
        // ولو لم أضع الثلاث نقاظ لكان قد أخذ المتغير الأول كمصفوفة ودمج معه المتغير الثاني
        // وكان الناتج دائما عنصرين أول واحد مصفوفة اوالثاني المتغير الثاني
        // dd($files, $folder);
        return Inertia::render("MyFiles", compact('files', 'folder', 'ancestors', 'favourites'));
    }

    public function createFolder(StoreFolderRequest $request) {
        $data = $request->validated();
        $parent = $request->parent;
        if(!$parent) {
            // اي بما ان المجلد الذي اريد انشاؤه ليس له أب يعني انني أريد انشاؤه في مجلد الروت
            $parent = $this->getRoot();
        }
        $file = new File();
        $file->is_folder = 1;
        $file->name = $data["name"];
        $parent->appendNode($file);

    }

    public function store(StoreFileRequest $request) {
        $data = $request->validated();
        $parent = $request->parent;
        $user = $request->user();
        $fileTree = $request->file_tree;

        if(!$parent) {
            $parent = $this->getRoot();
        }
        if(!empty($fileTree)) {
            $this->saveFileTree($fileTree, $parent, $user);
        } else {
            foreach($data['files'] as $file) {
                $this->saveFile($file, $user, $parent);
            }
        }
    }

    public function saveFileTree($fileTree, $parent, $user)
    {
        foreach ($fileTree as $name => $file) {
            if (is_array($file)) {
                $folder = new File();
                $folder->is_folder = 1;
                $folder->name = $name;

                $parent->appendNode($folder);
                $this->saveFileTree($file, $folder, $user);
            } else {

                $this->saveFile($file, $user, $parent);
            }
        }
    }

    private function saveFile($file, $user, $parent): void
    {
        $path = $file->store('/files/' . $user->id, 'local');
        $model = new File();
        $model->storage_path = $path;
        $model->is_folder = false;
        $model->name = $file->getClientOriginalName();
        $model->mime = $file->getMimeType();
        $model->size = $file->getSize();

        $parent->appendNode($model);
    }

    public function destroy(FilesActionRequest $request) {
        $data = $request->validated();
        $parent = $request->parent;

        if($data['all']) {
            $children = $parent->children;
            foreach($children as $child) {
                /*
                    في الباكج nested set
                    التابع delete
                    يحذف المجلد وأيضا يحذف كل الملفات الموجودة ضمنه وهذا لا نريده
                    وذلك لانه عند حذف المجلد فقظ يمكن بسهولة استعادته من سلة المحذوفات
                    اما اذا حذفنا المجلد والملفات التي ضمن أيضا ستظهر مسكلة وهي
                    أن المجلد و محتوياته ايضا ستظهر كلها في سلة المحذوفات
                    وهذه سيعقد من عملية استعادة الملفات
                    لذلك عرفنا هذا التابع الجديد لكي نضمن حذف المجلد فقظ
                */
                $child->moveToTrash();
                // $child->delete();
            }
        }else {
            foreach($data['ids'] ?? [] as $id) {
                $file = File::find($id);
                if($file) {
                    $file->moveToTrash();
                    // $file->delete();
                }
            }
        }
        /*
        if($data['all']){
            $descendants = $parent->descendants;
            foreach($descendants as $descendant) {
                if(!$descendant->is_folder) {
                    $descendant->delete();
                }
            }
            $descendants = $parent->descendants;
            foreach($descendants as $descendant) {
                $descendant->delete();
            }
        }else {
            foreach($data['ids'] ?? [] as $id) {
                $file = File::find($id);
                if(!$file->is_folder) {
                    $file->delete();
                }else {
                    $descendants = $file->descendants;
                    foreach($descendants as $descendant) {
                        if(!$descendant->is_folder) {
                            $descendant->delete();
                        }
                    }
                    $descendants = $file->descendants;
                    foreach($descendants as $descendant) {
                        $descendant->delete();
                    }
                    $file->delete();
                }
            }
        }
         */
        return to_route('myFiles',['folder' => $parent->path ]);
    }

    public function trash(Request $request) {

        $search = $request->search ?? '';

        $files = File::onlyTrashed()->where('created_by', Auth::id());
        if($search) {
            $files = $files->where('name','like','%'.$search.'%');
        }
        $files = $files->orderBy('is_folder', 'desc')
                    ->orderBy('deleted_at', 'desc')
                    ->paginate(10);

        $files = FileResource::collection($files);
        if($request->wantsJson()) {
            return $files;
        }
        return Inertia::render('Trash', compact('files'));
    }

    public function restore (TrashFilesRequest $request) {
        $data = $request->validated();
        if ($data['all']) {
            $children = File::onlyTrashed()->where('created_by', Auth::id())->get();
            foreach ($children as $child) {
                $child->restore();
            }
        } else {
            $ids = $data['ids'] ?? [];
            $children = File::onlyTrashed()
                        ->where('created_by', Auth::id())
                        ->whereIn('id', $ids)->get();
            foreach ($children as $child) {
                $child->restore();
            }
        }

        return to_route('trash');
    }

    public function deleteForever (TrashFilesRequest $request) {
        $data = $request->validated();
        if ($data['all']) {
            // dd($data['all']);
            $children = File::onlyTrashed()->where('created_by', Auth::id())->get();

            foreach ($children as $child) {
                $child->deleteForever();
            }
        } else {
            $ids = $data['ids'] ?? [];

            $children = File::onlyTrashed()
                        ->where('created_by', Auth::id())
                        ->whereIn('id', $ids)->get();

            foreach ($children as $child) {
                $child->deleteForever();
            }
        }

        return to_route('trash');
    }

    public function download(FilesActionRequest $request) {

        $data = $request->validated();
        $parent = $request->parent;

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if(!$all && empty($ids)) {
            return [
                'url' => '',
                'fileName' => '',
                'message' => 'Please select files to download'
            ];
        }

        if($all) {
            $url = $this->createZip($parent->children);
            $fileName = $parent->name . '.zip';
            $errorMessage =  '';

        } else {
            $getDownloadUrlInfo = $this->getDownloadUrl($ids, $parent->name);
            $url = $getDownloadUrlInfo['url'];
            $fileName = $getDownloadUrlInfo['fileName'];
            $errorMessage = $getDownloadUrlInfo['errorMessage'];
        }
        // dd($url);
        Download::create([
            'public_file_name' => pathinfo($url,PATHINFO_BASENAME),
            'save_as_name' => $fileName,
            'user_id' => Auth::id(),
        ]);
        return [
            'url' => $url,
            'fileName' => $fileName,
            'errorMessage' => $errorMessage
        ];
    }

    private function getDownloadUrl($ids, $downloadedFileName) {
        $url = '';
        $filePublicName = '';
        $filename = '';
        if(count($ids) === 1) {
            // اذا كنا نريد تحميل ملف واحد فقظ فأنه اذا كان مجلد فأننا نحوله الى ملف مضغوظ
            // أما اذا كام ملف واحد فقظ فأننا نحمله مباشرة
            $file = File::find($ids[0]);
            if($file->is_folder) {
                if($file->children->count() === 0) {
                    return [
                        'url' => '',
                        'fileName' => '',
                        'errorMessage' => 'The folder is empty'
                    ];
                }
                $url = $this->createZip($file->children);
                $filename = $file->name . '.zip';

            }else {
                // $dest = 'public/' . pathinfo($file->storage_path, PATHINFO_BASENAME);
                $filePublicName = Str::random(). '.' . pathinfo($file->storage_path, PATHINFO_EXTENSION);
                $dest = 'downloads/' . $filePublicName;
                Storage::copy($file->storage_path, $dest);
                // $url = asset(Storage::url($dest));
                $url = route('download', ['type' => 'file', 'public_file_name' => $filePublicName]);
                $filename = $file->name;
            }
        } // else there are multuple files selected
        else {
            $files = File::query()->whereIn('id', $ids)->get();
            $url = $this->createZip($files);
            $filename = $downloadedFileName . '.zip';
        }

        return [
            'url' => $url,
            'fileName' => $filename,
            'errorMessage' => '',
        ];
    }

    private function createZip($files){
        $filePublicName =  Str::random() .'.zip';
        // $publicPath = 'public/zip/' . $zipPath;
        $downloadsPath = 'downloads/zip/' . $filePublicName;
        if(!is_dir(dirname($downloadsPath))) {
            Storage::makeDirectory(dirname($downloadsPath));
        }
        $zipFile = Storage::path($downloadsPath);
        $zip = new \ZipArchive();
        if($zip->open($zipFile, \ZipArchive::CREATE|\ZipArchive::OVERWRITE) === true) {
            $this->addFilesToZip($zip, $files);
        }
        $zip->close();
        // return Storage::url($publicPath); => /storage/zip/5jegon33snvjpo9j.zip
        // return asset(Storage::url($downloadsPath)); // => http://127.0.0.1:8000/storage/zip/LokGMg8FPnjEseaq.zip
        return route('download', ['type' => 'zip', 'public_file_name' => $filePublicName]);
    }

    private function addFilesToZip($zip, $files, $ancestors = '') {
        foreach($files as $file) {
            if($file->is_folder) {
                $this->addFilesToZip($zip, $file->children, $ancestors . $file->name . '/');
            } else {
                $zip->addFile(Storage::path($file->storage_path), $ancestors . $file->name);
            }
        }
    }

    public function addToFavourites(AddToFavouritesRequest $request) {

        $data = $request->validated();
        $starredFile = StarredFile::query()->where('file_id',$data['id'])->where('user_id', Auth::id())->first();
        $message = '';
        if($starredFile) {
            $starredFile->delete();
            $message = 'Selected files have been removed from favourites';
        }else {
            StarredFile::create([
                'file_id' => $data['id'],
                'user_id'  => Auth::id()
            ]);
            $message = 'Selected files have been added to favourites';
        }
        // return redirect()->back();
        return response()->json(['message' => $message]);
    }

    public function share(ShareFilesRequest $request) {
        $data = $request->validated();

        $user = User::query()->where('email', $data['email'])->first();
        if($user) {
            if($data['all']) {
                $files = File::query()->with(['shared' => function(Builder $query) use ($user) {
                                $query->where('user_id', $user['id']);
                            }])
                            ->where('parent_id', $data['parent_id'])->get();
            } else {
                $files = File::query()->with(['shared' => function(Builder $query) use ($user) {
                                $query->where('user_id', $user['id']);
                            }])
                            ->whereIn('id', $data['ids'])->get();
            }

            $sharedFiles = [];
            foreach($files as $file) {
                if($file->shared->count() == 0) {
                    $sharedFiles[] = [
                        'file_id' => $file->id,
                        'user_id' => $user->id
                    ];
                }
            }
            FileShare::insert($sharedFiles);
        }
    }

    public function unshare(DownloadSharedFilesRequest $request) {
        $data = $request->validated();

        $all = $data['all'];
        $ids = $data['ids'];
        if($all) {
            $files = FileShare::has('userFiles')->delete();
        }else {
            $files = FileShare::whereIn('id', $ids)->delete();
        }
    }

    public function sharedWithMe(Request $request) {
        $search = $request->search ?? '';

        $files = FileShare::whereHas('file', function(Builder $query) use($search) {
                    if($search) {
                        $query->with('user')->where('name','like','%'.$search.'%');
                    }else {
                        $query->with('user');
                    }
                })->where('user_id', Auth::id())
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->paginate(10);

        // dd($files);
        $files = ShareFileResource::collection($files);

        if($request->wantsJson()) {
            return $files;
        }
        $sourcePage = 'sharedWithMe';
        return Inertia::render('SharedFiles', compact('files', 'sourcePage'));
    }

    public function sharedByMe(Request $request) {
        $search = $request->search ?? '';
        $files = FileShare::with(['file', 'user'])
                    ->whereHas('userFiles', function(Builder $query) use($search) {
                        $query->where('name','like','%'.$search.'%');
                    })
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->paginate(10);
        // dd($files);
        $files = ShareFileResource::collection($files);

        if($request->wantsJson()) {
            return $files;
        }
        $sourcePage = 'sharedByMe';
        return Inertia::render('SharedFiles', compact('files', 'sourcePage'));
    }

    public function downloadSharedWithMe(DownloadSharedFilesRequest $request) {
        $data = $request->validated();
        // dd($data);
        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if(!$all && empty($ids)) {
            return [
                'url' => '',
                'fileName' => '',
                'message' => 'Please select files to download'
            ];
        }

        if($all) {
            $files = File::WhereHas('sharedWithMe')->get();

            $url = $this->createZip($files);

            $fileName = 'SharedWithMe.zip';
            $errorMessage =  '';

        } else {
            $getDownloadUrlInfo = $this->getDownloadUrl($ids, 'SharedWithMe');

            $url = $getDownloadUrlInfo['url'];
            $fileName = $getDownloadUrlInfo['fileName'];
            $errorMessage = $getDownloadUrlInfo['errorMessage'];
        }
        Download::create([
            'public_file_name' => pathinfo($url,PATHINFO_BASENAME),
            'save_as_name' => $fileName,
            'user_id' => Auth::id(),
        ]);
        return [
            'url' => $url,
            'fileName' => $fileName,
            'errorMessage' => $errorMessage
        ];
    }

    public function downloadSharedByMe(DownloadSharedFilesRequest $request) {
        $data = $request->validated();

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        $updatedIds = File::WhereHas('shared',function(Builder $query) use ($ids){
                                $query->WhereIn('id', $ids);
                            })->where('created_by', Auth::id())->pluck('id')->toArray();
        // dd($ids, $updatedIds);
        if(!$all && empty($ids)) {
            return [
                'url' => '',
                'fileName' => '',
                'message' => 'Please select files to download'
            ];
        }

        if($all) {
            $files = File::WhereHas('shared')->where('created_by', Auth::id())->get();

            $url = $this->createZip($files);
            $fileName = 'SharedByMe.zip';
            $errorMessage =  '';
        } else {
            $getDownloadUrlInfo = $this->getDownloadUrl($updatedIds, 'SharedByMe');
            $url = $getDownloadUrlInfo['url'];
            $fileName = $getDownloadUrlInfo['fileName'];
            $errorMessage = $getDownloadUrlInfo['errorMessage'];
        }
        Download::create([
            'public_file_name' => pathinfo($url,PATHINFO_BASENAME),
            'save_as_name' => $fileName,
            'user_id' => Auth::id(),
        ]);
        return [
            'url' => $url,
            'fileName' => $fileName,
            'errorMessage' => $errorMessage
        ];
    }

    public function downloadFromStorage($type, $public_file_name) {
        $file = Download::where('public_file_name', $public_file_name)
                        ->where('user_id', Auth::id())->first();

        if($file) {
            if($type == 'zip' && Storage::exists('downloads/zip/'.$public_file_name)) {
                return Storage::download('downloads/zip/'.$public_file_name, $file['save_as_name']);
            }elseif($type == 'file' && Storage::exists('downloads/'.$public_file_name)) {
                return Storage::download('downloads/'.$public_file_name, $file['save_as_name']);
            }
        }
        abort(404);

    }

    private function getRoot() {
        return File::query()->whereIsRoot()->where("created_by", Auth::id())->firstOrFail();
    }
}
