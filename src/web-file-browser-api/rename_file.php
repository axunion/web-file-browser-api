<?php

declare(strict_types=1);

require_once __DIR__ . '/filepath_utils.php';

/**
 * Rename a file in the specified directory.
 *
 * @param string $directory The directory containing the file.
 * @param string $current_name The current name of the file.
 * @param string $new_name The desired new name for the file.
 * @return string The new name of the file.
 * @throws RuntimeException If the file cannot be renamed or validation fails.
 */
function rename_file(string $directory, string $current_name, string $new_name): string
{
    validate_file_name($new_name);

    $normalized_directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $current_file_path = $normalized_directory . $current_name;
    $new_file_path = $normalized_directory . $new_name;

    if (!file_exists($current_file_path)) {
        throw new RuntimeException("The file '{$current_name}' does not exist in the directory.");
    }

    if (!rename($current_file_path, $new_file_path)) {
        throw new RuntimeException("Failed to rename file '{$current_name}' to '{$new_name}'.");
    }

    return $new_name;
}
