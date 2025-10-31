<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/bootstrap.php';

validateMethod(['POST']);

try {
    if (!isset($_FILES['file'])) {
        throw new RuntimeException('No file uploaded.');
    }

    $validator = new UploadValidator(
        allowedMimeTypes: Config::SINGLE_UPLOAD_ALLOWED_TYPES,
        maxFileSize: Config::SINGLE_FILE_MAX_SIZE
    );

    $subPath = getInput(INPUT_POST, 'path', '');
    $targetDir = resolvePath($subPath);

    $validator->validate($_FILES['file']);
    $validator->uploadFile($targetDir, $_FILES['file']);

    sendSuccess();
} catch (Throwable $e) {
    handleError($e);
}
