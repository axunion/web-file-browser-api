<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/bootstrap.php';

validateMethod(['POST']);

try {
    if (!isset($_FILES['images'])) {
        throw new RuntimeException('No files uploaded under "images" key.');
    }

    $files = $_FILES['images'];
    $validator = new UploadValidator(
        allowedMimeTypes: Config::BATCH_UPLOAD_ALLOWED_TYPES,
        maxFileSize: Config::BATCH_FILE_MAX_SIZE
    );

    $validator->validateBatch($files, Config::BATCH_MAX_FILES, Config::BATCH_MAX_TOTAL_SIZE);

    $subPath   = getInput(INPUT_POST, 'path', '');
    $targetDir = resolvePath($subPath);

    $saved = [];
    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {
        $file = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];

        $validator->validate($file);
        $destPath = $validator->uploadFile($targetDir, $file);
        $saved[] = basename($destPath);
    }

    sendSuccess(['files' => $saved]);
} catch (Throwable $e) {
    handleError($e);
}
