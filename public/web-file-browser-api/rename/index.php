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

    if ($data_dir === false || !is_dir($data_dir)) {
        throw new RuntimeException('Base data directory does not exist.');
    }

    $sub_path = trim(filter_input(INPUT_POST, 'path') ?? '');
    $current_name = trim(filter_input(INPUT_POST, 'currentName') ?? '');
    $new_name = trim(filter_input(INPUT_POST, 'newName') ?? '');

    if (empty($current_name)) {
        throw new RuntimeException('Current file name is required.');
    }

    if (empty($new_name)) {
        throw new RuntimeException('New file name is required.');
    }

    if (preg_match('/[<>:"\/\\|?*]/', $new_name)) {
        throw new RuntimeException('New file name contains invalid characters.');
    }

    $target_path = realpath($data_dir . DIRECTORY_SEPARATOR . $sub_path);

    if ($target_path === false || strpos($target_path, $data_dir) !== 0) {
        throw new RuntimeException('Invalid or restricted path.');
    }

    if (!is_dir($target_path) || !is_readable($target_path)) {
        throw new RuntimeException('Target directory does not exist or is not readable.');
    }

    $renamed_file_name = rename_file($target_path, $current_name, $new_name);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
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
