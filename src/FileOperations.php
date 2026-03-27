<?php

declare(strict_types=1);

/**
 * Handles file and directory movement and renaming operations.
 */
final class FileOperations
{
    /**
     * Moves the specified file or directory to the target directory, handling
     * naming collisions robustly.
     *
     * @param string $filePath       Absolute or relative path of the file or directory to move.
     * @param string $destinationDir Absolute or relative path of the target directory.
     * @return string                New absolute path after moving.
     * @throws RuntimeException      If validations fail or move cannot be completed.
     */
    public static function move(string $filePath, string $destinationDir): string
    {
        $realSrc = realpath($filePath);

        if ($realSrc === false || (!is_file($realSrc) && !is_dir($realSrc))) {
            throw new RuntimeException('Specified path is not a valid file or directory.');
        }

        $realDest = realpath($destinationDir);

        if ($realDest === false || !is_dir($realDest) || !is_writable($realDest)) {
            throw new RuntimeException('Destination path is not a writable directory.');
        }

        $realDest = rtrim($realDest, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $filename = basename($realSrc);
        PathSecurity::validateFileName($filename);

        if (is_dir($realSrc)) {
            $sourcePrefix = rtrim($realSrc, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (strncmp($realDest, $sourcePrefix, strlen($sourcePrefix)) === 0) {
                throw new RuntimeException('Cannot move a directory into itself.');
            }
        }

        return PathSecurity::constructSequentialFilePath(
            $realDest,
            $filename,
            function (string $target) use ($realSrc): void {
                if (@rename($realSrc, $target)) {
                    return;
                }

                $error = error_get_last()['message'] ?? '';
                if (!self::isCrossDeviceError($error)) {
                    throw new RuntimeException('Failed to move the requested item.');
                }

                if (is_dir($realSrc)) {
                    self::copyDirectory($realSrc, $target);
                    self::removeDirectory($realSrc);
                    return;
                }

                if (!@copy($realSrc, $target)) {
                    throw new RuntimeException('Failed to move the requested item.');
                }

                if (!@unlink($realSrc)) {
                    @unlink($target);
                    throw new RuntimeException('Failed to move the requested item.');
                }
            }
        );
    }

    /**
     * Rename a file or directory within a parent directory safely.
     *
     * @param string $directory   Path to the parent directory.
     * @param string $currentName Current name of the file or directory.
     * @param string $newName     Desired new name for the file or directory.
     * @return string             Absolute path of the renamed file or directory.
     * @throws RuntimeException   On validation or rename failure.
     */
    public static function rename(string $directory, string $currentName, string $newName): string
    {
        $realDir = realpath($directory);

        if ($realDir === false || !is_dir($realDir) || !is_writable($realDir)) {
            throw new RuntimeException("Parent directory invalid or not writable: {$directory}");
        }

        $realDir = rtrim($realDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        PathSecurity::validateFileName($currentName);
        PathSecurity::validateFileName($newName);

        $srcPath = $realDir . $currentName;
        $dstPath = $realDir . $newName;

        if (!file_exists($srcPath)) {
            throw new RuntimeException('The requested item was not found.');
        }

        if (file_exists($dstPath)) {
            throw new RuntimeException('Target name already exists.');
        }

        if (!@rename($srcPath, $dstPath)) {
            throw new RuntimeException('Failed to rename the requested item.');
        }

        return realpath($dstPath) ?: $dstPath;
    }

    private static function isCrossDeviceError(string $error): bool
    {
        return stripos($error, 'EXDEV') !== false || stripos($error, 'cross-device') !== false;
    }

    private static function copyDirectory(string $source, string $target): void
    {
        if (!@mkdir($target, 0755)) {
            throw new RuntimeException('Failed to move the requested item.');
        }

        $items = scandir($source);
        if ($items === false) {
            @rmdir($target);
            throw new RuntimeException('Failed to move the requested item.');
        }

        try {
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
                $targetPath = $target . DIRECTORY_SEPARATOR . $item;

                if (is_dir($sourcePath)) {
                    self::copyDirectory($sourcePath, $targetPath);
                    continue;
                }

                if (!@copy($sourcePath, $targetPath)) {
                    throw new RuntimeException('Failed to move the requested item.');
                }
            }
        } catch (Throwable $e) {
            if (is_dir($target)) {
                self::removeDirectory($target);
            }

            throw $e;
        }
    }

    private static function removeDirectory(string $directory): void
    {
        $items = scandir($directory);
        if ($items === false) {
            throw new RuntimeException('Failed to move the requested item.');
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::removeDirectory($path);
            } else {
                if (!@unlink($path)) {
                    throw new RuntimeException('Failed to move the requested item.');
                }
            }
        }

        if (!@rmdir($directory)) {
            throw new RuntimeException('Failed to move the requested item.');
        }
    }
}
