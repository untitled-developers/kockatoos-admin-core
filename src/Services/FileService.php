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

    public function disk(): string
    {
        return $this->disk;
    }

    public function diskBaseUrl(): string
    {
        return parse_url(Storage::disk($this->disk)->url(''), PHP_URL_PATH) ?? '/';
    }

    public function relativePathFromUrl(string $url): ?string
    {
        $urlPath = parse_url($url, PHP_URL_PATH);
        if ($urlPath === null || $urlPath === false) {
            return null;
        }

        $basePath = $this->diskBaseUrl();
        $basePathTrimmed = rtrim($basePath, '/');

        if ($basePathTrimmed !== '' && !str_starts_with($urlPath, $basePathTrimmed)) {
            return null;
        }

        return ltrim(Str::after($urlPath, $basePath), '/');
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
        $relativePath = $this->relativePathFromUrl($url);
        if ($relativePath === null || $relativePath === '') {
            return false;
        }

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
