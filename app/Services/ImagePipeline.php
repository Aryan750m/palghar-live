<?php
// app/Services/ImagePipeline.php

namespace App\Services;

use Exception;
use App\Services\Logger;

class ImagePipeline {
    /**
     * Process an uploaded image: compresses, resizes, generates WebP/AVIF versions (if supported) and outputs srcset details
     * 
     * @param array $file The uploaded file array from $_FILES
     * @param string $subDir Subdirectory in uploads ('news' or 'sections')
     * @return array Metadata about processed images
     */
    public static function process(array $file, string $subDir = 'news'): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error code: " . $file['error']);
        }

        $config = require __DIR__ . '/../Config/app.php';
        $uploadDir = $config['paths']['uploads'] . '/' . $subDir;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];

        if (!array_key_exists($mimeType, $allowedTypes)) {
            throw new Exception("Unsupported file type: " . $mimeType);
        }

        $extension = $allowedTypes[$mimeType];
        $fileHash = bin2hex(random_bytes(16));
        $baseName = $fileHash;

        // Load image resource from uploaded file
        $srcImage = self::createImageFromSource($file['tmp_name'], $mimeType);
        if (!$srcImage) {
            throw new Exception("Failed to open uploaded image resource.");
        }

        $width = imagesx($srcImage);
        $height = imagesy($srcImage);

        // Target widths for responsive images
        $sizes = [
            'thumbnail' => 300,
            'medium'    => 800,
            'large'     => 1200,
            'retina'    => 2400
        ];

        $processedFiles = [];
        $supportsWebp = function_exists('imagewebp');
        $supportsAvif = function_exists('imageavif');

        Logger::upload("Beginning image processing pipeline", [
            'filename' => $file['name'], 
            'mime' => $mimeType, 
            'avif_support' => $supportsAvif, 
            'webp_support' => $supportsWebp
        ]);

        foreach ($sizes as $sizeKey => $targetWidth) {
            // Keep aspect ratio
            if ($width > $targetWidth) {
                $targetHeight = (int)round(($height / $width) * $targetWidth);
                $resizedImg = imagecreatetruecolor($targetWidth, $targetHeight);
                
                // Preserve transparency
                if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
                    imagealphablending($resizedImg, false);
                    imagesavealpha($resizedImg, true);
                }
                
                imagecopyresampled($resizedImg, $srcImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            } else {
                // If image is smaller than target, just use original size resource
                $resizedImg = $srcImage;
                $targetWidth = $width;
                $targetHeight = $height;
            }

            $sizeName = $baseName . '_' . $sizeKey;

            // 1. Generate AVIF version if environment allows
            if ($supportsAvif) {
                $avifPath = $uploadDir . '/' . $sizeName . '.avif';
                if (@imageavif($resizedImg, $avifPath, 65)) {
                    $processedFiles[$sizeKey]['avif'] = 'uploads/' . $subDir . '/' . $sizeName . '.avif';
                } else {
                    Logger::upload("Failed generating AVIF target, continuing fallback", ['size' => $sizeKey]);
                }
            }

            // 2. Generate WebP version if supported
            if ($supportsWebp) {
                $webpPath = $uploadDir . '/' . $sizeName . '.webp';
                if (@imagewebp($resizedImg, $webpPath, 80)) {
                    $processedFiles[$sizeKey]['webp'] = 'uploads/' . $subDir . '/' . $sizeName . '.webp';
                } else {
                    Logger::upload("Failed generating WebP target, continuing fallback", ['size' => $sizeKey]);
                }
            }

            // 3. Fallback: Generate JPEG or PNG depending on original MIME type
            $fallbackExt = ($mimeType === 'image/png') ? 'png' : 'jpg';
            $fallbackPath = $uploadDir . '/' . $sizeName . '.' . $fallbackExt;
            
            if ($fallbackExt === 'png') {
                imagepng($resizedImg, $fallbackPath, 6);
            } else {
                imagejpeg($resizedImg, $fallbackPath, 85);
            }
            
            $processedFiles[$sizeKey]['fallback'] = 'uploads/' . $subDir . '/' . $sizeName . '.' . $fallbackExt;
            $processedFiles[$sizeKey]['width'] = $targetWidth;
            $processedFiles[$sizeKey]['height'] = $targetHeight;

            // Clean up resized memory resource if it was a copy
            if ($resizedImg !== $srcImage) {
                imagedestroy($resizedImg);
            }
        }

        // Generate Blur placeholder (Ultra-low res 16px WebP or base64)
        $blurPlaceholder = self::generateBlurPlaceholder($srcImage, $width, $height);
        
        imagedestroy($srcImage);

        // Generate responsive srcset strings
        $srcsetWebp = [];
        $srcsetFallback = [];
        $srcsetView = [];

        foreach ($processedFiles as $sizeKey => $data) {
            if ($sizeKey === 'thumbnail') continue; // Exclude thumbnail from general responsive sets
            
            if (isset($data['webp'])) {
                $srcsetWebp[] = $data['webp'] . ' ' . $data['width'] . 'w';
            }
            $srcsetFallback[] = $data['fallback'] . ' ' . $data['width'] . 'w';
            
            $srcsetView[$sizeKey] = [
                'path' => $data['fallback'],
                'width' => $data['width']
            ];
        }

        return [
            'original_name' => $file['name'],
            'hash'          => $fileHash,
            'blur_data'     => $blurPlaceholder,
            'thumbnail'     => $processedFiles['thumbnail']['fallback'],
            'thumbnail_webp'=> $processedFiles['thumbnail']['webp'] ?? null,
            'srcset_webp'   => implode(', ', $srcsetWebp),
            'srcset_fallback'=> implode(', ', $srcsetFallback),
            'sizes_meta'    => $processedFiles
        ];
    }

    /**
     * Load image from file based on MIME type
     */
    private static function createImageFromSource(string $filePath, string $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/webp':
                return function_exists('imagecreatefromwebp') ? imagecreatefromwebp($filePath) : null;
            case 'image/gif':
                return imagecreatefromgif($filePath);
        }
        return null;
    }

    /**
     * Generate low-res placeholder image (base64 inline source)
     */
    private static function generateBlurPlaceholder($srcImage, int $width, int $height): string {
        $blurW = 16;
        $blurH = (int)round(($height / $width) * $blurW);
        $blurImg = imagecreatetruecolor($blurW, $blurH);
        
        imagecopyresampled($blurImg, $srcImage, 0, 0, 0, 0, $blurW, $blurH, $width, $height);
        
        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($blurImg, null, 10);
            $mime = 'image/webp';
        } else {
            imagejpeg($blurImg, null, 10);
            $mime = 'image/jpeg';
        }
        $data = ob_get_clean();
        
        imagedestroy($blurImg);
        
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
