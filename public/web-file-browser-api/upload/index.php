<?php

require_once __DIR__ . '/../../src/save_uploaded_file.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        if (!isset($_FILES['file'])) {
            throw new RuntimeException('No file uploaded.');
        }

        if ($_FILES['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed with error code ' . $_FILES['file']['error']);
        }

        if ($uploaded_file['size'] > 10000000000) {
            throw new RuntimeException('Exceeded filesize limit.');
        }

        $data_dir = realpath(__DIR__ . '/../../data');
        $saved_file_name = save_uploaded_file($_FILES['file'], $data_dir, $_FILES['file']['name']);

        echo json_encode([
            'status' => 'success',
            'message' => 'File uploaded successfully.',
            'file_name' => $saved_file_name,
        ]);
    } catch (RuntimeException $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}
