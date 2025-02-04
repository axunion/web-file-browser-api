<?php

declare(strict_types=1);

/**
 * Validates and resolves a path within a base directory.
 *
 * @param string $base_dir The base directory (must be an absolute path).
 * @param string $path The user-provided relative path.
 * @return string The resolved and validated absolute path.
 * @throws RuntimeException If the path is invalid or outside the base directory.
 */
function validate_and_resolve_path(string $base_dir, string $path): string
{
    $real_base_dir = realpath($base_dir);

    if ($real_base_dir === false || !is_dir($real_base_dir)) {
        throw new RuntimeException('The base directory does not exist or is not a valid directory.');
    }

    $combined_path = $real_base_dir . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    $resolved_path = realpath($combined_path);

    if ($resolved_path === false) {
        throw new RuntimeException('The specified path does not exist.');
    }

    if (
        $resolved_path !== $real_base_dir &&
        strpos($resolved_path, $real_base_dir . DIRECTORY_SEPARATOR) !== 0
    ) {
        throw new RuntimeException('The specified path is outside the base directory.');
    }

    return $resolved_path;
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
    if (empty($file_name)) {
        throw new RuntimeException("The file name '{$file_name}' cannot be empty.");
    }

    if (strlen($file_name) > 255) {
        throw new RuntimeException("The file name '{$file_name}' exceeds the maximum allowed length of 255 characters.");
    }

    if (preg_match('/[<>:"\/\\|?*]/', $file_name)) {
        throw new RuntimeException("The file name '{$file_name}' contains invalid characters.");
    }
}

/**
 * Construct a unique file path in the destination directory using sequential numbering.
 *
 * @param string $directory_path The destination directory.
 * @param string $filename The desired filename.
 * @return string The unique file path.
 */
function construct_sequential_file_path(string $directory_path, string $filename): string
{
    $normalized_directory = rtrim($directory_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $basename = pathinfo($filename, PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $extension = empty($extension) ? '' : ".{$extension}";
    $base_path = "{$normalized_directory}{$basename}{$extension}";

    if (!file_exists($base_path)) {
        return $base_path;
    }

    $counter = 1;

    while (file_exists("{$normalized_directory}{$basename}_{$counter}{$extension}")) {
        $counter++;
    }

    return "{$normalized_directory}{$basename}_{$counter}{$extension}";
}
