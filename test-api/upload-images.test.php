<?php

declare(strict_types=1);

require_once __DIR__ . '/ApiTestHelpers.php';

/**
 * API Test: Batch Image Upload Endpoint
 * Tests: /web-file-browser-api/upload-images/
 */

echo "Testing batch image upload endpoint...\n";

const DATA_DIR = __DIR__ . '/../public/data';

// Test 1: Upload multiple images
echo "  - Upload multiple images... ";
$img1 = ApiTestHelpers::createTempImage(100, 100, 'jpeg');
$img2 = ApiTestHelpers::createTempImage(200, 200, 'png');
$img3 = ApiTestHelpers::createTempImage(150, 150, 'jpeg');

$response = ApiTestHelpers::postMultipart(
    '/web-file-browser-api/upload-images/',
    ['path' => ''],
    ['images' => [$img1, $img2, $img3]]
);

ApiTestHelpers::assertSuccess($response, 'Upload multiple images');
ApiTestHelpers::assertArrayHasKey('files', $response['json'], 'Response has files array');
$uploadedFiles = $response['json']['files'];
ApiTestHelpers::assertEquals(3, count($uploadedFiles), '3 files uploaded');

// Verify all files exist
foreach ($uploadedFiles as $file) {
    assert(file_exists(DATA_DIR . '/' . $file), "Uploaded file $file exists");
    unlink(DATA_DIR . '/' . $file); // Cleanup
}

unlink($img1);
unlink($img2);
unlink($img3);
echo "OK\n";

// Test 2: Upload to subdirectory
echo "  - Upload images to subdirectory... ";
$img1 = ApiTestHelpers::createTempImage(100, 100, 'jpeg');
$img2 = ApiTestHelpers::createTempImage(100, 100, 'png');

$response = ApiTestHelpers::postMultipart(
    '/web-file-browser-api/upload-images/',
    ['path' => 'directory'],
    ['images' => [$img1, $img2]]
);

ApiTestHelpers::assertSuccess($response, 'Upload to subdirectory');
$uploadedFiles = $response['json']['files'];
ApiTestHelpers::assertEquals(2, count($uploadedFiles), '2 files uploaded');

// Verify files in subdirectory
foreach ($uploadedFiles as $file) {
    assert(file_exists(DATA_DIR . '/directory/' . $file), "File $file exists in subdirectory");
    unlink(DATA_DIR . '/directory/' . $file); // Cleanup
}

unlink($img1);
unlink($img2);
echo "OK\n";

// Test 3: Single image upload
echo "  - Upload single image... ";
$img1 = ApiTestHelpers::createTempImage(100, 100, 'jpeg');

$response = ApiTestHelpers::postMultipart(
    '/web-file-browser-api/upload-images/',
    ['path' => ''],
    ['images' => [$img1]]
);

ApiTestHelpers::assertSuccess($response, 'Upload single image');
$uploadedFiles = $response['json']['files'];
ApiTestHelpers::assertEquals(1, count($uploadedFiles), '1 file uploaded');
unlink(DATA_DIR . '/' . $uploadedFiles[0]); // Cleanup
unlink($img1);
echo "OK\n";

// Test 4: No files uploaded (error case)
echo "  - Handle missing files... ";
$response = ApiTestHelpers::post('/web-file-browser-api/upload-images/', ['path' => '']);
ApiTestHelpers::assertError($response, 400, 'Missing files rejected');
echo "OK\n";

// Test 5: Invalid file type (non-image)
echo "  - Reject non-image file... ";
$txtFile = ApiTestHelpers::createTempFile('Not an image', 'txt');
$img1 = ApiTestHelpers::createTempImage(100, 100, 'jpeg');

$response = ApiTestHelpers::postMultipart(
    '/web-file-browser-api/upload-images/',
    ['path' => ''],
    ['images' => [$img1, $txtFile]]
);

// Should reject the entire batch if one file is invalid
ApiTestHelpers::assertError($response, 400, 'Non-image file rejected');
unlink($txtFile);
unlink($img1);
echo "OK\n";

// Test 6: Path traversal attempt
echo "  - Reject path traversal... ";
$img1 = ApiTestHelpers::createTempImage(100, 100, 'jpeg');

$response = ApiTestHelpers::postMultipart(
    '/web-file-browser-api/upload-images/',
    ['path' => '../../../tmp'],
    ['images' => [$img1]]
);

ApiTestHelpers::assertError($response, 400, 'Path traversal rejected');
unlink($img1);
echo "OK\n";

// Test 7: Too many files (max 10)
echo "  - Reject too many files... ";
$images = [];
for ($i = 0; $i < 11; $i++) {
    $images[] = ApiTestHelpers::createTempImage(50, 50, 'jpeg');
}

$response = ApiTestHelpers::postMultipart(
    '/web-file-browser-api/upload-images/',
    ['path' => ''],
    ['images' => $images]
);

ApiTestHelpers::assertError($response, 400, 'Too many files rejected');

// Cleanup temp files
foreach ($images as $img) {
    unlink($img);
}
echo "OK\n";

// Test 8: Filename conflicts (sequential naming)
echo "  - Handle filename conflicts... ";
$img1 = ApiTestHelpers::createTempImage(100, 100, 'jpeg');
$img2 = ApiTestHelpers::createTempImage(100, 100, 'jpeg');

// First upload
$response1 = ApiTestHelpers::postMultipart(
    '/web-file-browser-api/upload-images/',
    ['path' => ''],
    ['images' => [$img1]]
);
ApiTestHelpers::assertSuccess($response1);
$file1 = $response1['json']['files'][0];

// Second upload with same filename
$response2 = ApiTestHelpers::postMultipart(
    '/web-file-browser-api/upload-images/',
    ['path' => ''],
    ['images' => [$img2]]
);
ApiTestHelpers::assertSuccess($response2);
$file2 = $response2['json']['files'][0];

// Files should have different names
assert($file1 !== $file2, 'Conflicting filenames are made unique');

// Cleanup
unlink($img1);
unlink($img2);
unlink(DATA_DIR . '/' . $file1);
unlink(DATA_DIR . '/' . $file2);
echo "OK\n";

// Test 9: Mixed JPEG and PNG
echo "  - Handle mixed image formats... ";
$jpeg1 = ApiTestHelpers::createTempImage(100, 100, 'jpeg');
$png1 = ApiTestHelpers::createTempImage(100, 100, 'png');
$jpeg2 = ApiTestHelpers::createTempImage(100, 100, 'jpeg');

$response = ApiTestHelpers::postMultipart(
    '/web-file-browser-api/upload-images/',
    ['path' => ''],
    ['images' => [$jpeg1, $png1, $jpeg2]]
);

ApiTestHelpers::assertSuccess($response, 'Mixed formats uploaded');
$uploadedFiles = $response['json']['files'];
ApiTestHelpers::assertEquals(3, count($uploadedFiles), '3 files uploaded');

// Cleanup
foreach ($uploadedFiles as $file) {
    unlink(DATA_DIR . '/' . $file);
}
unlink($jpeg1);
unlink($png1);
unlink($jpeg2);
echo "OK\n";

echo "All upload-images endpoint tests passed!\n";
