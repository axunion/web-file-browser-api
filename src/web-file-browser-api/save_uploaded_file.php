<?php

declare(strict_types=1);

/**
 * Save a sanitized uploaded file to the specified destination.
 *
 * @param array $uploaded_file Validated uploaded file array (from $_FILES).
 * @param string $destination_path The directory where the file will be saved.
 * @param string $filename A suggested filename for the uploaded file.
 * @return string The saved filename.
 * @throws RuntimeException If the file cannot be saved.
 */
function save_uploaded_file(array $uploaded_file, string $destination_path, string $filename): string
{
    $file_basename = pathinfo($filename, PATHINFO_FILENAME);
    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $final_file_path = construct_sequential_file_path($destination_path, $file_basename, $file_extension);

    if (!move_uploaded_file($uploaded_file['tmp_name'], $final_file_path)) {
        $error = error_get_last();
        throw new RuntimeException(
            "Failed to move uploaded file. Destination: '{$final_file_path}', Error: " . ($error['message'] ?? 'Unknown error.')
        );
    }

    return basename($final_file_path);
}

/**
 * Construct a unique file path in the destination directory using sequential numbering.
 *
 * @param string $directory The destination directory.
 * @param string $filename The desired filename without extension.
 * @param string $extension The file extension.
 * @return string The unique file path.
 */
function construct_sequential_file_path(string $directory, string $filename, string $extension): string
{
    $normalized_directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $base_path = "{$normalized_directory}{$filename}.{$extension}";

    if (!file_exists($base_path)) {
        return $base_path;
    }

    $counter = 1;

    while (file_exists("{$normalized_directory}{$filename}_{$counter}.{$extension}")) {
        $counter++;
    }

    return "{$normalized_directory}{$filename}_{$counter}.{$extension}";
}
