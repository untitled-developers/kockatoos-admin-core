<?php

namespace UntitledDevelopers\KockatoosAdminCore\Services;

use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class ImageService
{
    public static function webpConvert2($pathToImage, $compression_quality = 90): bool|string
    {
        // check if file exists
        if (!file_exists($pathToImage)) {
            return false;
        }

        $file_type = exif_imagetype($pathToImage);
        if ($file_type === false) {
            return false;
        }
        //https://www.php.net/manual/en/function.exif-imagetype.php
        //exif_imagetype($file);
        // 1    IMAGETYPE_GIF
        // 2    IMAGETYPE_JPEG
        // 3    IMAGETYPE_PNG
        // 6    IMAGETYPE_BMP
        // 15   IMAGETYPE_WBMP
        // 16   IMAGETYPE_XBM
        $output_file = $pathToImage . '.webp';
        if (file_exists($output_file)) {
            return $output_file;
        }
        if (!function_exists('imagewebp')) {
            return false;
        }

        switch ($file_type) {
            case IMAGETYPE_GIF:
                $image = self::processGifForWebpConversion($pathToImage);
                break;
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($pathToImage);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($pathToImage);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            case IMAGETYPE_BMP:
                $image = imagecreatefrombmp($pathToImage);
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($pathToImage);
                break;
            case IMAGETYPE_XBM:
                $image = imagecreatefromxbm($pathToImage);
                break;
            default:
                return false;
        }

        $result = imagewebp($image, $output_file, $compression_quality);
        if (false === $result) {
            return false;
        }

        imagedestroy($image);
        return $output_file;
    }

    public static function optimizeImageCompression($pathToImage, $pathToOutput): void
    {
        if ($pathToOutput) {
            ImageOptimizer::optimize($pathToImage, $pathToOutput);
        } else {
            ImageOptimizer::optimize($pathToImage);
        }
    }

    public static function optimizeImage($pathToImage, $pathToOutput): bool|string
    {
        $webpFile = self::webpConvert2($pathToImage);
        self::optimizeImageCompression($pathToImage, $pathToOutput);
        return $webpFile;
    }

    protected static function processGifForWebpConversion($pathToImage)
    {
        $image = imagecreatefromgif($pathToImage);
        // Convert palette image to true color for WebP compatibility
        $true_color_image = imagecreatetruecolor(imagesx($image), imagesy($image));
        // Preserve transparency
        imagealphablending($true_color_image, false);
        imagesavealpha($true_color_image, true);
        $transparent = imagecolorallocatealpha($true_color_image, 255, 255, 255, 127);
        imagefilledrectangle($true_color_image, 0, 0, imagesx($image), imagesy($image), $transparent);
        // Copy the palette image to the true color image
        imagecopy($true_color_image, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);
        return $true_color_image;

    }

}

