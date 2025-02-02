<?php

require_once __DIR__ . '/../../../src/web-file-browser-api/save_uploaded_file.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/filepath_utils.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed.',
    ], JSON_THROW_ON_ERROR);
    exit;
}

try {
    $data_dir = realpath(__DIR__ . '/../../data');
    $sub_path = filter_input(INPUT_POST, 'path') ?? '';
    $target_path = validate_and_resolve_path($data_dir, $sub_path);

    if (!is_writable($target_path)) {
        throw new RuntimeException('The specified path is not writable.');
    }

    if (!isset($_FILES['file'])) {
        throw new RuntimeException('No file uploaded.');
    }

    if (!isset($uploaded_file['tmp_name']) || !is_uploaded_file($uploaded_file['tmp_name'])) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    $upload_error = $_FILES['file']['error'];

    if ($upload_error !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
        ];

        $error_message = $error_messages[$upload_error] ?? 'Unknown upload error.';
        throw new RuntimeException($error_message);
    }

    $uploaded_filename = $_FILES['file']['name'];
    $sanitized_filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($uploaded_filename));

    if (strlen($sanitized_filename) === 0) {
        $sanitized_filename = 'default_' . time();
    }

    $saved_file_name = save_uploaded_file($_FILES['file'], $target_path, $sanitized_filename);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'path' => $sub_path,
        'filename' => $saved_file_name,
    ], JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to encode JSON response.',
    ]);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ], JSON_THROW_ON_ERROR);
}
