<?php


namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;


use Illuminate\Http\UploadedFile;
use UntitledDevelopers\KockatoosAdminCore\Services\FileService;

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


    public static function deleteFile($fileName, $directory)
    {
        return self::$fileService->deleteFile($fileName, $directory);
    }
}
