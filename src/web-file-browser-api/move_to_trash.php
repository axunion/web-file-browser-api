<?php

declare(strict_types=1);

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

    $file_name = basename($file_path);
    $trash_path = rtrim($trash_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file_name;

    $counter = 1;

    while (file_exists($trash_path)) {
        $trash_path = rtrim($trash_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
            pathinfo($file_name, PATHINFO_FILENAME) . "_{$counter}." . pathinfo($file_name, PATHINFO_EXTENSION);
        $counter++;
    }

    if (!rename($file_path, $trash_path)) {
        throw new RuntimeException("Failed to move file to trash: {$file_path}");
    }
}
