<?php

declare(strict_types=1);

/**
 * Handles all path resolution, validation, and sequential naming.
 */
final class PathSecurity
{
    /**
     * Resolve a user-supplied path within a given base directory safely.
     *
     * @param string $baseDir  Absolute path to the base directory.
     * @param string $userPath Relative path provided by the user.
     * @return string          The safely resolved path (may not exist yet).
     * @throws PathException If the base is invalid or the resolved parent escapes the base.
     */
    public static function resolveSafePath(string $baseDir, string $userPath): string
    {
        $realBase = realpath($baseDir);

        if ($realBase === false || !is_dir($realBase)) {
            throw new PathException('Invalid base directory.');
        }

        if ($userPath === '' || $userPath === '.' || $userPath === './') {
            return $realBase;
        }

        $combined   = $realBase . DIRECTORY_SEPARATOR . ltrim($userPath, '/\\');
        $parentDir  = dirname($combined);
        $realParent = realpath($parentDir);

        if ($realParent === false) {
            throw new PathException('Parent directory does not exist.');
        }

        if (strncmp($realParent . DIRECTORY_SEPARATOR, $realBase . DIRECTORY_SEPARATOR, strlen($realBase) + 1) !== 0) {
            throw new PathException('Attempt to escape base directory.');
        }

        return $combined;
    }

    /**
     * Validate a file name to ensure it meets platform-specific rules.
     *
     * @param string $fileName The file name to validate.
     * @return void
     * @throws ValidationException If the file name is invalid.
     */
    public static function validateFileName(string $fileName): void
    {
        if (class_exists('Normalizer')) {
            $fileName = \Normalizer::normalize($fileName, \Normalizer::FORM_C);
        }

        if ($fileName === '') {
            throw new ValidationException("The file name cannot be empty.");
        }

        if (mb_strlen($fileName, 'UTF-8') > 255) {
            throw new ValidationException("The file name exceeds the maximum length of 255 characters.");
        }

        if (preg_match('/[<>:"\/\\\\|\?\*\x00-\x1F]/u', $fileName)) {
            throw new ValidationException("The file name contains invalid characters.");
        }

        $upper = strtoupper(pathinfo($fileName, PATHINFO_FILENAME));

        if (preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/', $upper)) {
            throw new ValidationException("The file name '{$fileName}' is a reserved name on Windows.");
        }

        if (preg_match('/[\. ]$/', $fileName)) {
            throw new ValidationException("The file name must not end with a space or dot.");
        }
    }

    /**
     * Construct a unique file path in the destination directory by appending a counter if needed.
     *
     * @param string $directoryPath Absolute path to the target directory.
     * @param string $filename      Desired filename (with or without extension).
     * @return string               Unique file path within the directory.
     * @throws PathException        If the directory is invalid or not writable.
     */
    public static function constructSequentialFilePath(string $directoryPath, string $filename): string
    {
        $realDir = realpath($directoryPath);

        if ($realDir === false || !is_dir($realDir) || !is_writable($realDir)) {
            throw new PathException("Invalid or unwritable directory: {$directoryPath}");
        }

        $realDir = rtrim($realDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $baseName  = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $extPart   = $extension === '' ? '' : '.' . strtolower($extension);
        $candidate = $realDir . $baseName . $extPart;

        if (!file_exists($candidate)) {
            return $candidate;
        }

        $lockFile = $realDir . '.construct_lock';
        $fp = fopen($lockFile, 'c');

        if ($fp) {
            flock($fp, LOCK_EX);
        }

        $counter = 1;

        do {
            $candidate = $realDir . $baseName . '_' . $counter . $extPart;
            $counter++;
        } while (file_exists($candidate));

        if ($fp) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return $candidate;
    }
}
