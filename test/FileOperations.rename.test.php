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

// Test Case 1: File Renaming
// Create initial file
$orig = $realDir . '/old.txt';
file_put_contents($orig, 'data');

// 1.1. Valid file rename (with trailing slash in directory)
$newPath1 = FileOperations::rename($dir . '/', 'old.txt', 'new.txt');
assertEquals(
    $realDir . '/new.txt',
    $newPath1,
    'FileOperations::rename: return absolute path for renamed file'
);
assertEquals(false, file_exists($orig), 'FileOperations::rename: original file removed');
assertEquals(true, file_exists($realDir . '/new.txt'), 'FileOperations::rename: renamed file exists');

// 1.2. Valid file rename (no trailing slash)
$newPath2 = FileOperations::rename($realDir, 'new.txt', 'new2.txt');
assertEquals(
    $realDir . '/new2.txt',
    $newPath2,
    'FileOperations::rename: return absolute path for file without trailing slash'
);
assertEquals(true, file_exists($realDir . '/new2.txt'), 'FileOperations::rename: renamed file exists 2');

// Test Case 2: Directory Renaming
// Create test directory structure
$testDir = $realDir . '/olddir';
mkdir($testDir);
file_put_contents($testDir . '/content.txt', 'test data');

// 2.1. Valid directory rename
$newDirPath = FileOperations::rename($realDir, 'olddir', 'newdir');
assertEquals(
    $realDir . '/newdir',
    $newDirPath,
    'FileOperations::rename: return absolute path for renamed directory'
);
assertEquals(false, is_dir($testDir), 'FileOperations::rename: original directory removed');
assertEquals(true, is_dir($realDir . '/newdir'), 'FileOperations::rename: renamed directory exists');
assertEquals(
    true,
    file_exists($realDir . '/newdir/content.txt'),
    'FileOperations::rename: directory contents preserved'
);

// Test Case 3: Error Scenarios
// 3.1. Non-existent source
assertException(
    fn() => FileOperations::rename($realDir, 'doesnot.txt', 'x.txt'),
    'FileOperations::rename: non-existent source'
);

// 3.2. Invalid new name
assertException(
    fn() => FileOperations::rename($realDir, 'new2.txt', 'bad:name?.txt'),
    'FileOperations::rename: invalid new name'
);

// 3.3. Target already exists (file)
file_put_contents($realDir . '/existing.txt', 'existing');
assertException(
    fn() => FileOperations::rename($realDir, 'new2.txt', 'existing.txt'),
    'FileOperations::rename: target file already exists'
);

// 3.4. Target already exists (directory)
mkdir($realDir . '/existing_dir');
assertException(
    fn() => FileOperations::rename($realDir, 'newdir', 'existing_dir'),
    'FileOperations::rename: target directory already exists'
);

// Cleanup temporary directory
rrmdir($realDir);

echo "All FileOperations::rename tests passed.\n";
