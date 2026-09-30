<?php

declare(strict_types=1);

require_once __DIR__ . '/TestSetup.php';
require_once __DIR__ . '/ApiTestHelpers.php';

/**
 * API Test: API Key Authentication
 * Tests: bootstrap requireApiKey() via /api/list/
 *
 * Starts a dedicated server with the API_KEY environment variable set,
 * since the shared test server runs without a configured key.
 */

const AUTH_SERVER_PORT = 8001;
const AUTH_API_KEY = 'test-secret-key';

echo "Testing API key authentication...\n";

$authServerPid = startTestServer(AUTH_SERVER_PORT, 'API_KEY=' . AUTH_API_KEY);

try {
    ApiTestHelpers::setBaseUrl('http://' . SERVER_HOST . ':' . AUTH_SERVER_PORT);

    // Test 1: Request without key is rejected
    echo "  - Reject request without key... ";
    $response = ApiTestHelpers::get('/api/list/', ['path' => '']);
    ApiTestHelpers::assertError($response, 401, 'Missing API key rejected');
    echo "OK\n";

    // Test 2: Request with wrong key is rejected
    echo "  - Reject request with wrong key... ";
    ApiTestHelpers::setApiKey('wrong-key');
    $response = ApiTestHelpers::get('/api/list/', ['path' => '']);
    ApiTestHelpers::assertError($response, 401, 'Wrong API key rejected');
    echo "OK\n";

    // Test 3: Request with correct key succeeds
    echo "  - Accept request with correct key... ";
    ApiTestHelpers::setApiKey(AUTH_API_KEY);
    $response = ApiTestHelpers::get('/api/list/', ['path' => '']);
    ApiTestHelpers::assertSuccess($response, 'Correct API key accepted');
    echo "OK\n";

    // Test 4: CORS preflight OPTIONS stays unauthenticated
    echo "  - Allow OPTIONS preflight without key... ";
    $ch = curl_init('http://' . SERVER_HOST . ':' . AUTH_SERVER_PORT . '/api/list/');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    ApiTestHelpers::assertEquals(204, $httpCode, 'OPTIONS preflight unauthenticated');
    echo "OK\n";
} finally {
    ApiTestHelpers::setApiKey(null);
    stopTestServer($authServerPid);
}

echo "All auth tests passed!\n";
