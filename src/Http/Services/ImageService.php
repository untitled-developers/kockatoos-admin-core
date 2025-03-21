<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Services;

use Imagick;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class ImageService
{
    /**
     * @throws \ImagickException
     */
    public static function webpConvert2($pathToImage, $compression_quality = 90): bool|string
    {
        // check if file exists
        if (!file_exists($pathToImage)) {
            return false;
        }

        if (exif_imagetype($pathToImage) === false) {
            return false;
        }
        $file_type = exif_imagetype($pathToImage);
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
        if (function_exists('imagewebp')) {
            switch ($file_type) {
                case '1': //IMAGETYPE_GIF
                    $image = imagecreatefromgif($pathToImage);
                    break;
                case '2': //IMAGETYPE_JPEG
                    $image = imagecreatefromjpeg($pathToImage);
                    break;
                case '3': //IMAGETYPE_PNG
                    $image = imagecreatefrompng($pathToImage);
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    break;
                case '6': // IMAGETYPE_BMP
                    $image = imagecreatefrombmp($pathToImage);
                    break;
                case '18': //IMAGETYPE_Webp
                    $image = imagecreatefromwebp($pathToImage);
                    break;
                case '16': //IMAGETYPE_XBM
                    $image = imagecreatefromxbm($pathToImage);
                    break;
                default:
                    return false;
            }
            // Save the image
            $result = imagewebp($image, $output_file, $compression_quality);
            if (false === $result) {
                return false;
            }
            // Free up memory
            imagedestroy($image);
            return $output_file;
        } elseif (class_exists('Imagick')) {
            $image = new Imagick();
            $image->readImage($pathToImage);
            if ($file_type == "3") {
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality($compression_quality);
                $image->setOption('webp:lossless', 'true');
            }
            $image->writeImage($output_file);
            return $output_file;
        }
        return false;
    }

    public static function optimizeImageCompression($pathToImage, $pathToOutput): void
    {
        if ($pathToOutput)
            ImageOptimizer::optimize($pathToImage, $pathToOutput);
        else
            ImageOptimizer::optimize($pathToImage);
    }

    /**
     * @throws \ImagickException
     */
    public static function optimizeImage($pathToImage, $pathToOutput): bool|string
    {
        $webpFile = self::webpConvert2($pathToImage);
        self::optimizeImageCompression($pathToImage, $pathToOutput);
        return $webpFile;
    }

}
