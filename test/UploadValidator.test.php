<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/web-file-browser-api/UploadValidator.php';

/**
 * Assert that two values are equal.
 */
function assertEquals($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        echo "FAIL: $message - Expected '" . var_export($expected, true) . "', got '" . var_export($actual, true) . "'\n";
        exit(1);
    }
    echo "PASS: $message\n";
}

/**
 * Assert that a callable throws RuntimeException.
 */
function assertException(callable $fn, string $message = ''): void
{
    try {
        $fn();
        echo "FAIL: $message - No exception thrown\n";
        exit(1);
    } catch (RuntimeException $e) {
        echo "PASS: $message - Caught exception: {$e->getMessage()}\n";
    }
}

/**
 * Cleanup helper.
 */
function rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}

// ---------- UploadValidator Tests ----------

// Setup temporary directory
$uploadDir = sys_get_temp_dir() . '/upload_test_' . uniqid();
mkdir($uploadDir, 0777, true);
$realUploadDir = realpath($uploadDir);

// Test 1: checkFileSize validation
$validator1 = new UploadValidator(
    allowedMimeTypes: ['image/jpeg', 'image/png'],
    maxFileSize: 1024 * 1024 // 1MB
);

// Simulate file array for size check (error must be tested separately)
$oversizedFile = [
    'name' => 'large.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => '/tmp/mock',
    'error' => UPLOAD_ERR_OK,
    'size' => 2 * 1024 * 1024, // 2MB
];

assertException(
    fn() => $validator1->validate($oversizedFile),
    'UploadValidator: file size exceeds limit'
);

// Test 2: validateBatch - too many files
$validator2 = new UploadValidator(
    allowedMimeTypes: ['image/jpeg'],
    maxFileSize: 1024 * 1024
);

$tooManyFiles = [
    'name' => array_fill(0, 15, 'file.jpg'),
    'size' => array_fill(0, 15, 500 * 1024),
];

assertException(
    fn() => $validator2->validateBatch($tooManyFiles, maxFiles: 10, maxTotalSize: 30 * 1024 * 1024),
    'UploadValidator: too many files'
);

// Test 3: validateBatch - total size exceeds limit
$largeBatch = [
    'name' => ['file1.jpg', 'file2.jpg'],
    'size' => [20 * 1024 * 1024, 20 * 1024 * 1024], // 40MB total
];

assertException(
    fn() => $validator2->validateBatch($largeBatch, maxFiles: 10, maxTotalSize: 30 * 1024 * 1024),
    'UploadValidator: total size exceeds limit'
);

// Test 4: validateBatch - empty files array
$emptyFiles = [
    'name' => [],
    'size' => [],
];

assertException(
    fn() => $validator2->validateBatch($emptyFiles, maxFiles: 10, maxTotalSize: 30 * 1024 * 1024),
    'UploadValidator: no files uploaded'
);

// Test 5: checkUploadError
$errorFile = [
    'name' => 'test.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => '/tmp/mock',
    'error' => UPLOAD_ERR_NO_FILE,
    'size' => 0,
];

assertException(
    fn() => $validator1->validate($errorFile),
    'UploadValidator: upload error detected'
);

// Test 6: Invalid filename
$invalidNameFile = [
    'name' => 'bad:name?.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => '/tmp/mock',
    'error' => UPLOAD_ERR_OK,
    'size' => 500 * 1024,
];

assertException(
    fn() => $validator1->validate($invalidNameFile),
    'UploadValidator: invalid filename characters'
);

// Note: We cannot fully test MIME type validation and actual file upload
// without creating real uploaded files, which requires special test setup.
// The tests above cover the business logic that can be tested in isolation.

echo "PASS: UploadValidator constructor accepts parameters\n";
echo "All UploadValidator tests passed.\n";

// Cleanup
rrmdir($realUploadDir);
