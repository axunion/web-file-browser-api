<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/filepath_utils.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/rename_file.php';

/**
 * Send a JSON response and exit.
 */
function sendJson(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    sendJson(['status' => 'error', 'message' => 'Method not allowed.'], 405);
}

try {
    $dataDir = realpath(__DIR__ . '/../../data');

    if ($dataDir === false || !is_dir($dataDir)) {
        throw new RuntimeException('Server configuration error: data directory not found.');
    }

    $subPath      = filter_input(INPUT_POST, 'path',      FILTER_UNSAFE_RAW) ?? '';
    $currentName  = filter_input(INPUT_POST, 'name',      FILTER_UNSAFE_RAW) ?? '';
    $newName      = filter_input(INPUT_POST, 'newName',   FILTER_UNSAFE_RAW) ?? '';

    if ($currentName === '') {
        throw new RuntimeException('Current file name is required.');
    }

    if ($newName === '') {
        throw new RuntimeException('New file name is required.');
    }

    validateFileName($currentName);
    validateFileName($newName);

    $targetDir = resolveSafePath($dataDir, $subPath);

    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        throw new RuntimeException('Specified path is not a writable directory.');
    }

    $renamedPath = renameFile($targetDir, $currentName, $newName);

    sendJson([
        'status'   => 'success',
        'path'     => $subPath,
        'filename' => basename($renamedPath),
        'fullPath' => $renamedPath,
    ], 200);
} catch (RuntimeException $e) {
    error_log('Rename error: ' . $e->getMessage());
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('Unexpected rename error: ' . $e->getMessage());
    sendJson(['status' => 'error', 'message' => 'An unexpected error occurred.'], 500);
}
