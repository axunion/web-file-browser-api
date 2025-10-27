<?php

declare(strict_types=1);

require_once __DIR__ . '/PathSecurity.php';
require_once __DIR__ . '/Exceptions.php';
require_once __DIR__ . '/Config.php';

/**
 * Abstract base class for HTTP request handling.
 */
abstract class RequestHandler
{
    protected string $dataDir;
    protected string $trashDir;
    protected array $allowedMethods = ['GET', 'POST'];

    public function __construct()
    {
        $this->dataDir = realpath(__DIR__ . '/../../public/data');
        $this->trashDir = realpath(__DIR__ . '/../../public/trash');

        if ($this->dataDir === false || $this->trashDir === false) {
            $this->sendError('Server configuration error.', 500);
        }
    }

    /**
     * Main entry point for request handling.
     */
    public function handle(): void
    {
        $this->handleCors();

        try {
            $this->validateMethod();
            $this->process();
        } catch (PathException | ValidationException | DirectoryException $e) {
            error_log(static::class . ' error: ' . $e->getMessage());
            $this->sendError($e->getMessage(), 400);
        } catch (RuntimeException $e) {
            error_log(static::class . ' error: ' . $e->getMessage());
            $this->sendError($e->getMessage(), 400);
        } catch (Throwable $e) {
            error_log('Unexpected error in ' . static::class . ': ' . $e->getMessage());
            $this->sendError('An unexpected error occurred.', 500);
        }
    }

    /**
     * Handle CORS (Cross-Origin Resource Sharing) if enabled.
     */
    private function handleCors(): void
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

    /**
     * Implement this method to define endpoint-specific logic.
     */
    abstract protected function process(): void;

    /**
     * Validate HTTP method is allowed.
     */
    protected function validateMethod(): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], $this->allowedMethods, true)) {
            header('Allow: ' . implode(', ', $this->allowedMethods));
            $this->sendError('Method not allowed.', 405);
        }
    }

    /**
     * Resolve user path safely within the data directory.
     */
    protected function resolvePath(string $userPath): string
    {
        return PathSecurity::resolveSafePath($this->dataDir, $userPath);
    }

    /**
     * Resolve user path safely, allowing access to both data and trash directories.
     *
     * If the path starts with "trash/", it resolves to the trash directory.
     * Otherwise, it resolves to the data directory.
     */
    protected function resolvePathWithTrash(string $userPath): string
    {
        $segments = explode('/', trim($userPath, '/'));

        if (isset($segments[0]) && $segments[0] === 'trash') {
            $base = $this->trashDir;
            $path = isset($segments[1]) ? implode('/', array_slice($segments, 1)) : '';
            return PathSecurity::resolveSafePath($base, $path);
        }

        return PathSecurity::resolveSafePath($this->dataDir, $userPath);
    }

    /**
     * Send JSON success response.
     */
    protected function sendSuccess(array $data = [], int $code = 200): void
    {
        $this->sendJson(array_merge(['status' => 'success'], $data), $code);
    }

    /**
     * Send JSON error response.
     */
    protected function sendError(string $message, int $code = 400): void
    {
        $this->sendJson(['status' => 'error', 'message' => $message], $code);
    }

    /**
     * Get input parameter safely.
     */
    protected function getInput(int $type, string $key, mixed $default = null): mixed
    {
        return filter_input($type, $key, FILTER_UNSAFE_RAW) ?? $default;
    }

    /**
     * Send JSON response and terminate.
     */
    private function sendJson(array $payload, int $httpCode): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
