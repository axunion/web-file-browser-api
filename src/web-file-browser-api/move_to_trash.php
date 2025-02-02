<?php

declare(strict_types=1);

require_once __DIR__ . '/filepath_utils.php';

/**
 * Moves the specified file to the trash directory.
 *
 * @param string $file_path The path of the file to move.
 * @param string $trash_dir The path of the trash directory.
 * @return void
 * @throws RuntimeException If the file does not exist, the trash directory is not writable, or the file cannot be moved.
 */
function move_to_trash(string $file_path, string $trash_dir): void
{
    if (!file_exists($file_path)) {
        throw new RuntimeException("File does not exist: {$file_path}");
    }

    if (!is_writable($trash_dir)) {
        throw new RuntimeException("Trash directory is not writable: {$trash_dir}");
    }

    $filename = basename($file_path);
    validate_file_name($filename);
    $trash_path = construct_sequential_file_path($trash_dir, $filename);

    if (!rename($file_path, $trash_path)) {
        throw new RuntimeException("Failed to move file to trash: {$file_path}");
    }
}
