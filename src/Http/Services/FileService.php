<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Services;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;

class FileService
{
    protected string $disk;

    public function __construct(string $disk = 'public')
    {
        $this->disk = $disk;
    }


    public function uploadFile(UploadedFile $file, $directory, $extension = null): string
    {
        $extension = $extension ?? $this->getExtensionFromMimeType($file);
        $filename = $this->generateUniqueFilename($extension);

        $path = $this->getPathForStorage($directory, $filename);
        $file->storeAs($directory, $filename, ['disk' => $this->disk]);

        return Storage::disk($this->disk)->url($path);
    }

    public function deleteFile($fileName, $directory): bool
    {
        $path = $this->getPathForStorage($directory, $fileName);

        if (Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->delete($path);
        }
        return false;
    }

    protected function getExtensionFromMimeType(UploadedFile $file): string
    {
        $mimeTypeParts = explode('/', $file->getMimeType());
        $extension = end($mimeTypeParts);

        // Handle special cases
        if ($extension === 'jpeg') {
            return 'jpg';
        }

        return $extension;
    }

    protected function generateUniqueFilename(string $extension): string
    {
        return Uuid::uuid1() . '.' . $extension;
    }

    protected function getPathForStorage(string $directory, string $filename): string
    {
        // Ensure directory doesn't have trailing slash
        $directory = rtrim($directory, '/');

        // Add a slash only if directory is not empty
        return $directory !== '' ? $directory . '/' . $filename : $filename;
    }

    protected function getDiskPath(): string
    {
        return $this->disk;
    }


}
