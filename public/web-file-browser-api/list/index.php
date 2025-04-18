<?php

require_once __DIR__ . '/../../../src/web-file-browser-api/filepath_utils.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/get_directory_structure.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed.'
    ], JSON_THROW_ON_ERROR);
    exit;
}

try {
    $data_dir = realpath(__DIR__ . '/../../data');
    $trash_dir = realpath(__DIR__ . '/../../trash');
    $sub_path = filter_input(INPUT_GET, 'path') ?? '';
    $target_path = validate_and_resolve_path($sub_path === 'trash' ? $trash_dir : $data_dir, $sub_path);

    if (!is_readable($target_path)) {
        throw new RuntimeException('The specified path is not readable.');
    }

    $list = get_directory_structure($target_path);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'list' => $list,
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
