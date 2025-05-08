<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/filepath_utils.php';

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
    if (!isset($_FILES['file'])) {
        throw new RuntimeException('No file uploaded.');
    }

    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];

        $msg = $messages[$file['error']] ?? 'Unknown upload error.';
        throw new RuntimeException($msg);
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    validateFileName($file['name']);

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Uploaded file exceeds 5MB limit.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'application/pdf'];

    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('File type not allowed.');
    }

    $dataDir = realpath(__DIR__ . '/../../data');

    if ($dataDir === false) {
        throw new RuntimeException('Server misconfiguration: data directory not found.');
    }

    $subPath = filter_input(INPUT_POST, 'path', FILTER_UNSAFE_RAW) ?? '';
    $target  = resolveSafePath($dataDir, $subPath);

    if (!is_dir($target) || !is_writable($target)) {
        throw new RuntimeException('Target path is not a writable directory.');
    }

    $destPath = constructSequentialFilePath($target, $file['name']);

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        $err = error_get_last()['message'] ?? 'Unknown error';
        throw new RuntimeException("Failed to move uploaded file: {$err}");
    }

    sendJson(['status' => 'success'], 200);
} catch (RuntimeException $e) {
    error_log('Upload error: ' . $e->getMessage());
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('Unexpected upload error: ' . $e->getMessage());
    sendJson(['status' => 'error', 'message' => 'An unexpected error occurred.'], 500);
}
