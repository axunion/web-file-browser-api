<?php

require_once __DIR__ . '/../../../src/get_directory_structure.php';

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

    if ($data_dir === false || !is_dir($data_dir)) {
        throw new RuntimeException('Base data directory does not exist or is not a directory.');
    }

    $sub_path = filter_input(INPUT_GET, 'path') ?? '';
    $sub_path = trim($sub_path);

    if (preg_match('/\.\.|\x00/', $sub_path)) {
        throw new RuntimeException('Invalid path.');
    }

    $target_path = realpath($data_dir . DIRECTORY_SEPARATOR . $sub_path);

    if ($target_path === false || strpos($target_path, $data_dir) !== 0) {
        throw new RuntimeException('Invalid or restricted path.');
    }

    if (!is_dir($target_path) || !is_readable($target_path)) {
        throw new RuntimeException('Directory does not exist or is not readable.');
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
