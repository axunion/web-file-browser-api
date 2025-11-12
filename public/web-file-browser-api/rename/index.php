<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/bootstrap.php';

validateMethod(['POST']);

try {
    $subPath     = getInput(INPUT_POST, 'path', '');
    $currentName = getInput(INPUT_POST, 'name', '');
    $newName     = getInput(INPUT_POST, 'newName', '');

    if ($currentName === '') {
        throw new RuntimeException('Current file name is required.');
    }

    if ($newName === '') {
        throw new RuntimeException('New file name is required.');
    }

    $targetDir = resolvePathWithTrash($subPath);

    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        throw new RuntimeException('Specified path is not a writable directory.');
    }

    $renamedPath = FileOperations::rename($targetDir, $currentName, $newName);

    sendSuccess([
        'path'     => $subPath,
        'filename' => basename($renamedPath),
        'fullPath' => $renamedPath,
    ]);
} catch (Throwable $e) {
    handleError($e);
}
