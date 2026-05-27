<?php

namespace UntitledDevelopers\KockatoosAdminCore\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use UntitledDevelopers\KockatoosAdminCore\Models\Blob;

class BlobService
{
    protected FileService $fileService;
    protected ImageService $imageService;
    protected string $disk;

    protected array $imageMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
    ];

    public function __construct(FileService $fileService, ImageService $imageService, string $disk = 'public')
    {
        $this->fileService = $fileService;
        $this->imageService = $imageService;
        $this->disk = $disk;
    }

    public function store(UploadedFile $file, string $directory, string $name = null, int $sortNumber = 0): Blob
    {
        $name = $name ?? $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        $blob = new Blob();
        $blob->name = $name;
        $blob->directory = $directory;
        $blob->type = $file->getMimeType();
        $blob->size = $file->getSize();
        $blob->ext = $extension;
        $blob->sort_number = $sortNumber;

        $fileUrl = $this->fileService->uploadFile($file, $directory, $extension);
        $blob->url = $fileUrl;

        if ($this->isImage($file)) {
            $this->optimizeImage($blob);
        }

        try {
            $blob->save();
        } catch (\Throwable $e) {
            $this->fileService->deleteByUrl($blob->url);
            if ($blob->url !== $fileUrl) {
                $this->fileService->deleteByUrl($fileUrl);
            }
            throw $e;
        }

        return $blob;
    }

    public function update(Blob $blob, UploadedFile $file, string $directory = null, string $name = null): Blob
    {
        $oldUrl = $blob->url;

        $directory = $directory ?? $blob->directory;
        $name = $name ?? $blob->name;

        $blob->name = $name;
        $blob->directory = $directory;
        $blob->type = $file->getMimeType();
        $blob->size = $file->getSize();
        $blob->ext = $file->getClientOriginalExtension();

        $fileUrl = $this->fileService->uploadFile($file, $directory, $file->getClientOriginalExtension());
        $blob->url = $fileUrl;

        if ($this->isImage($file)) {
            $this->optimizeImage($blob);
        }

        try {
            $blob->save();
        } catch (\Throwable $e) {
            $this->fileService->deleteByUrl($blob->url);
            if ($blob->url !== $fileUrl) {
                $this->fileService->deleteByUrl($fileUrl);
            }
            throw $e;
        }

        $deleted = $this->fileService->deleteByUrl($oldUrl);
        if (!$deleted) {
            Log::warning('Old blob file could not be deleted from disk.', ['url' => $oldUrl]);
        }

        return $blob;
    }

    public function delete(Blob $blob): bool
    {
        $deleted = $this->fileService->deleteByUrl($blob->url);
        if (!$deleted) {
            throw new \RuntimeException('Could not delete blob file from disk: ' . $blob->url);
        }

        $blob->delete();

        return true;
    }

    public function deleteFile(Blob $blob): bool
    {
        return $this->fileService->deleteByUrl($blob->url);
    }

    public function find(int $id): ?Blob
    {
        return Blob::find($id);
    }

    public function getByDirectory(string $directory)
    {
        return Blob::where('directory', $directory)
            ->orderBy('sort_number')
            ->get();
    }

    public function list(array $query = []): LengthAwarePaginator|Collection
    {
        $builder = Blob::query();

        if (!empty($query['searchFor'])) {
            $builder->where('name', 'LIKE', '%' . $query['searchFor'] . '%');
        }

        if (!empty($query['type'])) {
            $builder->where('type', 'LIKE', '%' . $query['type'] . '%');
        }

        if (!empty($query['directory'])) {
            $builder->where('directory', $query['directory']);
        }

        $builder->orderBy($query['sortBy'] ?? 'id', $query['sortAs'] ?? 'desc');

        if (array_key_exists('paginate', $query) && $query['paginate'] === false) {
            return $builder->get();
        }

        return $builder->paginate($query['perPage'] ?? 15);
    }

    /**
     * Annotate each blob with an `exists` attribute describing whether its
     * backing file is present on the configured public disk.
     *
     * Values:
     *  - true  → URL points at the configured disk AND the file is on disk.
     *  - false → URL points at the configured disk AND the file is NOT on disk.
     *  - null  → URL does NOT point at the configured disk (external host /
     *            CDN). Existence cannot be verified without an outbound HTTP
     *            call and is therefore not claimed either way (FR-6).
     *
     * Calls `Storage::files($dir)` exactly once per distinct directory among
     * "on-disk" blobs (FR-10).
     */
    public function attachExistence(iterable $blobs): iterable
    {
        $disk = Storage::disk($this->disk);
        $diskPrefix = parse_url($disk->url(''), PHP_URL_PATH) ?? '';

        $onDiskByDir = [];
        foreach ($blobs as $blob) {
            $urlPath = parse_url((string) $blob->url, PHP_URL_PATH) ?? '';
            if ($diskPrefix === '' || !Str::startsWith($urlPath, $diskPrefix)) {
                $blob->setAttribute('exists', null);
                continue;
            }
            $dir = (string) $blob->directory;
            $onDiskByDir[$dir][] = $blob;
        }

        foreach ($onDiskByDir as $dir => $blobsInDir) {
            $filesInDir = array_flip($disk->files($dir));
            foreach ($blobsInDir as $blob) {
                $urlPath = parse_url((string) $blob->url, PHP_URL_PATH) ?? '';
                $relativePath = ltrim(Str::after($urlPath, $diskPrefix), '/');
                $blob->setAttribute('exists', isset($filesInDir[$relativePath]));
            }
        }

        return $blobs;
    }

    protected function isImage(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), $this->imageMimeTypes);
    }

    protected function optimizeImage(Blob $blob): void
    {
        $disk = Storage::disk($this->disk);
        $filename = basename(parse_url($blob->url, PHP_URL_PATH));
        $relativePath = $blob->directory . '/' . $filename;
        $fullPath = $disk->path($relativePath);

        try {
            $webpPath = $this->imageService->optimizeImage($fullPath, null);

            if ($webpPath && is_string($webpPath)) {
                $newFilename = basename($webpPath);
                $newRelativePath = $blob->directory . '/' . $newFilename;
                $blob->url = Storage::disk($this->disk)->url($newRelativePath);
                $blob->ext = 'webp';
            }
        } catch (\Exception $e) {
            Log::error('Image optimization failed: ' . $e->getMessage());
        }
    }
}
