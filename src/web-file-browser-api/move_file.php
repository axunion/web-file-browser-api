<?php

declare(strict_types=1);

require_once __DIR__ . '/filepath_utils.php';

/**
 * Moves the specified file to the target directory.
 *
 * @param string $file_path The path of the file to move.
 * @param string $destination_dir The target directory.
 * @return string The new file path after moving.
 * @throws RuntimeException If the file does not exist, is not a regular file, the destination directory is invalid, or the file cannot be moved.
 */
function move_file(string $file_path, string $destination_dir): string
{
    if (!is_file($file_path)) {
        throw new RuntimeException("Specified path is not a file: {$file_path}");
    }

    if (!is_dir($destination_dir) || !is_writable($destination_dir)) {
        throw new RuntimeException("Destination directory does not exist or is not writable: {$destination_dir}");
    }

    $filename = basename($file_path);
    validate_file_name($filename);
    $destination_path = construct_sequential_file_path($destination_dir, $filename);

    if (!rename($file_path, $destination_path)) {
        if (!file_exists($file_path)) {
            throw new RuntimeException("File was removed before moving: {$file_path}");
        }
        throw new RuntimeException("Failed to move file to destination: {$file_path}");
    }

    error_log("Moved file {$file_path} to {$destination_path}");

    return $destination_path;
}
