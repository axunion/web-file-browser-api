<?php

declare(strict_types=1);

/**
 * Send a JSON response and terminate execution.
 *
 * @param array $payload   Response data to be JSON-encoded.
 * @param int   $httpCode  HTTP status code.
 * @return void
 */
function sendJson(array $payload, int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
