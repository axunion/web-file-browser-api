<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/filepath_utils.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/image_utils.php';

const MAX_FILES          = 10;
const MAX_TOTAL_SIZE     = 30 * 1024 * 1024;
const COMPRESS_THRESHOLD = 5  * 1024 * 1024;
const MAX_WIDTH          = 1280;
const MAX_HEIGHT         = 720;
const JPEG_QUALITY       = 85;
const PNG_COMPRESSION    = 6;

function sendJson(array $payload, int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    sendJson(['status' => 'error', 'message' => 'Method not allowed.'], 405);
}

try {
    if (!isset($_FILES['images'])) {
        throw new RuntimeException('No files uploaded under "images" key.');
    }

    $files = $_FILES['images'];
    $count = count($files['name']);

    if ($count === 0) {
        throw new RuntimeException('No files uploaded.');
    }
    if ($count > MAX_FILES) {
        throw new RuntimeException(sprintf('Too many files. Maximum is %d.', MAX_FILES));
    }

    $totalSize = array_sum($files['size']);

    if ($totalSize > MAX_TOTAL_SIZE) {
        throw new RuntimeException(sprintf('Total upload size exceeds %d MB.', MAX_TOTAL_SIZE / (1024 * 1024)));
    }

    $dataDir = realpath(__DIR__ . '/../../data');

    if ($dataDir === false || !is_dir($dataDir) || !is_writable($dataDir)) {
        throw new RuntimeException('Server misconfiguration: data directory unavailable.');
    }

    $subPath   = filter_input(INPUT_POST, 'path', FILTER_UNSAFE_RAW) ?? '';
    $targetDir = resolveSafePath($dataDir, $subPath);

    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        throw new RuntimeException('Target path is not a writable directory.');
    }

    $finfo       = new finfo(FILEINFO_MIME_TYPE);
    $allowedMimes = ['image/jpeg', 'image/png'];
    $saved = [];

    for ($i = 0; $i < $count; $i++) {
        $error = $files['error'][$i];

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Upload error on file #" . ($i + 1));
        }

        $tmpName = $files['tmp_name'][$i];

        if (!is_uploaded_file($tmpName)) {
            throw new RuntimeException("Invalid upload for file #" . ($i + 1));
        }

        $origName = basename($files['name'][$i]);
        validateFileName($origName);
        $mime = $finfo->file($tmpName);

        if (!in_array($mime, $allowedMimes, true)) {
            throw new RuntimeException("File type not allowed for {$origName}.");
        }

        $destPath = constructSequentialFilePath($targetDir, $origName);

        if (filesize($tmpName) > COMPRESS_THRESHOLD) {
            $tmpDst = sys_get_temp_dir() . '/img_' . uniqid() . '.' . pathinfo($origName, PATHINFO_EXTENSION);
            compressResizeImage(
                $tmpName,
                $tmpDst,
                MAX_WIDTH,
                MAX_HEIGHT,
                JPEG_QUALITY,
                PNG_COMPRESSION
            );

            if (!rename($tmpDst, $destPath)) {
                throw new RuntimeException("Failed to save compressed image for {$origName}.");
            }
        } else {
            if (!move_uploaded_file($tmpName, $destPath)) {
                throw new RuntimeException("Failed to move uploaded file {$origName}.");
            }
        }

        $saved[] = basename($destPath);
    }

    sendJson(['status' => 'success', 'files' => $saved], 200);
} catch (RuntimeException $e) {
    error_log('Image upload error: ' . $e->getMessage());
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('Unexpected image upload error: ' . $e->getMessage());
    sendJson(['status' => 'error', 'message' => 'An unexpected error occurred.'], 500);
}
