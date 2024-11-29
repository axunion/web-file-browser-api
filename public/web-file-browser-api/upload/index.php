<?php

require_once __DIR__ . '/../../src/save_uploaded_file.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed.'
    ]);
    exit;
}

header('Content-Type: application/json');

try {
    if (!isset($_FILES['file'])) {
        throw new RuntimeException('No file uploaded.');
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed with error code: ' . $_FILES['file']['error']);
    }

    $maxFileSize = 5 * 1024 * 1024 * 1024;

    if ($_FILES['file']['size'] > $maxFileSize) {
        throw new RuntimeException('Exceeded file size limit of 5GB.');
    }

    $dataDir = realpath(__DIR__ . '/../../data');

    if ($dataDir === false || !is_dir($dataDir) || !is_writable($dataDir)) {
        throw new RuntimeException('Upload directory is not writable or does not exist.');
    }

    $uploadedFilename = $_FILES['file']['name'];
    $sanitizedFilename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($uploadedFilename));
    $savedFileName = save_uploaded_file($_FILES['file'], $dataDir, $sanitizedFilename);

    echo json_encode([
        'status' => 'success',
        'message' => 'File uploaded successfully.',
        'filename' => $savedFileName,
    ], JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to encode JSON response.'
    ]);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
