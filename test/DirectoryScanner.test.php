<?php

declare(strict_types=1);

define('TESTING_MODE', true);

require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/bootstrap.php';

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

// Empty directory returns empty array
$emptyDir = sys_get_temp_dir() . '/dir_empty_' . uniqid();
mkdir($emptyDir, 0777, true);
$emptyItems = DirectoryScanner::scan($emptyDir);
assertEquals(0, count($emptyItems), 'DirectoryScanner::scan: empty directory returns empty array');
rrmdir($emptyDir);

// Natural sort order: file2 before file10
$natDir = sys_get_temp_dir() . '/dir_nat_' . uniqid();
mkdir($natDir, 0777, true);
file_put_contents($natDir . '/file10.txt', 'ten');
file_put_contents($natDir . '/file2.txt', 'two');
file_put_contents($natDir . '/file1.txt', 'one');
$natItems = DirectoryScanner::scan($natDir);
assertEquals(3, count($natItems), 'DirectoryScanner::scan: natural sort item count');
assertEquals('file1.txt', $natItems[0]->name, 'Natural sort: file1 first');
assertEquals('file2.txt', $natItems[1]->name, 'Natural sort: file2 second');
assertEquals('file10.txt', $natItems[2]->name, 'Natural sort: file10 last');
rrmdir($natDir);

// Symlinks are skipped
$symlinkBase = sys_get_temp_dir() . '/dir_sym_' . uniqid();
mkdir($symlinkBase, 0777, true);
$realFile = $symlinkBase . '/real.txt';
$symLink  = $symlinkBase . '/link.txt';
file_put_contents($realFile, 'real');
if (symlink($realFile, $symLink)) {
    $symItems = DirectoryScanner::scan($symlinkBase);
    assertEquals(1, count($symItems), 'DirectoryScanner::scan: symlink excluded from results');
    assertEquals('real.txt', $symItems[0]->name, 'Only real file returned, not symlink');
} else {
    echo "PASS: DirectoryScanner::scan: symlink skipping - skipped (symlink() not supported)\n";
}
rrmdir($symlinkBase);

echo "All DirectoryScanner tests passed.\n";
