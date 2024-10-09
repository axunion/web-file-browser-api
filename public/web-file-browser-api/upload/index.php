<?php

require_once __DIR__ . '/../../src/save_uploaded_file.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        if (!isset($_FILES['file'])) {
            throw new RuntimeException('No file uploaded.');
        }

        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed with error code ' . $_FILES['file']['error']);
        }

        if ($_FILES['file']['size'] > 5000000000) {
            throw new RuntimeException('Exceeded filesize limit.');
        }

        $data_dir = realpath(__DIR__ . '/../../data');

        if ($data_dir === false || !is_dir($data_dir) || !is_writable($data_dir)) {
            throw new RuntimeException('Upload directory is not writable.');
        }

        $file_name = basename($_FILES['file']['name']);
        $file_name = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file_name);
        $saved_file_name = save_uploaded_file($_FILES['file'], $data_dir, $file_name);

        echo json_encode([
            'status' => 'success',
            'message' => 'File uploaded successfully.',
        ]);
    } catch (RuntimeException $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}
