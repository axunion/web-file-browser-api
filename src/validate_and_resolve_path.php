<?php

declare(strict_types=1);

/**
 * Validates and resolves a path within a base directory.
 *
 * @param string $base_dir The base directory (must be an absolute path).
 * @param string $path The user-provided relative or absolute path.
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

    return $resolved_path;
}
