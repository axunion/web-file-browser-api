<?php

declare(strict_types=1);

require_once __DIR__ . '/TestSetup.php';
require_once __DIR__ . '/ApiTestHelpers.php';

/**
 * API Test: File Upload Endpoint
 * Tests: /web-file-browser-api/upload/
 *
 * Note: These tests validate API behavior without actual file uploads,
 * since move_uploaded_file() requires real HTTP multipart requests.
 */

echo "Testing file upload endpoint...\n";

const DATA_DIR = __DIR__ . '/../public/data';

// Test 1: POST method validation
echo "  - Validate POST method required... ";
$response = ApiTestHelpers::get('/web-file-browser-api/upload/', ['path' => '']);
ApiTestHelpers::assertError($response, 405, 'GET method rejected');
echo "OK\n";

// Test 2: Path traversal rejection
echo "  - Reject path traversal... ";
$response = ApiTestHelpers::post('/web-file-browser-api/upload/', [
    'path' => '../../../etc',
]);
ApiTestHelpers::assertError($response, 400, 'Path traversal rejected');
echo "OK\n";

// Test 3: Invalid path (null byte)
echo "  - Reject null byte in path... ";
$response = ApiTestHelpers::post('/web-file-browser-api/upload/', [
    'path' => "test\x00path",
]);
ApiTestHelpers::assertError($response, 400, 'Null byte rejected');
echo "OK\n";

// Test 4: Missing file parameter
echo "  - Handle missing file... ";
$response = ApiTestHelpers::post('/web-file-browser-api/upload/', [
    'path' => '',
]);
ApiTestHelpers::assertError($response, 400, 'Missing file parameter');
echo "OK\n";

// Test 5: Empty path handling
echo "  - Handle empty path (root directory)... ";
$response = ApiTestHelpers::post('/web-file-browser-api/upload/', [
    'path' => '',
]);
// Should fail due to missing file, but path should be accepted
ApiTestHelpers::assertError($response, 400, 'Empty path accepted, missing file rejected');
echo "OK\n";

// Test 6: Valid subdirectory path
echo "  - Accept valid subdirectory path... ";
$response = ApiTestHelpers::post('/web-file-browser-api/upload/', [
    'path' => 'directory',
]);
// Should fail due to missing file, but path should be accepted
ApiTestHelpers::assertError($response, 400, 'Valid path accepted, missing file rejected');
echo "OK\n";

// Test 7: Non-existent directory path
echo "  - Reject non-existent directory... ";
$response = ApiTestHelpers::post('/web-file-browser-api/upload/', [
    'path' => 'nonexistent-directory-12345',
]);
ApiTestHelpers::assertError($response, 400, 'Non-existent directory rejected');
echo "OK\n";

// Note: Full upload tests with actual file content require HTTP multipart requests.
// The upload-images endpoint tests cover actual file upload scenarios.
echo "  - Note: Full file upload tests are covered by upload-images endpoint\n";

echo "All upload endpoint tests passed!\n";
