<?php

require_once __DIR__ . '/../../../src/web-file-browser-api/delete_file.php';

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

    $sub_path = trim((string) filter_input(INPUT_POST, 'path'));
    $file_name = trim((string) filter_input(INPUT_POST, 'fileName'));

    if (empty($file_name)) {
        throw new RuntimeException('File name is required.');
    }

    if (preg_match('/[<>:"\/\\|?*]/', $file_name) || strlen($file_name) > 255) {
        throw new RuntimeException('File name contains invalid characters or is too long.');
    }

    $target_path = realpath($data_dir . DIRECTORY_SEPARATOR . $sub_path);

    if ($target_path === false || !str_starts_with($target_path, $data_dir . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('Invalid or restricted path.');
    }

    if (!is_dir($target_path) || !is_readable($target_path)) {
        throw new RuntimeException('Target directory does not exist or is not readable.');
    }

    delete_file($target_path, $file_name);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
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
