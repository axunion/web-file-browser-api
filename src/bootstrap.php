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
        $publicRoot = realpath(__DIR__ . '/../public') ?: __DIR__ . '/../public';
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

    // Check directory configuration (skip in test mode)
    if (!defined('TESTING_MODE')) {
        $errors = [];

        if ($dataDirResolved === false) {
            $errors[] = 'Data directory not found or not accessible: ' . $dataDir;
        }

        if ($trashDirResolved === false) {
            $errors[] = 'Trash directory not found or not accessible: ' . $trashDir;
        }

        if (!empty($errors)) {
            error_log('API configuration error: ' . implode(', ', $errors));
            sendError('Server configuration error.', 500);
        }
    }

    if (!defined('API_DATA_DIR')) {
        define('API_DATA_DIR', $dataDirResolved === false ? '' : $dataDirResolved);
    }

    if (!defined('API_TRASH_DIR')) {
        define('API_TRASH_DIR', $trashDirResolved === false ? '' : $trashDirResolved);
    }
}

// Read a string value from the optional src/config.local.php override file.
// The file is gitignored and placed manually on the server; it must return
// an array (e.g. ['api_key' => '...', 'cors_allowed_origin' => '...']).
function localConfig(string $key): ?string
{
    static $config = null;

    if ($config === null) {
        $file = __DIR__ . '/config.local.php';
        $loaded = is_file($file) ? require $file : [];
        $config = is_array($loaded) ? $loaded : [];
    }

    $value = $config[$key] ?? null;

    return is_string($value) && $value !== '' ? $value : null;
}

// Constant-time API key comparison
function isApiKeyValid(string $configuredKey, ?string $providedKey): bool
{
    return $providedKey !== null && hash_equals($configuredKey, $providedKey);
}

// Require a valid X-Api-Key header when a key is configured.
// The API_KEY environment variable (used by tests/CI) takes precedence over
// config.local.php; an empty env value falls through to the local config.
// No configured key means authentication is disabled.
function requireApiKey(): void
{
    $envKey = getenv('API_KEY');
    $configured = ($envKey !== false && $envKey !== '') ? $envKey : localConfig('api_key');

    if ($configured === null) {
        return;
    }

    $provided = $_SERVER['HTTP_X_API_KEY'] ?? null;

    if (!is_string($provided) || !isApiKeyValid($configured, $provided)) {
        sendError('Invalid or missing API key.', 401);
    }
}

// CORS handling (skip in test mode)
function handleCors(): void
{
    if (!Config::ENABLE_CORS) {
        return;
    }

    $origin = localConfig('cors_allowed_origin') ?? Config::CORS_ALLOWED_ORIGIN;

    header('Access-Control-Allow-Origin: ' . $origin);
    if ($origin !== '*') {
        header('Vary: Origin');
    }
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
/**
 * @param list<string> $allowedMethods
 */
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

    if ($segments[0] === 'trash') {
        return PathSecurity::resolveSafePath(API_TRASH_DIR, implode('/', array_slice($segments, 1)));
    }

    return resolvePath($userPath);
}

// Safe input retrieval
/**
 * @param INPUT_GET|INPUT_POST $type
 */
function getInput(int $type, string $key, string $default = ''): string
{
    $value = filter_input($type, $key, FILTER_UNSAFE_RAW);

    // filter_input() returns false for non-scalar values (e.g. array parameters)
    if ($value === false) {
        throw new ValidationException("Invalid value for parameter '{$key}'.");
    }

    return $value ?? $default;
}

// JSON response helpers
/**
 * @param array<string, mixed> $data
 */
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
    if ($e instanceof PathException || $e instanceof ValidationException) {
        sendError($e->getMessage(), 400);
    }

    if ($e instanceof DirectoryException) {
        sendError('The requested directory operation could not be completed.', 400);
    }

    // Runtime/business logic errors (400 Bad Request)
    if ($e instanceof RuntimeException) {
        sendError('The requested operation could not be completed.', 400);
    }

    // Unexpected errors (500 Internal Server Error)
    sendError('An unexpected error occurred.', 500);
}

// Initialize CORS handling and authentication (skip in test mode).
// Order matters: handleCors() exits early for OPTIONS, keeping CORS
// preflight requests unauthenticated.
if (!defined('TESTING_MODE')) {
    handleCors();
    requireApiKey();
}
