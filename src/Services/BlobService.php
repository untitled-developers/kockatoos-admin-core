<?php

namespace UntitledDevelopers\KockatoosAdminCore\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use UntitledDevelopers\KockatoosAdminCore\Models\Blob;

class BlobService
{
    protected FileService $fileService;
    protected ImageService $imageService;
    protected string $disk;

    /**
     * Image MIME types that should be optimized
     */
    protected array $imageMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp'
    ];

    public function __construct(FileService $fileService = null, ImageService $imageService = null, string $disk = 'public')
    {
        $this->fileService = $fileService ?? new FileService($disk);
        $this->imageService = $imageService ?? new ImageService();
        $this->disk = $disk;
    }

    /**
     * Store a new blob with its associated file
     *
     * @param UploadedFile $file The file to upload
     * @param string $directory The directory to store the file
     * @param string|null $name Custom name for the blob (defaults to original filename)
     * @param int $sortNumber Sort order for the blob
     * @return Blob
     */
    public function store(UploadedFile $file, string $directory, string $name = null, int $sortNumber = 0): Blob
    {
        // Use original filename if no name provided
        $name = $name ?? $file->getClientOriginalName();

        // Get file extension
        $extension = $file->getClientOriginalExtension();

        // Create a model instance first
        $blob = new Blob();
        $blob->name = $name;
        $blob->directory = $directory;
        $blob->type = $file->getMimeType();
        $blob->size = $file->getSize();
        $blob->ext = $extension;
        $blob->sort_number = $sortNumber;

        // Upload the file
        $fileUrl = $this->fileService->uploadFile($file, $directory, $extension);
        $blob->url = $fileUrl;

        // For image files, handle optimization
        if ($this->isImage($file)) {
            $this->optimizeImage($blob);
        }

        $blob->save();

        return $blob;
    }

    /**
     * Update an existing blob with a new file
     *
     * @param Blob $blob The blob to update
     * @param UploadedFile $file The new file
     * @param string|null $directory Optional new directory
     * @param string|null $name Optional new name
     * @return Blob
     */
    public function update(Blob $blob, UploadedFile $file, string $directory = null, string $name = null): Blob
    {
        // Delete the old file first
        $this->deleteFile($blob);

        // Use existing values if not provided
        $directory = $directory ?? $blob->directory;
        $name = $name ?? $blob->name;

        // Update model with new file information
        $blob->name = $name;
        $blob->directory = $directory;
        $blob->type = $file->getMimeType();
        $blob->size = $file->getSize();
        $blob->ext = $file->getClientOriginalExtension();

        // Upload the new file
        $fileUrl = $this->fileService->uploadFile($file, $directory, $file->getClientOriginalExtension());
        $blob->url = $fileUrl;

        // For image files, handle optimization
        if ($this->isImage($file)) {
            $this->optimizeImage($blob);
        }

        $blob->save();

        return $blob;
    }

    /**
     * Delete a blob and its associated file
     *
     * @param Blob $blob The blob to delete
     * @return bool
     */
    public function delete(Blob $blob): bool
    {
        // Delete the physical file
        $fileDeleted = $this->deleteFile($blob);

        // Delete the database record
        $blob->delete();

        return $fileDeleted;
    }

    /**
     * Delete just the physical file associated with a blob
     *
     * @param Blob $blob The blob whose file should be deleted
     * @return bool
     */
    public function deleteFile(Blob $blob): bool
    {
        // Extract filename from URL
        $path = parse_url($blob->url, PHP_URL_PATH);
        $filename = basename($path);

        // Delete the file using FileService
        return $this->fileService->deleteFile($filename, $blob->directory);
    }

    /**
     * Check if a file is an image based on its MIME type
     *
     * @param UploadedFile $file The file to check
     * @return bool
     */
    protected function isImage(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), $this->imageMimeTypes);
    }

    /**
     * Optimize an image blob
     *
     * @param Blob $blob The image blob to optimize
     * @return void
     */
    protected function optimizeImage(Blob &$blob): void
    {
        // Get the full storage path for the image
        $disk = Storage::disk($this->disk);
        $urlPath = parse_url($blob->url, PHP_URL_PATH);
        $relativePath = Str::after($urlPath, '/storage/');
        $fullPath = $disk->path($relativePath);

        try {
            // Optimize the image
            $webpPath = $this->imageService->optimizeImage($fullPath, $fullPath);

            // If webp conversion was successful, update the blob URL and extension
            if ($webpPath && is_string($webpPath)) {
                $webpUrlPath = Str::after($webpPath, public_path('storage/'));
                $blob->url = Storage::disk($this->disk)->url($webpUrlPath);
                $blob->ext = 'webp';
            }
        } catch (\Exception $e) {
            // Log error but continue
            \Illuminate\Support\Facades\Log::error('Image optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Find a blob by ID
     *
     * @param int $id The blob ID
     * @return Blob|null
     */
    public function find(int $id): ?Blob
    {
        return Blob::find($id);
    }

    /**
     * Get blobs for a specific directory
     *
     * @param string $directory The directory to filter by
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByDirectory(string $directory)
    {
        return Blob::where('directory', $directory)
            ->orderBy('sort_number')
            ->get();
    }
}
