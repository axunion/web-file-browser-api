<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/filepath_utils.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/get_directory_structure.php';

function sendJson(array $payload, int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    sendJson(['status' => 'error', 'message' => 'Method not allowed.'], 405);
}

try {
    $dataDir  = realpath(__DIR__ . '/../../data');
    $trashDir = realpath(__DIR__ . '/../../trash');

    if ($dataDir === false || $trashDir === false) {
        throw new Exception('Server configuration error.');
    }

    $rawPath = filter_input(INPUT_GET, 'path', FILTER_UNSAFE_RAW) ?? '';
    $segments = explode('/', trim($rawPath, '/'));

    if (isset($segments[0]) && $segments[0] === 'trash') {
        $base     = $trashDir;
        $userPath = isset($segments[1]) ? implode('/', array_slice($segments, 1)) : '';
    } else {
        $base     = $dataDir;
        $userPath = $rawPath;
    }

    $target = resolveSafePath($base, $userPath);

    if (!is_dir($target) || !is_readable($target)) {
        throw new RuntimeException('Specified path is not a readable directory.');
    }

    $items = getDirectoryStructure($target);
    $list  = array_map(fn($item) => [
        'type' => $item->type->value,
        'name' => $item->name,
        'size' => $item->size,
    ], $items);

    sendJson(['status' => 'success', 'list' => $list], 200);
} catch (DirectoryException $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 400);
} catch (RuntimeException $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 400);
} catch (JsonException $e) {
    sendJson(['status' => 'error', 'message' => 'Failed to encode JSON response.'], 500);
} catch (Throwable $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 500);
}
