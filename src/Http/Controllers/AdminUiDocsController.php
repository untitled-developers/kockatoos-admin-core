<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminUiDocsController
{
    private string $vueBuildPath;

    public function __construct()
    {
        $this->vueBuildPath = base_path('node_modules/kockatoos-admin-ui/docs');
    }

    /**
     * Serve the main Vue application
     */
    public function index()
    {
        $indexPath = $this->vueBuildPath . '/index.html';

        if (!File::exists($indexPath)) {
            abort(404, 'Vue application not found');
        }

        $content = File::get($indexPath);

        return response($content)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Serve Vue app assets (CSS, JS, images, etc.)
     */
    public function assets(Request $request, $path)
    {

        $filePath = $this->vueBuildPath . '/assets/' . $path;
        if (!File::exists($filePath) || !$this->isValidAssetPath($path)) {
            abort(404);
        }

        $mimeType = $this->getMimeType($filePath);
        $content = File::get($filePath);


        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000'); // 1 year cache
    }

    /**
     * Handle Vue Router (catch-all for SPA routing)
     */
    public function catchAll()
    {
        return $this->index();
    }

    /**
     * Validate that the requested path is a legitimate asset
     */
    private function isValidAssetPath(string $path): bool
    {
        if (str_contains($path, '..') || str_starts_with($path, '/')) {
            return false;
        }

        $allowedExtensions = ['js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, $allowedExtensions);
    }

    /**
     * Get MIME type for the file
     */
    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
