<?php

require_once __DIR__ . '/../../../src/get_directory_structure.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed.'
    ]);
    exit;
}

try {
    $data_dir = realpath(__DIR__ . '/../../data');

    if ($data_dir === false) {
        throw new Exception('Base data directory does not exist.');
    }

    $sub_path = filter_input(INPUT_GET, 'path');
    $sub_path = $sub_path !== null ? trim($sub_path) : '';
    $target_path = realpath($data_dir . DIRECTORY_SEPARATOR . $sub_path);

    if ($target_path === false || strpos($target_path, $data_dir) !== 0) {
        throw new Exception('Invalid or restricted path.');
    }

    if (!is_dir($target_path) || !is_readable($target_path)) {
        throw new Exception('Directory does not exist or is not readable.');
    }

    $list = get_directory_structure($target_path);

    echo json_encode([
        'status' => 'success',
        'list' => $list
    ], JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'failure',
        'message' => 'Failed to encode JSON response.'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'failure',
        'message' => $e->getMessage(),
    ]);
}
