<?php

declare(strict_types=1);

/**
 * Bootstrap file for Web File Browser API
 *
 * Loads all required classes and provides helper functions for endpoints.
 */

// Load all core classes in dependency order
require_once __DIR__ . '/Exceptions.php';
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/PathSecurity.php';
require_once __DIR__ . '/DirectoryScanner.php';
require_once __DIR__ . '/FileOperations.php';
require_once __DIR__ . '/UploadValidator.php';

// Initialize base directories (only if not in test mode)
if (!defined('API_DATA_DIR') || !defined('API_TRASH_DIR')) {
    // Try to discover the public root by walking up from the executing script.
    // We look for a directory that contains at least one of 'data' or 'trash'.
    $publicRoot = null;

    if (isset($_SERVER['SCRIPT_FILENAME']) && is_string($_SERVER['SCRIPT_FILENAME'])) {
        $scriptPath = $_SERVER['SCRIPT_FILENAME'];
        $current = dirname($scriptPath);

        // Walk up to 6 levels to find candidate containing data/trash
        for ($i = 0; $i < 6; $i++) {
            if ($current === '' || $current === '/' || $current === '.') {
                break;
            }

            if (is_dir($current . '/data') || is_dir($current . '/trash')) {
                $publicRoot = realpath($current) ?: $current;
                break;
            }

            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }
            $current = $parent;
        }
    }

    // Fallback to previous layout if discovery failed
    if ($publicRoot === null) {
        $publicRoot = realpath(__DIR__ . '/../../public') ?: __DIR__ . '/../../public';
    }

    $dataDir = $publicRoot . '/data';
    $trashDir = $publicRoot . '/trash';

    $dataDirResolved = realpath($dataDir);
    if ($dataDirResolved === false && !defined('TESTING_MODE')) {
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0755, true);
            $dataDirResolved = realpath($dataDir);
        }
    }

    $trashDirResolved = realpath($trashDir);
    if ($trashDirResolved === false && !defined('TESTING_MODE')) {
        if (!is_dir($trashDir)) {
            @mkdir($trashDir, 0755, true);
            $trashDirResolved = realpath($trashDir);
        }
    }

    if (!defined('API_DATA_DIR')) {
        define('API_DATA_DIR', $dataDirResolved);
    }

    if (!defined('API_TRASH_DIR')) {
        define('API_TRASH_DIR', $trashDirResolved);
    }

    // Check directory configuration (skip in test mode)
    if (!defined('TESTING_MODE')) {
        $errors = [];

        if (API_DATA_DIR === false) {
            $errors[] = 'Data directory not found or not accessible: ' . ($dataDir);
        }

        if (API_TRASH_DIR === false) {
            $errors[] = 'Trash directory not found or not accessible: ' . ($trashDir);
        }

        if (!empty($errors)) {
            error_log('API configuration error: ' . implode(', ', $errors));
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Server configuration error.']);
            exit;
        }
    }
}

// CORS handling (skip in test mode)
function handleCors(): void
{
    if (!Config::ENABLE_CORS) {
        return;
    }

    header('Access-Control-Allow-Origin: ' . Config::CORS_ALLOWED_ORIGIN);
    header('Access-Control-Allow-Methods: ' . Config::CORS_ALLOWED_METHODS);
    header('Access-Control-Allow-Headers: ' . Config::CORS_ALLOWED_HEADERS);
    header('Access-Control-Max-Age: ' . Config::CORS_MAX_AGE);

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// HTTP method validation
function validateMethod(array $allowedMethods): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods, true)) {
        header('Allow: ' . implode(', ', $allowedMethods));
        sendError('Method not allowed.', 405);
    }
}

// Path resolution (data directory only)
function resolvePath(string $userPath): string
{
    return PathSecurity::resolveSafePath(API_DATA_DIR, $userPath);
}

// Path resolution with trash directory support
// If path starts with "trash/", resolves to trash directory
function resolvePathWithTrash(string $userPath): string
{
    $segments = explode('/', trim($userPath, '/'));

    if (isset($segments[0]) && $segments[0] === 'trash') {
        $base = API_TRASH_DIR;
        $path = isset($segments[1]) ? implode('/', array_slice($segments, 1)) : '';
        return PathSecurity::resolveSafePath($base, $path);
    }

    return PathSecurity::resolveSafePath(API_DATA_DIR, $userPath);
}

// Safe input retrieval
function getInput(int $type, string $key, mixed $default = null): mixed
{
    return filter_input($type, $key, FILTER_UNSAFE_RAW) ?? $default;
}

// JSON response helpers
function sendSuccess(array $data = [], int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        array_merge(['status' => 'success'], $data),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function sendError(string $message, int $code = 400): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['status' => 'error', 'message' => $message],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

// Exception handler
function handleError(Throwable $e): void
{
    error_log('API error: ' . $e->getMessage());

    // User input errors (400 Bad Request)
    if ($e instanceof PathException ||
        $e instanceof ValidationException ||
        $e instanceof DirectoryException) {
        sendError($e->getMessage(), 400);
    }

    // Runtime/business logic errors (400 Bad Request)
    if ($e instanceof RuntimeException) {
        sendError($e->getMessage(), 400);
    }

    // Unexpected errors (500 Internal Server Error)
    sendError('An unexpected error occurred.', 500);
}

// Initialize CORS handling (skip in test mode)
if (!defined('TESTING_MODE')) {
    handleCors();
}
