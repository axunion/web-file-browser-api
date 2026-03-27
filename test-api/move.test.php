<?php

declare(strict_types=1);

require_once __DIR__ . '/TestSetup.php';
require_once __DIR__ . '/ApiTestHelpers.php';

/**
 * API Test: File Move Endpoint
 * Tests: /api/move/
 */

echo "Testing file move endpoint...\n";

const DATA_DIR = __DIR__ . '/../public/data';

// Setup: create test directories and files
$testDir1 = DATA_DIR . '/move-test-src';
$testDir2 = DATA_DIR . '/move-test-dest';
@mkdir($testDir1, 0755, true);
@mkdir($testDir2, 0755, true);

// Test 1: Move file to another directory
echo "  - Move file to another directory... ";
$srcFile1 = $testDir1 . '/test-move-file.txt';
file_put_contents($srcFile1, 'Move me');
$response = ApiTestHelpers::post('/api/move/', [
    'path'            => 'move-test-src',
    'name'            => 'test-move-file.txt',
    'destinationPath' => 'move-test-dest',
]);
ApiTestHelpers::assertSuccess($response, 'Move file');
ApiTestHelpers::assertArrayHasKey('filename', $response['json'], 'Response has filename');
ApiTestHelpers::assertArrayHasKey('path', $response['json'], 'Response has path');
ApiTestHelpers::assertEquals('move-test-dest', $response['json']['path'], 'Destination path matches');
$movedName = $response['json']['filename'];
ApiTestHelpers::assertTrue(!file_exists($srcFile1), 'Original file removed');
ApiTestHelpers::assertTrue(file_exists($testDir2 . '/' . $movedName), 'File moved to destination');
@unlink($testDir2 . '/' . $movedName);
echo "OK\n";

// Test 2: Move file into subdirectory
echo "  - Move file into subdirectory... ";
$srcFile2 = DATA_DIR . '/test-move-to-sub.txt';
file_put_contents($srcFile2, 'Move to subdirectory');
$response = ApiTestHelpers::post('/api/move/', [
    'path'            => '',
    'name'            => 'test-move-to-sub.txt',
    'destinationPath' => 'move-test-dest',
]);
ApiTestHelpers::assertSuccess($response, 'Move file to subdirectory');
$movedName = $response['json']['filename'];
ApiTestHelpers::assertTrue(!file_exists($srcFile2), 'Original file removed');
ApiTestHelpers::assertTrue(file_exists($testDir2 . '/' . $movedName), 'File moved to subdirectory');
@unlink($testDir2 . '/' . $movedName);
echo "OK\n";

// Test 3: Move with filename collision (sequential naming)
echo "  - Move with filename collision... ";
$srcFile3 = $testDir1 . '/collision-test.txt';
$existingFile = $testDir2 . '/collision-test.txt';
file_put_contents($srcFile3, 'Source file');
file_put_contents($existingFile, 'Existing file');
$response = ApiTestHelpers::post('/api/move/', [
    'path'            => 'move-test-src',
    'name'            => 'collision-test.txt',
    'destinationPath' => 'move-test-dest',
]);
ApiTestHelpers::assertSuccess($response, 'Move with collision');
$movedName = $response['json']['filename'];
ApiTestHelpers::assertTrue($movedName !== 'collision-test.txt', 'Filename changed due to collision');
ApiTestHelpers::assertTrue(!file_exists($srcFile3), 'Original file removed');
ApiTestHelpers::assertTrue(file_exists($testDir2 . '/' . $movedName), 'File moved with new name');
@unlink($existingFile);
@unlink($testDir2 . '/' . $movedName);
echo "OK\n";

// Test 4: Move directory to another directory
echo "  - Move directory to another directory... ";
$dirSource = DATA_DIR . '/move-dir-src';
$dirNestedFile = $dirSource . '/nested.txt';
@mkdir($dirSource, 0755, true);
file_put_contents($dirNestedFile, 'Move the whole directory');
$response = ApiTestHelpers::post('/api/move/', [
    'path' => '',
    'name' => 'move-dir-src',
    'destinationPath' => 'move-test-dest',
]);
ApiTestHelpers::assertSuccess($response, 'Move directory');
ApiTestHelpers::assertTrue(!is_dir($dirSource), 'Original directory removed');
ApiTestHelpers::assertTrue(is_dir($testDir2 . '/move-dir-src'), 'Directory moved to destination');
ApiTestHelpers::assertTrue(
    file_exists($testDir2 . '/move-dir-src/nested.txt'),
    'Nested file preserved during directory move'
);
@unlink($testDir2 . '/move-dir-src/nested.txt');
@rmdir($testDir2 . '/move-dir-src');
echo "OK\n";

