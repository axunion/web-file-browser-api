<?php

require __DIR__ . '/../../src/get_directory_structure.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    try {
        $data_dir = realpath(__DIR__ . '/data');
        $sub_path = isset($_GET['path']) ? $_GET['path'] : '';
        $target_path = realpath($data_dir . DIRECTORY_SEPARATOR . $sub_path);

        if ($target_path === false || strpos($target_path, $data_dir) !== 0) {
            throw new Exception("Invalid path specified.");
        }

        $data = get_directory_structure($target_path);

        echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}
