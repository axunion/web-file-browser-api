<?php

/**
 * Save a sanitized uploaded file to the specified destination.
 *
 * @param array $uploadedFile Validated uploaded file array (from $_FILES).
 * @param string $destinationPath The directory where the file will be saved.
 * @param string $safeFilename A sanitized filename for the uploaded file.
 * @return string The saved filename.
 * @throws RuntimeException If the file cannot be saved.
 */
function save_uploaded_file(array $uploadedFile, string $destinationPath, string $safeFilename): string
{
    $fileExtension = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));

    validate_file_extension($fileExtension);
    validate_destination_directory($destinationPath);

    $finalFilePath = construct_unique_file_path($destinationPath, $safeFilename, $fileExtension);

    if (!move_uploaded_file($uploadedFile['tmp_name'], $finalFilePath)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return basename($finalFilePath);
}

/**
 * Validate the file extension against an allowed list.
 *
 * @param string $extension The file extension to validate.
 * @return void
 * @throws RuntimeException If the extension is not allowed.
 */
function validate_file_extension(string $extension): void
{
    $allowedExtensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'heic',
        'mp4',
        'mov',
        'avi',
        'mkv',
        'mp3',
        'wav',
        'aac',
        'm4a',
        'pdf',
        'txt',
        'doc',
        'docx',
        'xlsx',
        'csv'
    ];

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException("File type '{$extension}' is not allowed.");
    }
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
    $normalizedPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (!is_dir($normalizedPath) || !is_writable($normalizedPath)) {
        throw new RuntimeException("Destination path '{$path}' is not writable or does not exist.");
    }
}

/**
 * Construct a unique file path in the destination directory.
 *
 * @param string $directory The destination directory.
 * @param string $filename The desired filename.
 * @param string $extension The file extension.
 * @return string The unique file path.
 */
function construct_unique_file_path(string $directory, string $filename, string $extension): string
{
    $normalizedDirectory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $filePath = $normalizedDirectory . $filename;

    while (file_exists($filePath)) {
        $filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
        $uniqueSuffix = uniqid();
        $filePath = $normalizedDirectory . "{$filenameWithoutExtension}_{$uniqueSuffix}.{$extension}";
    }

    return $filePath;
}
