<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\Traits;

use App\Models\Blob;
use Illuminate\Http\Request;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\FilesController;

trait BlobTrait
{
    public function _uploadBlob(Request $request): Blob
    {
        $blob = new Blob();
        $uploadResponse = FilesController::uploadFileWithName($request->file('file'), $this->filesDirectory);
        $blob->url = $uploadResponse['url'];
        $blob->name = $uploadResponse['name'];
        $blob->type = $request->file('file')->getMimeType();
        $blob->size = $request->file('file')->getSize();
        $blob->save();
        return $blob;
    }

    public function _deleteBlob(int $blob_id)
    {
        $blob = Blob::query()->find($blob_id);
        FilesController::deleteFile($blob->name, $blob->url);
        $blob->deleted_at = now();
        return Blob::query()->where('id', '=', $blob_id)->delete();
    }
}
