<?php

declare(strict_types=1);

require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/web-file-browser-api/DirectoryScanner.php';

// ---------- DirectoryItem constructor tests ----------

// Valid FILE item
$itemFile = new DirectoryItem(ItemType::FILE, 'example.txt');
assertEquals(ItemType::FILE, $itemFile->type, 'DirectoryItem: file type');
assertEquals('example.txt', $itemFile->name, 'DirectoryItem: file name');

// Valid DIRECTORY item
$itemDir = new DirectoryItem(ItemType::DIRECTORY, 'folder');
assertEquals(ItemType::DIRECTORY, $itemDir->type, 'DirectoryItem: directory type');
assertEquals('folder', $itemDir->name, 'DirectoryItem: directory name');

// ---------- DirectoryScanner::scan tests ----------

// Invalid path
assertException(
    fn() => DirectoryScanner::scan('/no/such/path'),
    'DirectoryScanner::scan: invalid path'
);

// Setup temporary directory structure
$base = sys_get_temp_dir() . '/dir_test_' . uniqid();
mkdir($base, 0777, true);
mkdir($base . '/subdir', 0777, true);
file_put_contents($base . '/file1.txt', 'data1');
file_put_contents($base . '/file2.log', 'data2');

// Execute
$items = DirectoryScanner::scan($base);

// Expect 3 items: one directory, then two files sorted by name
assertEquals(3, count($items), 'DirectoryScanner::scan: item count');

// Directory first
assertEquals(ItemType::DIRECTORY, $items[0]->type, 'First item is directory');
assertEquals('subdir', $items[0]->name, 'Directory name');

// file1.txt next
assertEquals(ItemType::FILE, $items[1]->type, 'Second item is file');
assertEquals('file1.txt', $items[1]->name, 'First file name');

// file2.log last
assertEquals(ItemType::FILE, $items[2]->type, 'Third item is file');
assertEquals('file2.log', $items[2]->name, 'Second file name');

rrmdir($base);

echo "All DirectoryScanner tests passed.\n";
