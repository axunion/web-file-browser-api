<?php

declare(strict_types=1);

require_once __DIR__ . '/filepath_utils.php';

/**
 * Moves the specified file to the target directory, handling cross-filesystem
 * and naming collisions robustly.
 *
 * @param string $filePath       Absolute or relative path of the file to move.
 * @param string $destinationDir Absolute or relative path of the target directory.
 * @return string                New absolute file path after moving.
 * @throws RuntimeException      If validations fail or move cannot be completed.
 */
function moveFile(string $filePath, string $destinationDir): string
{
    $realSrc = realpath($filePath);

    if ($realSrc === false || !is_file($realSrc)) {
        throw new RuntimeException("Specified path is not a valid file: {$filePath}");
    }

    $realDest = realpath($destinationDir);

    if ($realDest === false || !is_dir($realDest) || !is_writable($realDest)) {
        throw new RuntimeException("Destination dir invalid or unwritable: {$destinationDir}");
    }

    $realDest = rtrim($realDest, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    $filename = basename($realSrc);
    validateFileName($filename);
    $target   = constructSequentialFilePath($realDest, $filename);

    if (!@rename($realSrc, $target)) {
        $error = error_get_last()['message'] ?? '';

        if (strpos($error, 'EXDEV') !== false) {
            if (!@copy($realSrc, $target)) {
                throw new RuntimeException("Failed to copy file across devices: {$error}");
            }
            if (!@unlink($realSrc)) {
                @unlink($target);
                throw new RuntimeException("Failed to remove original after copy.");
            }
        } else {
            throw new RuntimeException("Failed to rename file: {$error}");
        }
    }

    return realpath($target) ?: $target;
}
