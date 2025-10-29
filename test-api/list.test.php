<?php

declare(strict_types=1);

require_once __DIR__ . '/ApiTestHelpers.php';

/**
 * API Test: Directory Listing Endpoint
 * Tests: /web-file-browser-api/list/
 */

echo "Testing directory listing endpoint...\n";

// Test 1: List root directory
echo "  - List root directory... ";
$response = ApiTestHelpers::get('/web-file-browser-api/list/', ['path' => '']);
ApiTestHelpers::assertSuccess($response, 'Root directory listing');
ApiTestHelpers::assertArrayHasKey('list', $response['json'], 'Response has list key');
assert(is_array($response['json']['list']), 'List is an array');
echo "OK\n";

// Test 2: List existing subdirectory
echo "  - List existing subdirectory... ";
$response = ApiTestHelpers::get('/web-file-browser-api/list/', ['path' => 'directory']);
ApiTestHelpers::assertSuccess($response, 'Subdirectory listing');
ApiTestHelpers::assertArrayHasKey('list', $response['json'], 'Response has list key');
echo "OK\n";

// Test 3: Invalid path (path traversal attempt)
echo "  - Reject path traversal attempt... ";
$response = ApiTestHelpers::get('/web-file-browser-api/list/', ['path' => '../../../etc']);
ApiTestHelpers::assertError($response, 400, 'Path traversal rejected');
echo "OK\n";

// Test 4: Non-existent directory
echo "  - Handle non-existent directory... ";
$response = ApiTestHelpers::get('/web-file-browser-api/list/', ['path' => 'nonexistent-dir']);
ApiTestHelpers::assertError($response, 400, 'Non-existent directory');
echo "OK\n";

// Test 5: Verify response structure
echo "  - Verify response structure... ";
$response = ApiTestHelpers::get('/web-file-browser-api/list/', ['path' => '']);
ApiTestHelpers::assertSuccess($response);
if (!empty($response['json']['list'])) {
    $firstItem = $response['json']['list'][0];
    ApiTestHelpers::assertArrayHasKey('name', $firstItem, 'List item has name');
    ApiTestHelpers::assertArrayHasKey('type', $firstItem, 'List item has type');
    assert(
        in_array($firstItem['type'], ['file', 'directory'], true),
        'Item type is file or directory'
    );
}
echo "OK\n";

// Test 6: Check content-type header
echo "  - Verify JSON content-type... ";
$response = ApiTestHelpers::get('/web-file-browser-api/list/', ['path' => '']);
assert(
    str_contains($response['headers']['Content-Type'] ?? '', 'application/json'),
    'Content-Type is application/json'
);
echo "OK\n";

echo "All list endpoint tests passed!\n";
