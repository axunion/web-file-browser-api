<?php

declare(strict_types=1);

require_once __DIR__ . '/filepath_utils.php';

/**
 * Rename a file within a directory safely.
 *
 * @param string $directory   Path to the target directory.
 * @param string $currentName Current filename.
 * @param string $newName     Desired new filename.
 * @return string             Absolute path of the renamed file.
 * @throws RuntimeException   On validation or rename failure.
 */
function renameFile(string $directory, string $currentName, string $newName): string
{
    $realDir = realpath($directory);

    if ($realDir === false || !is_dir($realDir) || !is_writable($realDir)) {
        throw new RuntimeException("Target directory invalid or not writable: {$directory}");
    }

    $realDir = rtrim($realDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    validateFileName($newName);

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
