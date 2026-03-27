<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

validateMethod(['POST']);

try {
    $subPath         = getInput(INPUT_POST, 'path', '');
    $fileName        = getInput(INPUT_POST, 'name', '');
    $destinationPath = getInput(INPUT_POST, 'destinationPath', '');

    if ($fileName === '') {
        throw new ValidationException('File name is required.');
    }

    if ($destinationPath === '') {
        throw new ValidationException('Destination path is required.');
    }

    PathSecurity::validateFileName($fileName);

    $sourceDir = resolvePath($subPath);

    if (!is_dir($sourceDir) || !is_readable($sourceDir)) {
        throw new RuntimeException('Source path is not a readable directory.');
    }

    $destDir = resolvePath($destinationPath);

    if (!is_dir($destDir) || !is_writable($destDir)) {
        throw new RuntimeException('Destination path is not a writable directory.');
    }

    $realSourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $newPath = FileOperations::move($realSourceDir . $fileName, $destDir);

    sendSuccess([
        'path'     => $destinationPath,
        'filename' => basename($newPath),
    ]);
} catch (Throwable $e) {
    handleError($e);
}
