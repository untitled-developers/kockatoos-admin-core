<?php

namespace UntitledDevelopers\KockatoosAdminCore\Tests\Unit;

use Illuminate\Http\Testing\FileFactory;
use Illuminate\Support\Facades\Storage;
use UntitledDevelopers\KockatoosAdminCore\Services\ImageService;

beforeEach(function () {
    // Set up a fake storage disk for testing
    Storage::fake('local');
});

it('converts jpg image to webp', function () {
    // Create a test jpg image
    $factory = new FileFactory();
    $file = $factory->image('test.jpg', 500, 500)->store('images', 'local');
    $filePath = Storage::disk('local')->path($file);

    // Execute the conversion
    $webpPath = ImageService::webpConvert2($filePath);

    // Assert the webp file was created
    expect($webpPath)->toBe($filePath . '.webp')
        ->and(file_exists($webpPath))->toBeTrue();

    // Check that the file type is webp
    $imageType = exif_imagetype($webpPath);
    expect($imageType)->toBe(18); // IMAGETYPE_WEBP
});

it('converts png image to webp', function () {
    // Create a test png image
    $factory = new FileFactory();
    $file = $factory->image('test.png', 500, 500)->store('images', 'local');
    $filePath = Storage::disk('local')->path($file);

    // Execute the conversion
    $webpPath = ImageService::webpConvert2($filePath);

    // Assert the webp file was created
    expect($webpPath)->toBe($filePath . '.webp');
    expect(file_exists($webpPath))->toBeTrue();
});

it('returns false when the input file does not exist', function () {
    $result = ImageService::webpConvert2('non-existent-file.jpg');
    expect($result)->toBeFalse();
});

it('optimizes image compression', function () {
    // Create a test jpg image
    $factory = new FileFactory();
    $file = $factory->image('test.jpg', 500, 500)->store('images', 'local');
    $filePath = Storage::disk('local')->path($file);
    $newPath = Storage::disk('local')->path('images/optimized-test.jpg');

    // Get the original file size
    $originalSize = filesize($filePath);

    // Execute optimization with a new file path
    ImageService::optimizeImageCompression($filePath, $newPath);

    // Assert new file exists
    expect(file_exists($newPath))->toBeTrue();

    // Alternative test - optimize in place
    $inPlacePath = Storage::disk('local')->path('images/in-place-test.jpg');
    copy($filePath, $inPlacePath);
    ImageService::optimizeImageCompression($inPlacePath, null);

    // File should still exist after in-place optimization
    expect(file_exists($inPlacePath))->toBeTrue();
});

it('optimizes image and converts to webp', function () {
    // Create a test jpg image
    $factory = new FileFactory();
    $file = $factory->image('test.jpg', 500, 500)->store('images', 'local');
    $filePath = Storage::disk('local')->path($file);
    $newPath = Storage::disk('local')->path('images/optimized-test.jpg');

    // Execute full optimization
    $webpPath = ImageService::optimizeImage($filePath, $newPath);

    // Assert the webp file was created
    expect($webpPath)->toBe($filePath . '.webp');
    expect(file_exists($webpPath))->toBeTrue();

    // Assert the optimized file was created
    expect(file_exists($newPath))->toBeTrue();
});

// Create test specifically for GIF conversion
it('converts gif image to webp', function () {
    // Skip test if GD doesn't support GIF
    if (!function_exists('imagecreatefromgif')) {
        $this->markTestSkipped('GD library does not support GIF.');
    }

    // Make sure the images directory exists
    Storage::disk('local')->makeDirectory('images');

    // Create a GIF file - since FileFactory doesn't support GIF directly
    $gifPath = Storage::disk('local')->path('images/test.gif');

    // Create a simple GIF
    $image = imagecreate(100, 100);
    $background = imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image, 0, 0, 255);
    imagestring($image, 5, 20, 40, 'Test GIF', $text_color);

    // Make sure the directory exists
    $imageDir = dirname($gifPath);
    if (!file_exists($imageDir)) {
        mkdir($imageDir, 0777, true);
    }

    imagegif($image, $gifPath);
    imagedestroy($image);

    // Check file exists and has size greater than 0
    expect(file_exists($gifPath))->toBeTrue();
    expect(filesize($gifPath))->toBeGreaterThan(0);

    // Execute the conversion
    $webpPath = ImageService::webpConvert2($gifPath);

    // Assert the webp file was created
    expect($webpPath)->toBe($gifPath . '.webp');
    expect(file_exists($webpPath))->toBeTrue();
});

// Test with existing webp file
it('returns the path when output file already exists', function () {
    // Create a test jpg image
    $factory = new FileFactory();
    $file = $factory->image('test.jpg', 500, 500)->store('images', 'local');
    $filePath = Storage::disk('local')->path($file);

    // Create a mock webp file
    $webpPath = $filePath . '.webp';
    file_put_contents($webpPath, 'mock content');

    // Execute conversion - should just return existing webp path
    $result = ImageService::webpConvert2($filePath);

    // Assert the existing path was returned
    expect($result)->toBe($webpPath);
});

// Test with invalid image type
it('returns false for unsupported image types', function () {
    // Create a text file pretending to be an image
    $textPath = 'images/fake-image.txt';
    Storage::disk('local')->put($textPath, 'This is not an image');
    $fullPath = Storage::disk('local')->path($textPath);

    // Execute conversion - should return false
    $result = ImageService::webpConvert2($fullPath);

    // Assert false is returned
    expect($result)->toBeFalse();
});
