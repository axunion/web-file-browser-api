<?php

declare(strict_types=1);

define('TESTING_MODE', true);

require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/bootstrap.php';

// ---------- UploadValidator Tests ----------

// Setup temporary directory
$uploadDir = sys_get_temp_dir() . '/upload_test_' . uniqid();
mkdir($uploadDir, 0777, true);
$realUploadDir = realpath($uploadDir);

$validator = new UploadValidator(
    allowedMimeTypes: ['image/jpeg', 'image/png'],
    maxFileSize: 1024 * 1024 // 1MB
);

// Test 1: checkUploadError - no file uploaded
assertException(
    fn() => $validator->validate([
        'name'     => 'test.jpg',
        'type'     => 'image/jpeg',
        'tmp_name' => '/tmp/mock',
        'error'    => UPLOAD_ERR_NO_FILE,
        'size'     => 0,
    ]),
    'UploadValidator: UPLOAD_ERR_NO_FILE detected',
    ValidationException::class
);

// Test 2: checkUploadError - partial upload
assertException(
    fn() => $validator->validate([
        'name'     => 'test.jpg',
        'type'     => 'image/jpeg',
        'tmp_name' => '/tmp/mock',
        'error'    => UPLOAD_ERR_PARTIAL,
        'size'     => 0,
    ]),
    'UploadValidator: UPLOAD_ERR_PARTIAL detected',
    ValidationException::class
);

// Test 3: validateBatch - too many files
$validatorBatch = new UploadValidator(
    allowedMimeTypes: ['image/jpeg'],
    maxFileSize: 1024 * 1024
);

assertException(
    fn() => $validatorBatch->validateBatch(
        ['name' => array_fill(0, 15, 'file.jpg'), 'size' => array_fill(0, 15, 500 * 1024)],
        maxFiles: 10,
        maxTotalSize: 30 * 1024 * 1024
    ),
    'UploadValidator: too many files',
    ValidationException::class
);

// Test 4: validateBatch - total size exceeds limit
assertException(
    fn() => $validatorBatch->validateBatch(
        ['name' => ['file1.jpg', 'file2.jpg'], 'size' => [20 * 1024 * 1024, 20 * 1024 * 1024]],
        maxFiles: 10,
        maxTotalSize: 30 * 1024 * 1024
    ),
    'UploadValidator: total size exceeds limit',
    ValidationException::class
);

// Test 5: validateBatch - empty files array
assertException(
    fn() => $validatorBatch->validateBatch(
        ['name' => [], 'size' => []],
        maxFiles: 10,
        maxTotalSize: 30 * 1024 * 1024
    ),
    'UploadValidator: no files uploaded',
    ValidationException::class
);

// Test 6: normalizeBatchFiles - scalar payload becomes one-item batch
$normalized = UploadValidator::normalizeBatchFiles([
    'name' => 'single.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => '/tmp/single',
    'error' => UPLOAD_ERR_OK,
    'size' => 123,
]);
assertEquals(['single.jpg'], $normalized['name'], 'UploadValidator: scalar batch name normalized');
assertEquals([123], $normalized['size'], 'UploadValidator: scalar batch size normalized');

// Test 7: normalizeBatchFiles - inconsistent payload rejected
assertException(
    fn() => UploadValidator::normalizeBatchFiles([
        'name' => ['a.jpg'],
        'type' => ['image/jpeg', 'image/png'],
        'tmp_name' => ['/tmp/a'],
        'error' => [UPLOAD_ERR_OK],
        'size' => [10],
    ]),
    'UploadValidator: inconsistent batch payload rejected',
    ValidationException::class
);

// Note: checkIsUploaded, checkFileSize, and checkMimeType in validate() require
// a real HTTP-uploaded file (is_uploaded_file check). These paths are covered
// by the API integration tests in test-api/upload.test.php and
// test-api/upload-images.test.php.

echo "PASS: UploadValidator constructor accepts parameters\n";
echo "All UploadValidator tests passed.\n";

// Cleanup
rrmdir($realUploadDir);
