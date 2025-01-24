<?php

declare(strict_types=1);

/**
 * Save a sanitized uploaded file to the specified destination.
 *
 * @param array $uploaded_file Validated uploaded file array (from $_FILES).
 * @param string $destination_path The directory where the file will be saved.
 * @param string $desired_filename A suggested filename for the uploaded file.
 * @return string The saved filename.
 * @throws RuntimeException If the file cannot be saved.
 */
function save_uploaded_file(array $uploaded_file, string $destination_path, string $desired_filename): string
{
    if (!isset($uploaded_file['tmp_name']) || !is_uploaded_file($uploaded_file['tmp_name'])) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    $file_extension = strtolower(pathinfo($desired_filename, PATHINFO_EXTENSION));
    validate_destination_directory($destination_path);

    $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($desired_filename, PATHINFO_FILENAME));
    $final_file_path = construct_unique_file_path($destination_path, $safe_filename, $file_extension);

    if (!move_uploaded_file($uploaded_file['tmp_name'], $final_file_path)) {
        $error = error_get_last();
        throw new RuntimeException(
            "Failed to move uploaded file to '{$final_file_path}'. Error: " . ($error['message'] ?? 'Unknown error.')
        );
    }

    return basename($final_file_path);
}

/**
 * Validate the destination directory.
 *
 * @param string $path The directory path.
 * @return void
 * @throws RuntimeException If the directory is not valid or writable.
 */
function validate_destination_directory(string $path): void
{
    $normalized_path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (!is_dir($normalized_path) || !is_writable($normalized_path)) {
        throw new RuntimeException("Destination path '{$path}' is not writable or does not exist.");
    }
}

/**
 * Construct a unique file path in the destination directory.
 *
 * @param string $directory The destination directory.
 * @param string $filename The desired filename without extension.
 * @param string $extension The file extension.
 * @return string The unique file path.
 */
function construct_unique_file_path(string $directory, string $filename, string $extension): string
{
    $normalized_directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $unique_name = hash('sha256', $filename . microtime(true));

    return "{$normalized_directory}{$filename}_{$unique_name}.{$extension}";
}
