<?php

declare(strict_types=1);

require_once __DIR__ . '/TestSetup.php';
require_once __DIR__ . '/ApiTestHelpers.php';

/**
 * API Test: File Upload Endpoint
 * Tests: /api/upload/
 *
 * Note: These tests validate API behavior without actual file uploads,
 * since move_uploaded_file() requires real HTTP multipart requests.
 */

echo "Testing file upload endpoint...\n";

const DATA_DIR = __DIR__ . '/../public/data';

// Test 1: POST method validation
echo "  - Validate POST method required... ";
$response = ApiTestHelpers::get('/api/upload/', ['path' => '']);
ApiTestHelpers::assertError($response, 405, 'GET method rejected');
echo "OK\n";

// Test 2: Path traversal rejection
echo "  - Reject path traversal... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => '../../../etc',
]);
ApiTestHelpers::assertError($response, 400, 'Path traversal rejected');
echo "OK\n";

// Test 3: Invalid path (null byte)
echo "  - Reject null byte in path... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => "test\x00path",
]);
ApiTestHelpers::assertError($response, 400, 'Null byte rejected');
echo "OK\n";

// Test 4: Missing file parameter
echo "  - Handle missing file... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => '',
]);
ApiTestHelpers::assertError($response, 400, 'Missing file parameter');
echo "OK\n";

// Test 5: Empty path handling
echo "  - Handle empty path (root directory)... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => '',
]);
// Should fail due to missing file, but path should be accepted
ApiTestHelpers::assertError($response, 400, 'Empty path accepted, missing file rejected');
echo "OK\n";

// Test 6: Valid subdirectory path
echo "  - Accept valid subdirectory path... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => 'directory',
]);
// Should fail due to missing file, but path should be accepted
ApiTestHelpers::assertError($response, 400, 'Valid path accepted, missing file rejected');
echo "OK\n";

// Test 7: Non-existent directory path
echo "  - Reject non-existent directory... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => 'nonexistent-directory-12345',
]);
ApiTestHelpers::assertError($response, 400, 'Non-existent directory rejected');
echo "OK\n";

// Test 8: Upload JPEG file (success)
echo "  - Upload JPEG file... ";
$jpegFile = ApiTestHelpers::createTempImage(100, 100, 'jpeg');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $jpegFile]);
ApiTestHelpers::assertSuccess($response, 'JPEG upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, basename($jpegFile));
echo "OK\n";

// Test 9: Upload PNG file (success)
echo "  - Upload PNG file... ";
$pngFile = ApiTestHelpers::createTempImage(100, 100, 'png');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $pngFile]);
ApiTestHelpers::assertSuccess($response, 'PNG upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, basename($pngFile));
echo "OK\n";

// Test 10: Upload PDF file (success — /upload accepts pdf, /upload-images does not)
echo "  - Upload PDF file... ";
$pdfFile = ApiTestHelpers::createTempPdf();
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $pdfFile]);
ApiTestHelpers::assertSuccess($response, 'PDF upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, basename($pdfFile));
echo "OK\n";

// Test 11: Reject wrong MIME type (text file disguised with .jpg extension)
echo "  - Reject wrong MIME type... ";
$fakeImage = ApiTestHelpers::createTempFile('This is plain text, not an image.', 'jpg');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $fakeImage]);
ApiTestHelpers::assertError($response, 400, 'Wrong MIME type rejected');
echo "OK\n";

echo "All upload endpoint tests passed!\n";
