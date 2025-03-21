<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use UntitledDevelopers\KockatoosAdminCore\Http\Services\FileService;

beforeEach(function () {
    // Create a fake storage disk for testing
    Storage::fake('public');
    Storage::fake('custom');

    // Create instance of the service with the public disk
    $this->fileService = new FileService('public');

    // Also create an instance with a custom disk for testing different disks
    $this->customDiskService = new FileService('custom');
});

test('it can upload a file and return a url', function () {
    // Create a fake file
    $file = UploadedFile::fake()->image('test.jpg');

    // Upload the file
    $url = $this->fileService->uploadFile($file, 'uploads');

    // Check that the URL is a string
    expect($url)->toBeString();

    // Extract the filename from the URL
    $filename = basename($url);

    // Check that the file was stored correctly
    Storage::disk('public')->assertExists('uploads/' . $filename);
});

test('it can upload files with different mime types', function () {
    // Test with different file types
    $fileTypes = [
        'image' => UploadedFile::fake()->image('photo.jpg'),
        'pdf' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        'text' => UploadedFile::fake()->create('readme.txt', 50, 'text/plain'),
    ];

    foreach ($fileTypes as $type => $file) {
        $url = $this->fileService->uploadFile($file, 'uploads/' . $type);

        // Check that the URL is a string
        expect($url)->toBeString();

        // Extract the path from the URL
        $path = parse_url($url, PHP_URL_PATH);
        $filename = basename($path);

        // The file should exist on the disk
        Storage::disk('public')->assertExists('uploads/' . $type . '/' . $filename);

        // Check that the file has the correct extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if ($type === 'image') {
            expect($extension)->toBe('jpg');
        } elseif ($type === 'pdf') {
            expect($extension)->toBe('pdf');
        } elseif ($type === 'text') {
            expect($extension)->toBe('plain');
        }
    }
});

test('it uses the correct disk for storage', function () {
    // Create a fake file
    $file = UploadedFile::fake()->image('test.jpg');

    // Upload to the custom disk
    $url = $this->customDiskService->uploadFile($file, 'uploads');

    // File should exist on the custom disk, not the public disk
    $filename = basename($url);
    Storage::disk('custom')->assertExists('uploads/' . $filename);
    Storage::disk('public')->assertMissing('uploads/' . $filename);
});

test('it can specify custom extension when uploading', function () {
    // Create a fake file
    $file = UploadedFile::fake()->image('test.jpg');

    // Upload with a custom extension
    $url = $this->fileService->uploadFile($file, 'uploads', 'custom-ext');

    // Check that the URL contains the custom extension
    expect($url)->toContain('.custom-ext');

    // Extract the filename from the URL
    $filename = basename($url);

    // Check that the file was stored with the custom extension
    Storage::disk('public')->assertExists('uploads/' . $filename);
});

test('it deletes existing files', function () {
    // Create and upload a fake file first
    $file = UploadedFile::fake()->image('test.jpg');
    $url = $this->fileService->uploadFile($file, 'uploads');

    // Extract the filename from the URL
    $filename = basename($url);

    // Confirm the file exists
    Storage::disk('public')->assertExists('uploads/' . $filename);

    // Now delete the file
    $result = $this->fileService->deleteFile($filename, 'uploads');

    // Check the deletion was successful
    expect($result)->toBeTrue();

    // Verify the file is gone
    Storage::disk('public')->assertMissing('uploads/' . $filename);
});

test('it returns false when deleting non-existent files', function () {
    // Try to delete a file that doesn't exist
    $result = $this->fileService->deleteFile('non-existent-file.jpg', 'uploads');

    // It should return false
    expect($result)->toBeFalse();
});

test('it generates unique filenames', function () {
    // Use reflection to access the protected method
    $reflector = new ReflectionClass(FileService::class);
    $method = $reflector->getMethod('generateUniqueFilename');
    $method->setAccessible(true);

    // Generate two filenames with the same extension
    $filename1 = $method->invoke($this->fileService, 'jpg');
    $filename2 = $method->invoke($this->fileService, 'jpg');

    // They should be different
    expect($filename1)->not->toBe($filename2);

    // Both should have the correct extension
    expect($filename1)->toEndWith('.jpg');
    expect($filename2)->toEndWith('.jpg');
});

test('it extracts extension from mime type correctly', function () {
    // Use reflection to access the protected method
    $reflector = new ReflectionClass(FileService::class);
    $method = $reflector->getMethod('getExtensionFromMimeType');
    $method->setAccessible(true);

    // Create various fake files with different mime types
    $imageFile = UploadedFile::fake()->image('photo.jpg');     // image/jpeg
    $pdfFile = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
    $textFile = UploadedFile::fake()->create('file.txt', 50, 'text/plain');

    // Check the extracted extensions
    expect($method->invoke($this->fileService, $imageFile))->toBe('jpg');
    expect($method->invoke($this->fileService, $pdfFile))->toBe('pdf');
    expect($method->invoke($this->fileService, $textFile))->toBe('plain');
});

test('it builds correct storage paths', function () {
    // Use reflection to access the protected method
    $reflector = new ReflectionClass(FileService::class);
    $method = $reflector->getMethod('getPathForStorage');
    $method->setAccessible(true);

    // Test with various directories and filenames
    $paths = [
        ['uploads', 'file.jpg', 'uploads/file.jpg'],
        ['images/avatars', 'user.png', 'images/avatars/user.png'],
        ['', 'test.pdf', 'test.pdf'],  // Updated expectation to remove leading slash
        ['docs/', 'report.docx', 'docs/report.docx'],  // Fixed double slash issue
    ];

    foreach ($paths as [$directory, $filename, $expected]) {
        $result = $method->invoke($this->fileService, $directory, $filename);
        expect($result)->toBe($expected);
    }
});

test('it returns correct disk path', function () {
    // Use reflection to access the protected method
    $reflector = new ReflectionClass(FileService::class);
    $method = $reflector->getMethod('getDiskPath');
    $method->setAccessible(true);

    // Test with the default public disk
    expect($method->invoke($this->fileService))->toBe('public');

    // Test with the custom disk
    expect($method->invoke($this->customDiskService))->toBe('custom');
});


