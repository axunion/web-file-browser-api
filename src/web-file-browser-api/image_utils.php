<?php

declare(strict_types=1);

/**
 * Compress and resize an image file to fit within specified dimensions and quality.
 *
 * @param string $src     Path to the source image file.
 * @param string $dst     Path where the output image should be saved.
 * @param int    $maxWidth  Maximum width in pixels.
 * @param int    $maxHeight Maximum height in pixels.
 * @param int    $jpegQuality Quality for JPEG (0-100).
 * @param int    $pngCompressionLevel Compression level for PNG (0-9).
 * @throws RuntimeException If the image cannot be processed or saved.
 */
function compressResizeImage(
    string $src,
    string $dst,
    int $maxWidth = 1920,
    int $maxHeight = 1080,
    int $jpegQuality = 85,
    int $pngCompressionLevel = 6
): void {
    $info = @getimagesize($src);

    if ($info === false) {
        throw new RuntimeException("Cannot read image info: {$src}");
    }

    [$width, $height, $type] = $info;
    $ratio = min(1.0, $maxWidth / $width, $maxHeight / $height);
    $newW = (int)($width * $ratio);
    $newH = (int)($height * $ratio);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImg = @imagecreatefromjpeg($src);
            break;
        case IMAGETYPE_PNG:
            $srcImg = @imagecreatefrompng($src);
            break;
        default:
            throw new RuntimeException('Unsupported image type: ' . $src);
    }

    if ($srcImg === false) {
        throw new RuntimeException("Failed to create image resource from: {$src}");
    }

    $dstImg = imagecreatetruecolor($newW, $newH);

    if ($dstImg === false) {
        imagedestroy($srcImg);
        throw new RuntimeException('Failed to create destination image resource.');
    }

    if ($type === IMAGETYPE_PNG) {
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
    }

    if (!imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $width, $height)) {
        imagedestroy($srcImg);
        imagedestroy($dstImg);
        throw new RuntimeException('Failed to resample image.');
    }

    if ($type === IMAGETYPE_JPEG) {
        if (!imagejpeg($dstImg, $dst, $jpegQuality)) {
            throw new RuntimeException("Failed to save JPEG to {$dst}");
        }
    } else {
        if (!imagepng($dstImg, $dst, $pngCompressionLevel)) {
            throw new RuntimeException("Failed to save PNG to {$dst}");
        }
    }

    imagedestroy($srcImg);
    imagedestroy($dstImg);
}
