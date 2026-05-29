<?php

namespace UntitledDevelopers\KockatoosAdminCore\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\StorageAttributes;

class BlobReconciliationService
{
    protected FileService $fileService;
    protected string $disk;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
        $this->disk = $fileService->disk();
    }

    /**
     * Take a single snapshot of the configured disk. Returns a map keyed by
     * the object's relative key → ['size' => int|null, 'last_modified' => int|null].
     * Returns null when the listing fails (degraded mode — Decision 9).
     */
    public function snapshot(): ?array
    {
        try {
            $contents = Storage::disk($this->disk)->listContents('', true);

            $snapshot = [];
            foreach ($contents as $entry) {
                /** @var StorageAttributes $entry */
                if (!$entry->isFile()) {
                    continue;
                }

                $key = $entry->path();
                $size = null;
                $lastModified = null;

                if (method_exists($entry, 'fileSize')) {
                    try { $size = $entry->fileSize(); } catch (\Throwable) { $size = null; }
                }
                if (method_exists($entry, 'lastModified')) {
                    try { $lastModified = $entry->lastModified(); } catch (\Throwable) { $lastModified = null; }
                }

                $snapshot[$key] = [
                    'size' => $size,
                    'last_modified' => $lastModified,
                ];
            }

            return $snapshot;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Attach an `exists` flag to each blob using a single shared snapshot.
     * - exists = true  → key resolved and present in snapshot
     * - exists = false → key resolved and absent
     * - exists = null  → URL was unverifiable (different host/bucket)
     */
    public function attachExistence(iterable $blobs, ?array $snapshot): Collection
    {
        $result = collect();

        foreach ($blobs as $blob) {
            $array = $this->blobToArray($blob);
            $url = $array['url'] ?? null;

            if ($snapshot === null) {
                $array['exists'] = null;
            } else {
                $key = $url ? $this->fileService->relativePathFromUrl($url) : null;
                if ($key === null || $key === '') {
                    $array['exists'] = null;
                } else {
                    $array['exists'] = array_key_exists($key, $snapshot);
                }
            }

            $result->push($array);
        }

        return $result;
    }

    /**
     * Build the full reconciled set: blobs enriched with status + key, plus
     * synthetic entries for files on disk with no blob row.
     */
    public function reconcile(iterable $blobs): array
    {
        $snapshot = $this->snapshot();

        if ($snapshot === null) {
            $degraded = [];
            foreach ($blobs as $blob) {
                $array = $this->blobToArray($blob);
                $array['status'] = 'unverifiable';
                $array['exists'] = null;
                $array['key'] = null;
                $array['tree_directory'] = $array['directory'] ?? '';
                $array['error'] = 'disk_unreachable';
                $degraded[] = $array;
            }
            return $degraded;
        }

        $reconciled = [];
        $consumed = [];

        foreach ($blobs as $blob) {
            $array = $this->blobToArray($blob);
            $url = $array['url'] ?? null;
            $key = $url ? $this->fileService->relativePathFromUrl($url) : null;

            if ($key === null || $key === '') {
                $array['status'] = 'unverifiable';
                $array['exists'] = null;
                $array['key'] = null;
                $array['tree_directory'] = $array['directory'] ?? '';
            } elseif (array_key_exists($key, $snapshot)) {
                $array['status'] = 'matched';
                $array['exists'] = true;
                $array['key'] = $key;
                $array['tree_directory'] = self::dirnameOfKey($key);
                $consumed[$key] = true;
            } else {
                $array['status'] = 'db_only';
                $array['exists'] = false;
                $array['key'] = $key;
                $array['tree_directory'] = self::dirnameOfKey($key);
            }

            $reconciled[] = $array;
        }

        foreach ($snapshot as $key => $meta) {
            if (isset($consumed[$key])) {
                continue;
            }
            $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
            $reconciled[] = [
                'id' => null,
                'name' => basename($key),
                'directory' => null,
                'tree_directory' => self::dirnameOfKey($key),
                'key' => $key,
                'url' => Storage::disk($this->disk)->url($key),
                'type' => self::mimeFromExt($ext),
                'ext' => $ext ?: null,
                'size' => $meta['size'],
                'updated_at' => $meta['last_modified']
                    ? date(DATE_ATOM, $meta['last_modified'])
                    : null,
                'created_at' => null,
                'base_url' => null,
                'sort_number' => null,
                'deleted_at' => null,
                'status' => 'disk_only',
                'exists' => true,
            ];
        }

        return $reconciled;
    }

    protected function blobToArray($blob): array
    {
        if (is_array($blob)) {
            return $blob;
        }
        if (method_exists($blob, 'toArray')) {
            return $blob->toArray();
        }
        return (array) $blob;
    }

    protected static function dirnameOfKey(string $key): string
    {
        $dir = dirname($key);
        return $dir === '.' ? '' : $dir;
    }

    protected static function mimeFromExt(string $ext): ?string
    {
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'pdf' => 'application/pdf',
            default => null,
        };
    }
}
