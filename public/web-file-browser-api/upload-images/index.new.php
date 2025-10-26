<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/RequestHandler.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/UploadValidator.php';

const MAX_FILES       = 10;
const MAX_FILE_SIZE   = 10 * 1024 * 1024;
const MAX_TOTAL_SIZE  = 30 * 1024 * 1024;

final class UploadImagesHandler extends RequestHandler
{
    protected array $allowedMethods = ['POST'];

    protected function process(): void
    {
        if (!isset($_FILES['images'])) {
            throw new RuntimeException('No files uploaded under "images" key.');
        }

        $files = $_FILES['images'];
        $validator = new UploadValidator(
            allowedMimeTypes: ['image/jpeg', 'image/png'],
            maxFileSize: MAX_FILE_SIZE
        );

        $validator->validateBatch($files, MAX_FILES, MAX_TOTAL_SIZE);

        $subPath   = $this->getInput(INPUT_POST, 'path', '');
        $targetDir = $this->resolvePath($subPath);

        if (!file_exists($targetDir)) {
            if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new RuntimeException("Failed to create directory: {$targetDir}");
            }
        }

        if (!is_dir($targetDir) || !is_writable($targetDir)) {
            throw new RuntimeException('Target path is not a writable directory.');
        }

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

        $this->sendSuccess(['files' => $saved]);
    }
}

(new UploadImagesHandler())->handle();
