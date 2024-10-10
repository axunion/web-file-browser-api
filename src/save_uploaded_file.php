<?php

function save_uploaded_file(array $uploaded_file, string $destination_path, string $filename): string
{
    $filename = basename(preg_replace("/[^a-zA-Z0-9_.-]/", "", $filename));

    if (strlen($filename) === 0) {
        throw new RuntimeException('Invalid filename.');
    }

    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $destination_path = rtrim($destination_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (!is_dir($destination_path) || !is_writable($destination_path)) {
        throw new RuntimeException('Destination path is not writable.');
    }

    $full_path = $destination_path . $filename;
    $counter = 1;

    while (file_exists($full_path)) {
        $new_filename = pathinfo($filename, PATHINFO_FILENAME) . "_{$counter}." . $file_extension;
        $full_path = $destination_path . $new_filename;
        $counter++;
    }

    if (!move_uploaded_file($uploaded_file['tmp_name'], $full_path)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return basename($full_path);
}
