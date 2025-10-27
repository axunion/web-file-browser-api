<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/RequestHandler.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/FileOperations.php';

final class RenameHandler extends RequestHandler
{
    protected array $allowedMethods = ['POST'];

    protected function process(): void
    {
        $subPath     = $this->getInput(INPUT_POST, 'path', '');
        $currentName = $this->getInput(INPUT_POST, 'name', '');
        $newName     = $this->getInput(INPUT_POST, 'newName', '');

        if ($currentName === '') {
            throw new RuntimeException('Current file name is required.');
        }

        if ($newName === '') {
            throw new RuntimeException('New file name is required.');
        }

        $targetDir = $this->resolvePath($subPath);

        if (!is_dir($targetDir) || !is_writable($targetDir)) {
            throw new RuntimeException('Specified path is not a writable directory.');
        }

        $renamedPath = FileOperations::rename($targetDir, $currentName, $newName);

        $this->sendSuccess([
            'path'     => $subPath,
            'filename' => basename($renamedPath),
            'fullPath' => $renamedPath,
        ]);
    }
}

(new RenameHandler())->handle();
