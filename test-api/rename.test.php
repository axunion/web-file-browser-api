<?php

declare(strict_types=1);

require_once __DIR__ . '/TestSetup.php';
require_once __DIR__ . '/ApiTestHelpers.php';

/**
 * API Test: File Rename Endpoint
 * Tests: /api/rename/
 */

echo "Testing file rename endpoint...\n";

const DATA_DIR = __DIR__ . '/../public/data';

// Setup: Create test files
$testFile1 = DATA_DIR . '/test-rename-file.txt';
$testFile2 = DATA_DIR . '/test-rename-source.txt';
file_put_contents($testFile1, 'Test content for rename');
file_put_contents($testFile2, 'Source file for renaming');

// Test 1: Rename file in root directory
echo "  - Rename file in root... ";
$response = ApiTestHelpers::post('/api/rename/', [
    'path' => '',
    'name' => 'test-rename-file.txt',
    'newName' => 'renamed-file.txt',
]);
ApiTestHelpers::assertSuccess($response, 'Rename file');
ApiTestHelpers::assertTrue(!file_exists($testFile1), 'Original file removed');
ApiTestHelpers::assertTrue(file_exists(DATA_DIR . '/renamed-file.txt'), 'New file exists');
unlink(DATA_DIR . '/renamed-file.txt'); // Cleanup
echo "OK\n";

// Test 2: Rename file in subdirectory
echo "  - Rename file in subdirectory... ";
$subFile = DATA_DIR . '/directory/test-rename-sub.txt';
file_put_contents($subFile, 'Subdirectory file');
$response = ApiTestHelpers::post('/api/rename/', [
    'path' => 'directory',
    'name' => 'test-rename-sub.txt',
    'newName' => 'renamed-sub.txt',
]);
ApiTestHelpers::assertSuccess($response, 'Rename file in subdirectory');
ApiTestHelpers::assertTrue(!file_exists($subFile), 'Original file removed from subdirectory');
ApiTestHelpers::assertTrue(file_exists(DATA_DIR . '/directory/renamed-sub.txt'), 'Renamed file exists');
unlink(DATA_DIR . '/directory/renamed-sub.txt'); // Cleanup
echo "OK\n";

// Test 3: Invalid filename (empty)
echo "  - Reject empty filename... ";
$response = ApiTestHelpers::post('/api/rename/', [
    'path' => '',
    'name' => 'test-rename-source.txt',
    'newName' => '',
]);
ApiTestHelpers::assertError($response, 400, 'Empty filename rejected');
ApiTestHelpers::assertTrue(file_exists($testFile2), 'Original file unchanged');
echo "OK\n";

// Test 4: Invalid filename (path traversal)
echo "  - Reject path traversal in new name... ";
$response = ApiTestHelpers::post('/api/rename/', [
    'path' => '',
    'name' => 'test-rename-source.txt',
    'newName' => '../../../etc/passwd',
]);
ApiTestHelpers::assertError($response, 400, 'Path traversal in newName rejected');
ApiTestHelpers::assertTrue(file_exists($testFile2), 'Original file unchanged');
echo "OK\n";

// Test 5: Invalid filename (null byte)
echo "  - Reject null byte in filename... ";
$response = ApiTestHelpers::post('/api/rename/', [
    'path' => '',
    'name' => 'test-rename-source.txt',
    'newName' => "test\x00.txt",
]);
ApiTestHelpers::assertError($response, 400, 'Null byte rejected');
ApiTestHelpers::assertTrue(file_exists($testFile2), 'Original file unchanged');
echo "OK\n";

// Test 6: Invalid source path (path traversal)
echo "  - Reject path traversal in source... ";
$response = ApiTestHelpers::post('/api/rename/', [
    'path' => '../../../etc',
    'name' => 'passwd',
    'newName' => 'hacked.txt',
]);
ApiTestHelpers::assertError($response, 400, 'Path traversal in source rejected');
echo "OK\n";

// Test 7: Non-existent source file
echo "  - Handle non-existent source... ";
$response = ApiTestHelpers::post('/api/rename/', [
    'path' => '',
    'name' => 'non-existent-file.txt',
    'newName' => 'new-name.txt',
]);
ApiTestHelpers::assertError($response, 400, 'Non-existent source file');
echo "OK\n";

// Test 8: Reserved filename (Windows)
echo "  - Reject reserved filename... ";
$response = ApiTestHelpers::post('/api/rename/', [
    'path' => '',
    'name' => 'test-rename-source.txt',
    'newName' => 'CON.txt',
]);
ApiTestHelpers::assertError($response, 400, 'Reserved filename rejected');
ApiTestHelpers::assertTrue(file_exists($testFile2), 'Original file unchanged');
echo "OK\n";

// Test 9: Filename with special characters
echo "  - Handle special characters... ";
$response = ApiTestHelpers::post('/api/rename/', [
    'path' => '',
    'name' => 'test-rename-source.txt',
    'newName' => 'file_with-special.chars_123.txt',
]);
ApiTestHelpers::assertSuccess($response, 'Special characters allowed');
ApiTestHelpers::assertTrue(!file_exists($testFile2), 'Original file removed');
ApiTestHelpers::assertTrue(
    file_exists(DATA_DIR . '/file_with-special.chars_123.txt'),
    'File with special characters exists'
);
unlink(DATA_DIR . '/file_with-special.chars_123.txt'); // Cleanup
echo "OK\n";

// Test 10: Filename conflict (returns error, doesn't auto-rename)
echo "  - Handle filename conflict... ";
$existingFile = DATA_DIR . '/existing-file.txt';
$conflictSource = DATA_DIR . '/conflict-source.txt';
file_put_contents($existingFile, 'Existing file');
file_put_contents($conflictSource, 'File to rename');

$response = ApiTestHelpers::post('/api/rename/', [
    'path' => '',
    'name' => 'conflict-source.txt',
    'newName' => 'existing-file.txt',
]);
// Rename endpoint rejects conflicts rather than auto-renaming
ApiTestHelpers::assertError($response, 400, 'Filename conflict rejected');
ApiTestHelpers::assertTrue(file_exists($conflictSource), 'Source file unchanged');
ApiTestHelpers::assertTrue(file_exists($existingFile), 'Existing file unchanged');

// Cleanup
unlink($existingFile);
unlink($conflictSource);
echo "OK\n";

// Test 11: Reject GET method (rename is POST-only)
echo "  - Reject GET method... ";
$response = ApiTestHelpers::get('/api/rename/', ['path' => '', 'name' => 'a.txt', 'newName' => 'b.txt']);
ApiTestHelpers::assertError($response, 405, 'GET method rejected on rename endpoint');
echo "OK\n";

echo "All rename endpoint tests passed!\n";
