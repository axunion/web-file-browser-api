<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

validateMethod(['POST']);

try {
    $subPath     = getInput(INPUT_POST, 'path', '');
    $currentName = getInput(INPUT_POST, 'name', '');

    if ($currentName === '') {
        throw new ValidationException('Current file name is required.');
    }

    PathSecurity::validateFileName($currentName);

    $targetDir = resolvePath($subPath);

    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        throw new RuntimeException('Specified path is not a writable directory.');
    }

    $realDir = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $newPath = FileOperations::move($realDir . $currentName, API_TRASH_DIR);

    sendSuccess([
        'path'     => $subPath,
        'filename' => basename($newPath),
    ]);
} catch (Throwable $e) {
    handleError($e);
}
