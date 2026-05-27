<?php


namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD;

use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\Controller;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\Traits\IndexableCrud;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\Traits\LanguageableCrud;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\Traits\ValidateModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\FilesController;
use UntitledDevelopers\KockatoosAdminCore\Models\BaseModel;
use UntitledDevelopers\KockatoosAdminCore\Models\Blob;
use UntitledDevelopers\KockatoosAdminCore\Services\ImageService;

abstract class  CrudController extends Controller
{
    use IndexableCrud, LanguageableCrud, ValidateModel;

    protected string $table;
    protected string $filesDirectory = '';
    protected string $modelClass = BaseModel::class;

    /**
     * @var array
     */
    protected array $selectColumns = [];

    public function store(Request $request): JsonResponse
    {
        $model = new $this->modelClass;
        $model->setTable($this->table);
        return response()->json($this->getModel($this->saveModel($request, $model, true)->id));
    }

    public function update(Request $request, $id): JsonResponse
    {
        return response()->json($this->getModel($this->saveModel($request, $this->findOrFailModel($id), false)->id));
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $model = $this->getModel($id);

        if ($this->safeDelete) {
            $model->deleted_at = now();
            $model->save();
        } else {
            $model->delete();
        }
        return response()->json(['message' => 'OK']);
    }


    protected abstract function saveModel(Request $request, BaseModel $model, bool $isNew): BaseModel;

    protected function initSaveModel(Request $request, BaseModel $model): mixed
    {
        $data = json_decode($request['data']);
        $model->setTable($this->table);
        if (trait_exists(ValidateModel::class) && $this->shouldValidate) {
            $this->validateModel($this->modelClass, $data, $model->id == null);
        }

        if (method_exists($this->modelClass, 'blob') && $request->hasFile('image')) {
            $this->updateBlob($request, $model);
            if (isset($data->blob_id) && $data->blob_id != null) {
                $oldBlob = Blob::find($data->blob_id);
                if ($oldBlob) {
                    app(\UntitledDevelopers\KockatoosAdminCore\Services\FileService::class)->deleteByUrl($oldBlob->url);
                }
            }
        }
        return $data;
    }

    protected function findOrFailModel($id): BaseModel|Collection|\Illuminate\Database\Eloquent\Builder|array|null
    {
        $model = BaseModel::query()->setModel(new $this->modelClass)->from($this->table)->where('id', $id)->first();
        if ($model == null)
            abort(404);
        return $model;
    }

    protected function getModel(int $id): \Illuminate\Database\Eloquent\Builder|Model
    {
        return $this->builder()->where($this->table . '.id', $id)->first()->setTable($this->table);
    }

    // TODO: refactor to use BlobService::store() — currently duplicates file/image handling logic
    protected function updateBlob(Request $request, Model $model, string $column = 'blob_id', string $requestFileName = 'image', bool $isImage = true, bool $keepName = false)
    {
        $blob = new Blob();
        if ($isImage) {
            // Check if the image is SVG
            $mimeType = $request->file($requestFileName)->getClientMimeType();
            if ($mimeType === 'image/svg+xml') {
                $uploadedFile = $request->file($requestFileName);
            } else {
                try {
                    $file = ImageService::optimizeImage($request->file($requestFileName)->getRealPath(), null);
                    $oldSize = $request->file($requestFileName)->getSize();
                    $uploadedFile = new UploadedFile($file, $request->file($requestFileName)->getClientOriginalName(), $request->file($requestFileName)->getClientMimeType());
                    if ($oldSize - $uploadedFile->getSize() < 0) {
                        $newFile = $request->file($requestFileName)->getRealPath() . '.optimized';
                        ImageService::optimizeImage($request->file($requestFileName)->getRealPath(), $newFile);
                        $uploadedFile = new UploadedFile($newFile, $request->file($requestFileName)->getClientOriginalName(), $request->file($requestFileName)->getClientMimeType());
                        if ($oldSize - $uploadedFile->getSize() < 0) {
                            //Use original file
                            $uploadedFile = $request->file($requestFileName);
                        }
                    }
                } catch (\Throwable $e) {
                    $uploadedFile = $request->file($requestFileName);
                    \Log::error($e);

                }
            }
            $blob->url = FilesController::uploadFile($uploadedFile, $this->filesDirectory);
        } else {
            if ($keepName) {
                [$url, $name] = FilesController::uploadFileKeepName($request->file($requestFileName), $this->filesDirectory);
                $blob->url = $url;
                $blob->name = $name;
            } else {
                $blob->url = FilesController::uploadFile($request->file($requestFileName), $this->filesDirectory);
            }
        }
        $blob->type = $request->file($requestFileName)->getMimeType();
        $blob->ext = $request->file($requestFileName)->getClientOriginalExtension();
        $blob->size = $request->file($requestFileName)->getSize();
        $blob->directory = $this->filesDirectory;
        if (empty($blob->name)) {
            $blob->name = basename($blob->url);
        }
        $blob->save();
        //in case of saving project's gallery
        if ($requestFileName == "gallery") {
            return $blob;
        } else {
            $model->$column = $blob->id;
        }
        return $blob;
    }


}