// Test 5: Reject path traversal in source path
echo "  - Reject path traversal in source path... ";
$response = ApiTestHelpers::post('/api/move/', [
    'path'            => '../../../etc',
    'name'            => 'passwd',
    'destinationPath' => 'move-test-dest',
]);
ApiTestHelpers::assertError($response, 400, 'Path traversal in source rejected');
echo "OK\n";

// Test 6: Reject path traversal in destination path
echo "  - Reject path traversal in destination path... ";
$srcFile5 = $testDir1 . '/traversal-test.txt';
file_put_contents($srcFile5, 'Test content');
$response = ApiTestHelpers::post('/api/move/', [
    'path'            => 'move-test-src',
    'name'            => 'traversal-test.txt',
    'destinationPath' => '../../../tmp',
]);
ApiTestHelpers::assertError($response, 400, 'Path traversal in destination rejected');
@unlink($srcFile5);
echo "OK\n";

// Test 7: Reject non-existent source file
echo "  - Reject non-existent source file... ";
$response = ApiTestHelpers::post('/api/move/', [
    'path'            => 'move-test-src',
    'name'            => 'non-existent-file.txt',
    'destinationPath' => 'move-test-dest',
]);
ApiTestHelpers::assertError($response, 400, 'Non-existent source file rejected');
echo "OK\n";

// Test 8: Reject non-existent destination directory
echo "  - Reject non-existent destination directory... ";
$srcFile7 = $testDir1 . '/dest-not-exist-test.txt';
file_put_contents($srcFile7, 'Test content');
$response = ApiTestHelpers::post('/api/move/', [
    'path'            => 'move-test-src',
    'name'            => 'dest-not-exist-test.txt',
    'destinationPath' => 'non-existent-directory',
]);
ApiTestHelpers::assertError($response, 400, 'Non-existent destination rejected');
@unlink($srcFile7);
echo "OK\n";

// Test 9: Reject missing name parameter
echo "  - Reject missing name parameter... ";
$response = ApiTestHelpers::post('/api/move/', [
    'path'            => 'move-test-src',
    'name'            => '',
    'destinationPath' => 'move-test-dest',
]);
ApiTestHelpers::assertError($response, 400, 'Missing name rejected');
echo "OK\n";

// Test 10: Reject missing destinationPath parameter
echo "  - Reject missing destinationPath parameter... ";
$response = ApiTestHelpers::post('/api/move/', [
    'path'            => 'move-test-src',
    'name'            => 'some-file.txt',
    'destinationPath' => '',
]);
ApiTestHelpers::assertError($response, 400, 'Missing destinationPath rejected');
echo "OK\n";

// Test 11: Reject slash in name parameter
echo "  - Reject slash in name parameter... ";
$nestedMoveSource = DATA_DIR . '/directory/move-path-injection.txt';
file_put_contents($nestedMoveSource, 'Stay in place');
$response = ApiTestHelpers::post('/api/move/', [
    'path' => '',
    'name' => 'directory/move-path-injection.txt',
    'destinationPath' => 'move-test-dest',
]);
ApiTestHelpers::assertError($response, 400, 'Slash in name rejected');
ApiTestHelpers::assertTrue(file_exists($nestedMoveSource), 'Nested source file unchanged after rejected move');
@unlink($nestedMoveSource);
echo "OK\n";

// Test 12: Reject GET method (move is POST-only)
echo "  - Reject GET method... ";
$response = ApiTestHelpers::get('/api/move/', [
    'path'            => 'move-test-src',
    'name'            => 'file.txt',
    'destinationPath' => 'move-test-dest',
]);
ApiTestHelpers::assertError($response, 405, 'GET method rejected on move endpoint');
echo "OK\n";

// Cleanup test directories
@rmdir($testDir1);
@rmdir($testDir2);

echo "All move endpoint tests passed!\n";
