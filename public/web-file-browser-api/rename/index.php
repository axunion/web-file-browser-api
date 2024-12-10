<?php

require_once __DIR__ . '/../../../src/rename_file.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed.',
    ]);
    exit;
}

try {
    $data_dir = realpath(__DIR__ . '/../../data');

    if ($data_dir === false) {
        http_response_code(500);
        throw new Exception('Base data directory does not exist.');
    }

    $sub_path = filter_input(INPUT_POST, 'path') ?? '';
    $target_path = realpath($data_dir . DIRECTORY_SEPARATOR . $sub_path);

    if ($target_path === false || strpos($target_path, $data_dir) !== 0) {
        throw new Exception("Invalid or restricted path: {$sub_path}");
    }

    if (!is_dir($target_path) || !is_readable($target_path)) {
        throw new Exception("Directory does not exist or is not readable: {$target_path}");
    }

    $current_name = trim(filter_input(INPUT_POST, 'currentName') ?? '');
    $new_name = trim(filter_input(INPUT_POST, 'newName') ?? '');

    if (empty($current_name)) {
        throw new Exception('Current file name is required.');
    }

    if (empty($new_name)) {
        throw new Exception('New file name is required.');
    }

    $renamed_file_name = rename_file($target_path, $current_name, $new_name);

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
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
