<?php

declare(strict_types=1);

require_once __DIR__ . '/TestSetup.php';
require_once __DIR__ . '/ApiTestHelpers.php';

/**
 * API Test: File Delete Endpoint
 * Tests: /web-file-browser-api/delete/
 */

echo "Testing file delete endpoint...\n";

const DATA_DIR = __DIR__ . '/../public/data';
const TRASH_DIR = __DIR__ . '/../public/trash';

// Setup: create test files
$rootFile = DATA_DIR . '/test-delete-file.txt';
$subFile = DATA_DIR . '/directory/test-delete-sub.txt';
file_put_contents($rootFile, 'Delete me');
file_put_contents($subFile, 'Delete me in subdir');

// Test 1: Delete file in root directory
echo "  - Delete file in root... ";
$response = ApiTestHelpers::post('/web-file-browser-api/delete/', [
    'path' => '',
    'name' => 'test-delete-file.txt',
]);
ApiTestHelpers::assertSuccess($response, 'Delete root file');
ApiTestHelpers::assertArrayHasKey('filename', $response['json'], 'Response has filename');
$deletedName = $response['json']['filename'];
assert(!file_exists($rootFile), 'Original file removed');
assert(file_exists(TRASH_DIR . '/' . $deletedName), 'File moved to trash');
@unlink(TRASH_DIR . '/' . $deletedName); // Cleanup moved file
echo "OK\n";

// Test 2: Delete file in subdirectory
echo "  - Delete file in subdirectory... ";
$response = ApiTestHelpers::post('/web-file-browser-api/delete/', [
    'path' => 'directory',
    'name' => 'test-delete-sub.txt',
]);
ApiTestHelpers::assertSuccess($response, 'Delete file in subdirectory');
ApiTestHelpers::assertArrayHasKey('filename', $response['json'], 'Response has filename');
$deletedName = $response['json']['filename'];
assert(!file_exists($subFile), 'Original subdirectory file removed');
assert(file_exists(TRASH_DIR . '/' . $deletedName), 'Subdirectory file moved to trash');
@unlink(TRASH_DIR . '/' . $deletedName); // Cleanup moved file
echo "OK\n";

// Test 3: Missing filename (invalid)
echo "  - Reject missing filename... ";
$response = ApiTestHelpers::post('/web-file-browser-api/delete/', [
    'path' => '',
    'name' => '',
]);
ApiTestHelpers::assertError($response, 400, 'Missing filename rejected');
echo "OK\n";

// Test 4: Path traversal attempt in path
echo "  - Reject path traversal in path... ";
$response = ApiTestHelpers::post('/web-file-browser-api/delete/', [
    'path' => '../../../etc',
    'name' => 'passwd',
]);
ApiTestHelpers::assertError($response, 400, 'Path traversal rejected');
echo "OK\n";

// Test 5: Non-existent file
echo "  - Handle non-existent file... ";
$response = ApiTestHelpers::post('/web-file-browser-api/delete/', [
    'path' => '',
    'name' => 'this-file-does-not-exist.txt',
]);
ApiTestHelpers::assertError($response, 400, 'Non-existent file handled');
echo "OK\n";

echo "All delete endpoint tests passed!\n";
