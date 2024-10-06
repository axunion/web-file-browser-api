<?php

const MAX_FILESIZE = 16 * 1024 * 1024 * 1024;

function save_uploaded_file(array $uploaded_file, string $destination_path, string $filename): string
{
    if (!isset($uploaded_file['error']) || is_array($uploaded_file['error'])) {
        throw new RuntimeException('Invalid parameters.');
    }

    switch ($uploaded_file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }

    if ($uploaded_file['size'] > MAX_FILESIZE) {
        throw new RuntimeException('Exceeded filesize limit.');
    }

    $filename = preg_replace("/[^a-zA-Z0-9_.-]/", "", $filename);

    if (strlen($filename) === 0) {
        throw new RuntimeException('Invalid filename.');
    }

    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $destination_path = rtrim($destination_path, '/') . '/';
    $full_path = $destination_path . $filename;
    $counter = 1;

    while (file_exists($full_path)) {
        $newFilename = pathinfo($filename, PATHINFO_FILENAME) . "_{$counter}." . $file_extension;
        $full_path = $destination_path . $newFilename;
        $counter++;
    }

    if (!move_uploaded_file($uploaded_file['tmp_name'], $full_path)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return basename($full_path);
}
