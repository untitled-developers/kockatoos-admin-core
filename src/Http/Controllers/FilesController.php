<?php


namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

class FilesController
{
    public static function uploadFile(UploadedFile $file, $directory, $extension = null): string
    {
        $filePath = $file->storeAs('public/' . $directory, Uuid::uuid1() . '.' . ($extension ?? explode('/', $file->getMimeType())[1]));
        return Storage::url($filePath);
    }
    public static function uploadFileKeepName(UploadedFile $file, $directory): array
    {
        $name = $file->getClientOriginalName();
        $filePath = $file->storeAs('public/' . $directory, $name);
        $url = Storage::url($filePath);
        return [$url, $name];
    }


    public static function uploadFileWithName(UploadedFile $file, $directory, $extension = null): array
    {
        $name = Uuid::uuid1() . '.' . ($extension ?? explode('/', $file->getMimeType())[1]);
        $filePath = $file->storeAs('public/' . $directory, $name);
        $url = Storage::url($filePath);
        return ['url' => $url, 'name' => $name];
    }

    public static function deleteFile($fileName, $directory)
    {
        $imagePath = 'public/' . $directory . '/' . $fileName;
        if (Storage::exists($imagePath))
            Storage::delete($imagePath);
    }
}
