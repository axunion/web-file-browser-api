<?php

declare(strict_types=1);

define('TESTING_MODE', true);

require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/web-file-browser-api/bootstrap.php';

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
