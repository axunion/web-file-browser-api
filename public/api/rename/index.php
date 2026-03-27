<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

validateMethod(['POST']);

try {
    $subPath     = getInput(INPUT_POST, 'path', '');
    $currentName = getInput(INPUT_POST, 'name', '');
    $newName     = getInput(INPUT_POST, 'newName', '');

    if ($currentName === '') {
        throw new ValidationException('Current file name is required.');
    }

    if ($newName === '') {
        throw new ValidationException('New file name is required.');
    }

    PathSecurity::validateFileName($currentName);

    $targetDir = resolvePath($subPath);

    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        throw new RuntimeException('Specified path is not a writable directory.');
    }

    $renamedPath = FileOperations::rename($targetDir, $currentName, $newName);

    sendSuccess([
        'path'     => $subPath,
        'filename' => basename($renamedPath),
    ]);
} catch (Throwable $e) {
    handleError($e);
}
