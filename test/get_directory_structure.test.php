<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/web-file-browser-api/get_directory_structure.php';

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
 * Assert that a callable throws a specific exception.
 */
function assertException(callable $fn, string $message = ''): void
{
    try {
        $fn();
        echo "FAIL: $message - No exception thrown\n";
        exit(1);
    } catch (Exception $e) {
        echo "PASS: $message - Caught exception: " . get_class($e) . " (" . $e->getMessage() . ")\n";
    }
}


/**
 * Cleanup function.
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


// ---------- DirectoryItem constructor tests ----------

// Valid FILE item
$itemFile = new DirectoryItem(ItemType::FILE, 'example.txt', 123);
assertEquals(ItemType::FILE, $itemFile->type, 'DirectoryItem: file type');
assertEquals('example.txt', $itemFile->name, 'DirectoryItem: file name');
assertEquals(123, $itemFile->size, 'DirectoryItem: file size');

// Valid DIRECTORY item
$itemDir = new DirectoryItem(ItemType::DIRECTORY, 'folder', null);
assertEquals(ItemType::DIRECTORY, $itemDir->type, 'DirectoryItem: directory type');
assertEquals('folder', $itemDir->name, 'DirectoryItem: directory name');
assertEquals(null, $itemDir->size, 'DirectoryItem: directory size');

// FILE without size should throw
assertException(
    fn() => new DirectoryItem(ItemType::FILE, 'no_size.txt', null),
    'DirectoryItem: file missing size'
);

// DIRECTORY with size should throw
assertException(
    fn() => new DirectoryItem(ItemType::DIRECTORY, 'bad_dir', 10),
    'DirectoryItem: directory with size'
);

// ---------- getDirectoryStructure tests ----------

// Invalid path
assertException(
    fn() => getDirectoryStructure('/no/such/path'),
    'getDirectoryStructure: invalid path'
);

// Setup temporary directory structure
$base = sys_get_temp_dir() . '/dir_test_' . uniqid();
mkdir($base, 0777, true);
mkdir($base . '/subdir', 0777, true);
file_put_contents($base . '/file1.txt', 'data1');
file_put_contents($base . '/file2.log', 'data2');

// Execute
$items = getDirectoryStructure($base);

// Expect 3 items: one directory, then two files sorted by name
assertEquals(3, count($items), 'getDirectoryStructure: item count');

// Directory first
assertEquals(ItemType::DIRECTORY, $items[0]->type, 'First item is directory');
assertEquals('subdir', $items[0]->name, 'Directory name');
assertEquals(null, $items[0]->size, 'Directory size is null');

// file1.txt next
assertEquals(ItemType::FILE, $items[1]->type, 'Second item is file');
assertEquals('file1.txt', $items[1]->name, 'First file name');
assertEquals(filesize($base . '/file1.txt'), $items[1]->size, 'First file size');

// file2.log last
assertEquals(ItemType::FILE, $items[2]->type, 'Third item is file');
assertEquals('file2.log', $items[2]->name, 'Second file name');
assertEquals(filesize($base . '/file2.log'), $items[2]->size, 'Second file size');

rrmdir($base);

echo "All tests passed.\n";
