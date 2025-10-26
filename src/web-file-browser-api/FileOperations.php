<?php

declare(strict_types=1);

require_once __DIR__ . '/PathSecurity.php';

/**
 * Handles file movement and renaming operations.
 */
final class FileOperations
{
    /**
     * Moves the specified file to the target directory, handling cross-filesystem
     * and naming collisions robustly.
     *
     * @param string $filePath       Absolute or relative path of the file to move.
     * @param string $destinationDir Absolute or relative path of the target directory.
     * @return string                New absolute file path after moving.
     * @throws RuntimeException      If validations fail or move cannot be completed.
     */
    public static function move(string $filePath, string $destinationDir): string
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
        PathSecurity::validateFileName($filename);
        $target   = PathSecurity::constructSequentialFilePath($realDest, $filename);

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

    /**
     * Rename a file within a directory safely.
     *
     * @param string $directory   Path to the target directory.
     * @param string $currentName Current filename.
     * @param string $newName     Desired new filename.
     * @return string             Absolute path of the renamed file.
     * @throws RuntimeException   On validation or rename failure.
     */
    public static function rename(string $directory, string $currentName, string $newName): string
    {
        $realDir = realpath($directory);

        if ($realDir === false || !is_dir($realDir) || !is_writable($realDir)) {
            throw new RuntimeException("Target directory invalid or not writable: {$directory}");
        }

        $realDir = rtrim($realDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        PathSecurity::validateFileName($newName);

        $srcPath = $realDir . $currentName;
        $dstPath = $realDir . $newName;

        if (!is_file($srcPath)) {
            throw new RuntimeException("File not found: {$srcPath}");
        }

        if (file_exists($dstPath)) {
            throw new RuntimeException("Cannot rename: target file already exists: {$newName}");
        }

        if (!@rename($srcPath, $dstPath)) {
            $err = error_get_last()['message'] ?? '';
            throw new RuntimeException("Failed to rename '{$currentName}' → '{$newName}': {$err}");
        }

        return realpath($dstPath) ?: $dstPath;
    }
}
