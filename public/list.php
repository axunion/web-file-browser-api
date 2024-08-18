<?php

require __DIR__ . '/../src/get_directory_structure.php';

header('Content-Type: application/json');

try {
    $dataDir = realpath(__DIR__ . '/../data');
    $subPath = isset($_GET['path']) ? $_GET['path'] : '';
    $targetPath = realpath($dataDir . DIRECTORY_SEPARATOR . $subPath);

    if ($targetPath === false || strpos($targetPath, $dataDir) !== 0) {
        throw new Exception("Invalid path specified.");
    }

    $directoryStructure = get_directory_structure($targetPath);

    echo json_encode([
        'status' => 'success',
        'data' => $directoryStructure
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
