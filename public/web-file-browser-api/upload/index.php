<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/RequestHandler.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/UploadValidator.php';

const MAX_FILE_SIZE = 100 * 1024 * 1024;

final class UploadHandler extends RequestHandler
{
    protected array $allowedMethods = ['POST'];

    protected function process(): void
    {
        if (!isset($_FILES['file'])) {
            throw new RuntimeException('No file uploaded.');
        }

        $validator = new UploadValidator(
            allowedMimeTypes: ['image/jpeg', 'image/png', 'application/pdf'],
            maxFileSize: MAX_FILE_SIZE
        );

        $subPath = $this->getInput(INPUT_POST, 'path', '');
        $targetDir = $this->resolvePath($subPath);

        $validator->validate($_FILES['file']);
        $validator->uploadFile($targetDir, $_FILES['file']);

        $this->sendSuccess();
    }
}

(new UploadHandler())->handle();
