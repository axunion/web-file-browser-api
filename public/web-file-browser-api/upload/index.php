<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/RequestHandler.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/UploadValidator.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/Config.php';

final class UploadHandler extends RequestHandler
{
    protected array $allowedMethods = ['POST'];

    protected function process(): void
    {
        if (!isset($_FILES['file'])) {
            throw new RuntimeException('No file uploaded.');
        }

        $validator = new UploadValidator(
            allowedMimeTypes: Config::SINGLE_UPLOAD_ALLOWED_TYPES,
            maxFileSize: Config::SINGLE_FILE_MAX_SIZE
        );

        $subPath = $this->getInput(INPUT_POST, 'path', '');
        $targetDir = $this->resolvePath($subPath);

        $validator->validate($_FILES['file']);
        $validator->uploadFile($targetDir, $_FILES['file']);

        $this->sendSuccess();
    }
}

(new UploadHandler())->handle();
