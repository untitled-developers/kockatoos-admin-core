<?php

namespace UntitledDevelopers\KockatoosAdminCore\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

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

    /**
     * @deprecated since 1.2.0 Use deleteByUrl() instead.
     */
    public function deleteFile($fileName, $directory): bool
    {
        $path = $this->getPathForStorage($directory, $fileName);

        if (Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->delete($path);
        }
        return false;
    }

    public function deleteByUrl(string $url): bool
    {
        $urlPath = parse_url($url, PHP_URL_PATH);
        $basePath = parse_url(Storage::disk($this->disk)->url(''), PHP_URL_PATH);
        $relativePath = ltrim(Str::after($urlPath, $basePath), '/');

        if (Storage::disk($this->disk)->exists($relativePath)) {
            return Storage::disk($this->disk)->delete($relativePath);
        }
        return false;
    }

    protected function getExtensionFromMimeType(UploadedFile $file): string
    {
        return match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/svg+xml' => 'svg',
            default => last(explode('/', $file->getMimeType())),
        };
    }

    public function generateUniqueFilename(string $extension): string
    {
        return Uuid::uuid1() . '.' . $extension;
    }

    public function getPathForStorage(string $directory, string $filename): string
    {
        // Ensure directory doesn't have trailing slash
        $directory = rtrim($directory, '/');

        // Add a slash only if directory is not empty
        return $directory !== '' ? $directory . '/' . $filename : $filename;
    }



}
