<?php

require_once __DIR__ . '/../../src/get_directory_structure.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    try {
        $data_dir = realpath(__DIR__ . '/../../data');
        $sub_path = filter_input(INPUT_GET, 'path', FILTER_DEFAULT);
        $sub_path = $sub_path !== null ? strip_tags($sub_path) : '';
        $target_path = realpath($data_dir . DIRECTORY_SEPARATOR . $sub_path);

        if ($target_path === false || strpos($target_path, $data_dir) !== 0) {
            throw new Exception("Invalid or restricted path.");
        }

        if (!is_dir($target_path) || !is_readable($target_path)) {
            throw new Exception("Directory does not exist or is not readable.");
        }

        $list = get_directory_structure($target_path);

        echo json_encode([
            'status' => 'success',
            'list' => $list
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred while fetching the directory structure.'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed.'
    ]);
}
