<?php

namespace UntitledDevelopers\KockatoosAdminCore\Models;


class Blob extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'url',
        'type',
        'size',
        'ext',
        'name',
        'directory',
        'sort_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'size' => 'double',
        'sort_number' => 'integer',
    ];

    /**
     * Get the full URL for the blob
     *
     * @return string
     */
    public function getFullUrlAttribute(): string
    {
        return $this->url;
    }

    /**
     * Check if the blob is an image
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return str_starts_with($this->type, 'image/');
    }

    /**
     * Format the file size for human readability
     *
     * @return string
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get only the filename without the path
     *
     * @return string
     */
    public function getFilenameAttribute(): string
    {
        $urlPath = parse_url($this->url, PHP_URL_PATH);
        return basename($urlPath);
    }
}
