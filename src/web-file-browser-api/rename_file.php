<?php

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
    $normalized_directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $current_file_path = $normalized_directory . $current_name;
    $new_file_path = $normalized_directory . $new_name;

    validate_file_name($new_name);

    if (!file_exists($current_file_path)) {
        throw new RuntimeException("The file '{$current_name}' does not exist in the directory.");
    }

    if (!rename($current_file_path, $new_file_path)) {
        throw new RuntimeException("Failed to rename file '{$current_name}' to '{$new_name}'.");
    }

    return $new_name;
}

/**
 * Validate a file name to ensure it does not contain invalid characters.
 *
 * @param string $file_name The file name to validate.
 * @return void
 * @throws RuntimeException If the file name contains invalid characters.
 */
function validate_file_name(string $file_name): void
{
    if (preg_match('/[<>:"\/\\|?*]/', $file_name)) {
        throw new RuntimeException("The file name '{$file_name}' contains invalid characters.");
    }

    if (strlen($file_name) === 0) {
        throw new RuntimeException("The file name cannot be empty.");
    }

    if (strlen($file_name) > 255) {
        throw new RuntimeException("The file name '{$file_name}' exceeds the maximum allowed length of 255 characters.");
    }
}
