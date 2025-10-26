<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/web-file-browser-api/FileOperations.php';

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
 * Cleanup helper (recursive).
 */
function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}



// ---------- FileOperations::rename Tests ----------

// Setup temporary directory
$dir = sys_get_temp_dir() . '/rename_test_' . uniqid();
mkdir($dir, 0777, true);
$realDir = realpath($dir);

// Create initial file
$orig = $realDir . '/old.txt';
file_put_contents($orig, 'data');

// 1. Valid rename (with trailing slash in directory)
$newPath1 = FileOperations::rename($dir . '/', 'old.txt', 'new.txt');
assertEquals(
    $realDir . '/new.txt',
    $newPath1,
    'FileOperations::rename: return absolute path for new name'
);
assertEquals(false, file_exists($orig), 'FileOperations::rename: original removed');
assertEquals(true, file_exists($realDir . '/new.txt'), 'FileOperations::rename: moved file exists');

// 2. Valid rename (no trailing slash)
$newPath2 = FileOperations::rename($realDir, 'new.txt', 'new2.txt');
assertEquals(
    $realDir . '/new2.txt',
    $newPath2,
    'FileOperations::rename: return absolute path without trailing slash'
);
assertEquals(true, file_exists($realDir . '/new2.txt'), 'FileOperations::rename: moved file exists 2');

// 3. Non-existent source file
assertException(
    fn() => FileOperations::rename($realDir, 'doesnot.txt', 'x.txt'),
    'FileOperations::rename: non-existent source'
);

// 4. Invalid new name
assertException(
    fn() => FileOperations::rename($realDir, 'new2.txt', 'bad:name?.txt'),
    'FileOperations::rename: invalid new name'
);

// Cleanup temporary directory
rrmdir($realDir);

echo "All FileOperations::rename tests passed.\n";
