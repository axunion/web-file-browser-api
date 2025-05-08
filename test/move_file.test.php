<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/web-file-browser-api/move_file.php';

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

// ---------- moveFile Tests ----------

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
$newPath = moveFile($file1, $destDir);
assertEquals(
    $realDestDir . '/test1.txt',
    $newPath,
    'moveFile: normal move'
);
assertEquals(false, file_exists($file1), 'moveFile: original removed');
assertEquals(true, file_exists($newPath), 'moveFile: moved file exists');

// 2. Sequential naming collision
// Create another source file with same name
$file2 = $realSourceDir . '/test1.txt';
file_put_contents($file2, 'world');
$newPath2 = moveFile($file2, $destDir);
assertEquals(
    $realDestDir . '/test1_1.txt',
    $newPath2,
    'moveFile: sequential naming'
);
assertEquals(false, file_exists($file2), 'moveFile: original2 removed');
assertEquals(true, file_exists($newPath2), 'moveFile: moved file2 exists');

// 3. Non-existent source file
assertException(
    function () use ($realSourceDir, $destDir) {
        moveFile($realSourceDir . '/no_file.txt', $destDir);
    },
    'moveFile: non-existent source'
);

// 4. Invalid destination directory
assertException(
    function () use ($newPath) {
        moveFile($newPath, $newPath . '/no_dir');
    },
    'moveFile: invalid destination'
);

// 5. Unwritable destination
chmod($destDir, 0444);
$file3 = $realSourceDir . '/test3.txt';
file_put_contents($file3, 'data');
assertException(
    function () use ($file3, $destDir) {
        moveFile($file3, $destDir);
    },
    'moveFile: unwritable destination'
);
chmod($destDir, 0755);

// 6. Invalid filename (invalid chars)
$invalidName = 'bad:name?.txt';
$file4 = $realSourceDir . '/' . $invalidName;
file_put_contents($file4, 'x');
assertException(
    function () use ($file4, $destDir) {
        moveFile($file4, $destDir);
    },
    'moveFile: invalid filename'
);


// Cleanup temporary dirs
rrmdir($realSourceDir);
rrmdir($realDestDir);

echo "All tests passed.\n";
