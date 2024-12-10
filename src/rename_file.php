<?php

/**
 * Rename a file in the specified directory.
 *
 * @param string $directory The directory containing the file.
 * @param string $currentName The current name of the file.
 * @param string $newName The desired new name for the file.
 * @return string The new name of the file.
 * @throws RuntimeException If the file cannot be renamed or validation fails.
 */
function rename_file(string $directory, string $currentName, string $newName): string
{
    $normalizedDirectory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $currentFilePath = $normalizedDirectory . $currentName;
    $newFilePath = $normalizedDirectory . $newName;

    if (preg_match('/[<>:"\/\\|?*]/', $newName)) {
        throw new RuntimeException("The new file name '{$newName}' contains invalid characters.");
    }

    if (!file_exists($currentFilePath)) {
        throw new RuntimeException("File '{$currentFilePath}' does not exist.");
    }

    if (!rename($currentFilePath, $newFilePath)) {
        throw new RuntimeException("Failed to rename file '{$currentFilePath}' to '{$newFilePath}'.");
    }

    return basename($newFilePath);
}
