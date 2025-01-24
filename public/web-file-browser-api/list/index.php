<?php

require_once __DIR__ . '/../../../src/web-file-browser-api/get_directory_structure.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/validate_and_resolve_path.php';

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
    $sub_path = filter_input(INPUT_GET, 'path') ?? '';
    $target_path = validate_and_resolve_path($data_dir, $sub_path);

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
}
