<?php

declare(strict_types=1);

define('TESTING_MODE', true);

require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/web-file-browser-api/bootstrap.php';

// ---------- PathSecurity::resolveSafePath Tests ----------

// Setup temporary base directory
$base = sys_get_temp_dir() . '/base_' . uniqid();
mkdir($base . '/subdir', 0777, true);
file_put_contents($base . '/subdir/file.txt', 'content');

// 0a. Empty userPath returns base
$result0 = PathSecurity::resolveSafePath($base, '');
assertEquals(
    realpath($base),
    $result0,
    'PathSecurity::resolveSafePath: empty userPath returns base'
);

// 0b. "." returns base
$resultDot = PathSecurity::resolveSafePath($base, '.');
assertEquals(
    realpath($base),
    $resultDot,
    'PathSecurity::resolveSafePath: "." returns base'
);

// 0c. "./" returns base
$resultDotSlash = PathSecurity::resolveSafePath($base, './');
assertEquals(
    realpath($base),
    $resultDotSlash,
    'PathSecurity::resolveSafePath: "./" returns base'
);

// 1. Valid path
$result = PathSecurity::resolveSafePath($base, 'subdir/file.txt');
assertEquals(
    realpath($base . '/subdir') . '/file.txt',
    $result,
    'PathSecurity::resolveSafePath: valid relative path'
);

// 2. Escape outside base
assertException(function () use ($base) {
    PathSecurity::resolveSafePath($base, '../etc/passwd');
}, 'PathSecurity::resolveSafePath: escape base');

// 3. Invalid base directory
assertException(function () {
    PathSecurity::resolveSafePath('/no/such/dir', 'file');
}, 'PathSecurity::resolveSafePath: invalid base');

// ---------- PathSecurity::validateFileName Tests ----------

// 4. Valid filename
PathSecurity::validateFileName('hello.txt');
echo "PASS: PathSecurity::validateFileName: valid name\n";

// 5. Empty filename
assertException(function () {
    PathSecurity::validateFileName('');
}, 'PathSecurity::validateFileName: empty');

// 6. Too long filename
$longName = str_repeat('a', 256);
assertException(function () use ($longName) {
    PathSecurity::validateFileName($longName);
}, 'PathSecurity::validateFileName: too long');

// 7. Invalid characters
assertException(function () {
    PathSecurity::validateFileName('bad:name?.txt');
}, 'PathSecurity::validateFileName: invalid chars');

// 8. Reserved name on Windows
assertException(function () {
    PathSecurity::validateFileName('CON');
}, 'PathSecurity::validateFileName: reserved name');

// 9. Trailing dot or space
assertException(function () {
    PathSecurity::validateFileName('name.');
}, 'PathSecurity::validateFileName: trailing dot');
assertException(function () {
    PathSecurity::validateFileName('name ');
}, 'PathSecurity::validateFileName: trailing space');

// ---------- PathSecurity::constructSequentialFilePath Tests ----------

// Setup temporary directory for sequential tests
$seqDir = sys_get_temp_dir() . '/seq_' . uniqid();
mkdir($seqDir, 0777, true);

// Resolve real path of seqDir for accurate comparisons
$realSeqDir = realpath($seqDir);

// 10. Initial candidate
$path1 = PathSecurity::constructSequentialFilePath($seqDir, 'file.txt');
assertEquals(
    $realSeqDir . '/file.txt',
    $path1,
    'PathSecurity::constructSequentialFilePath: initial file'
);

// Create the first file and test next candidate
file_put_contents($realSeqDir . '/file.txt', 'a');
$path2 = PathSecurity::constructSequentialFilePath($seqDir, 'file.txt');
assertEquals(
    $realSeqDir . '/file_1.txt',
    $path2,
    'PathSecurity::constructSequentialFilePath: second file'
);

// Create the second file and test next candidate
file_put_contents($realSeqDir . '/file_1.txt', 'b');
$path3 = PathSecurity::constructSequentialFilePath($seqDir, 'file.txt');
assertEquals(
    $realSeqDir . '/file_2.txt',
    $path3,
    'PathSecurity::constructSequentialFilePath: third file'
);

// 11. Invalid directory (non-existent)
assertException(function () {
    PathSecurity::constructSequentialFilePath('/no/such/directory', 'x.txt');
}, 'PathSecurity::constructSequentialFilePath: invalid directory');

rrmdir($base);
rrmdir($realSeqDir);

echo "All PathSecurity tests passed. Temporary directories cleaned up.\n";
