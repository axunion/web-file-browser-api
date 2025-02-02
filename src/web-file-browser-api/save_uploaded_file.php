<?php

declare(strict_types=1);

require_once __DIR__ . '/filepath_utils.php';

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
    validate_file_name($filename);
    $final_file_path = construct_sequential_file_path($destination_path, $filename);

    if (!move_uploaded_file($uploaded_file['tmp_name'], $final_file_path)) {
        $error = error_get_last();
        throw new RuntimeException(
            "Failed to move uploaded file. Destination: '{$final_file_path}', Error: " . ($error['message'] ?? 'Unknown error.')
        );
    }

    return basename($final_file_path);
}
