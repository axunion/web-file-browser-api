<?php

require_once __DIR__ . '/../../../src/web-file-browser-api/rename_file.php';

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
    $current_name = filter_input(INPUT_POST, 'name') ?? '';
    $new_name = filter_input(INPUT_POST, 'newName') ?? '';

    if (empty($current_name)) {
        throw new RuntimeException('Current file name is required.');
    }

    if (empty($new_name)) {
        throw new RuntimeException('New file name is required.');
    }

    $target_path = validate_and_resolve_path($data_dir, $sub_path);

    if (!is_writable($target_path)) {
        throw new RuntimeException('The specified path is not writable.');
    }

    $renamed_file_name = rename_file($target_path, $current_name, $new_name);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'path' => $sub_path,
        'filename' => $renamed_file_name,
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
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred.',
    ], JSON_THROW_ON_ERROR);
}
