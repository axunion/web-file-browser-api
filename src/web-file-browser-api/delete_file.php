<?php

declare(strict_types=1);

/**
 * Delete a file in the specified directory.
 *
 * @param string $directory The directory where the file is located.
 * @param string $file_name The name of the file to delete.
 * @return void
 * @throws RuntimeException If the file cannot be deleted.
 */
function delete_file(string $directory, string $file_name): void
{
    $normalized_directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $file_path = $normalized_directory . $file_name;
    validate_file_path($file_path);

    if (!unlink($file_path)) {
        $error = error_get_last();
        $message = $error['message'] ?? 'An unknown error occurred.';
        throw new RuntimeException("Failed to delete file '{$file_path}'. Error: {$message}");
    }
}

/**
 * Validate that the file exists and is deletable.
 *
 * @param string $file_path The full file path to validate.
 * @return void
 * @throws RuntimeException If the file does not exist or is not deletable.
 */
function validate_file_path(string $file_path): void
{
    if (!file_exists($file_path)) {
        throw new RuntimeException("File '{$file_path}' does not exist.");
    }

    if (!is_file($file_path)) {
        throw new RuntimeException("The path '{$file_path}' is not a file.");
    }

    if (!is_writable($file_path)) {
        throw new RuntimeException("File '{$file_path}' is not writable.");
    }
}
