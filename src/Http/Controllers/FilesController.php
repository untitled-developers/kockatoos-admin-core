<?php


namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;


use Illuminate\Http\UploadedFile;
use UntitledDevelopers\KockatoosAdminCore\Http\Services\FileService;

class FilesController
{
    protected static FileService $fileService;

    public function __construct(FileService $fileService)
    {
        self::$fileService = $fileService;

    }

    public static function uploadFile(UploadedFile $file, $directory, $extension = null): string
    {
        return self::$fileService->uploadFile($file, $directory, $extension);
    }


//    public static function uploadFileWithName(UploadedFile $file, $directory, $extension = null): array
//    {
//        $name = Uuid::uuid1() . '.' . ($extension ?? explode('/', $file->getMimeType())[1]);
//        $filePath = $file->storeAs('public/' . $directory, $name);
//        $url = Storage::url($filePath);
//        return ['url' => $url, 'name' => $name];
//    }

    public static function deleteFile($fileName, $directory)
    {
        return self::$fileService->deleteFile($fileName, $directory);
    }
}
