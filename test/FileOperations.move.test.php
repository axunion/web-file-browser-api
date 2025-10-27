<?php

declare(strict_types=1);

require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/web-file-browser-api/FileOperations.php';

// ---------- FileOperations::move Tests ----------

// Setup source and destination directories
$sourceDir = sys_get_temp_dir() . '/move_src_' . uniqid();
$destDir   = sys_get_temp_dir() . '/move_dest_' . uniqid();

mkdir($sourceDir, 0777, true);
mkdir($destDir,   0777, true);

// Resolve real paths for accurate comparisons
$realSourceDir = realpath($sourceDir);
$realDestDir   = realpath($destDir);

// Create a test file to move
$file1 = $realSourceDir . '/test1.txt';
file_put_contents($file1, 'hello');

// 1. Normal move
$newPath = FileOperations::move($file1, $destDir);
assertEquals(
    $realDestDir . '/test1.txt',
    $newPath,
    'FileOperations::move: normal move'
);
assertEquals(false, file_exists($file1), 'FileOperations::move: original removed');
assertEquals(true, file_exists($newPath), 'FileOperations::move: moved file exists');

// 2. Sequential naming collision
// Create another source file with same name
$file2 = $realSourceDir . '/test1.txt';
file_put_contents($file2, 'world');
$newPath2 = FileOperations::move($file2, $destDir);
assertEquals(
    $realDestDir . '/test1_1.txt',
    $newPath2,
    'FileOperations::move: sequential naming'
);
assertEquals(false, file_exists($file2), 'FileOperations::move: original2 removed');
assertEquals(true, file_exists($newPath2), 'FileOperations::move: moved file2 exists');

// 3. Non-existent source file
assertException(
    function () use ($realSourceDir, $destDir) {
        FileOperations::move($realSourceDir . '/no_file.txt', $destDir);
    },
    'FileOperations::move: non-existent source'
);

// 4. Invalid destination directory
assertException(
    function () use ($newPath) {
        FileOperations::move($newPath, '/no/such/directory');
    },
    'FileOperations::move: invalid destination'
);

// 5. Invalid filename (invalid chars)
$invalidName = 'bad:name?.txt';
$file4 = $realSourceDir . '/' . $invalidName;
file_put_contents($file4, 'x');
assertException(
    function () use ($file4, $destDir) {
        FileOperations::move($file4, $destDir);
    },
    'FileOperations::move: invalid filename'
);


// Cleanup temporary dirs
rrmdir($realSourceDir);
rrmdir($realDestDir);

echo "All FileOperations::move tests passed.\n";
