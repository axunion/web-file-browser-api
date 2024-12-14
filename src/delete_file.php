<?php

declare(strict_types=1);

/**
 * Delete a file at the specified path.
 *
 * @param string $filePath The full path to the file to delete.
 * @return void
 * @throws RuntimeException If the file cannot be deleted.
 */
function deleteFile(string $filePath): void
{
    validateFilePath($filePath);

    if (!unlink($filePath)) {
        $error = error_get_last();
        $message = $error['message'] ?? 'An unknown error occurred.';
        throw new RuntimeException("Failed to delete file '{$filePath}'. Error: {$message}");
    }
}

/**
 * Validate that the file exists and is deletable.
 *
 * @param string $filePath The file path to validate.
 * @return void
 * @throws RuntimeException If the file does not exist or is not deletable.
 */
function validateFilePath(string $filePath): void
{
    if (!file_exists($filePath)) {
        throw new RuntimeException("File '{$filePath}' does not exist.");
    }

    if (!is_file($filePath)) {
        throw new RuntimeException("The path '{$filePath}' is not a file.");
    }

    if (!is_writable($filePath)) {
        throw new RuntimeException("File '{$filePath}' is not writable.");
    }
}
