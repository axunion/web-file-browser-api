<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/web-file-browser-api/filepath_utils.php';

/**
 * Simple assertion for equality.
 */
function assertEquals($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        echo "FAIL: $message - Expected '" . $expected . "', got '" . $actual . "'\n";
        exit(1);
    }
    echo "PASS: $message\n";
}

/**
 * Assert that the callable throws a RuntimeException.
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
 * Recursively delete a directory and its contents.
 */
function rrmdir(string $dirPath): void
{
    if (!is_dir($dirPath)) {
        return;
    }
    $items = scandir($dirPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dirPath . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dirPath);
}

// ---------- resolveSafePath Tests ----------

// Setup temporary base directory
$base = sys_get_temp_dir() . '/base_' . uniqid();
mkdir($base . '/subdir', 0777, true);
file_put_contents($base . '/subdir/file.txt', 'content');

// 0a. Empty userPath returns base
$result0 = resolveSafePath($base, '');
assertEquals(
    realpath($base),
    $result0,
    'resolveSafePath: empty userPath returns base'
);

// 0b. "." returns base
$resultDot = resolveSafePath($base, '.');
assertEquals(
    realpath($base),
    $resultDot,
    'resolveSafePath: "." returns base'
);

// 0c. "./" returns base
$resultDotSlash = resolveSafePath($base, './');
assertEquals(
    realpath($base),
    $resultDotSlash,
    'resolveSafePath: "./" returns base'
);

// 1. Valid path
$result = resolveSafePath($base, 'subdir/file.txt');
assertEquals(
    realpath($base . '/subdir') . '/file.txt',
    $result,
    'resolveSafePath: valid relative path'
);

// 2. Escape outside base
assertException(function () use ($base) {
    resolveSafePath($base, '../etc/passwd');
}, 'resolveSafePath: escape base');

// 3. Invalid base directory
assertException(function () {
    resolveSafePath('/no/such/dir', 'file');
}, 'resolveSafePath: invalid base');

// ---------- validateFileName Tests ----------

// 4. Valid filename
validateFileName('hello.txt');
echo "PASS: validateFileName: valid name\n";

// 5. Empty filename
assertException(function () {
    validateFileName('');
}, 'validateFileName: empty');

// 6. Too long filename
$longName = str_repeat('a', 256);
assertException(function () use ($longName) {
    validateFileName($longName);
}, 'validateFileName: too long');

// 7. Invalid characters
assertException(function () {
    validateFileName('bad:name?.txt');
}, 'validateFileName: invalid chars');

// 8. Reserved name on Windows
assertException(function () {
    validateFileName('CON');
}, 'validateFileName: reserved name');

// 9. Trailing dot or space
assertException(function () {
    validateFileName('name.');
}, 'validateFileName: trailing dot');
assertException(function () {
    validateFileName('name ');
}, 'validateFileName: trailing space');

// ---------- constructSequentialFilePath Tests ----------

// Setup temporary directory for sequential tests
$seqDir = sys_get_temp_dir() . '/seq_' . uniqid();
mkdir($seqDir, 0777, true);

// Resolve real path of seqDir for accurate comparisons
$realSeqDir = realpath($seqDir);

// 10. Initial candidate
$path1 = constructSequentialFilePath($seqDir, 'file.txt');
assertEquals(
    $realSeqDir . '/file.txt',
    $path1,
    'constructSequentialFilePath: initial file'
);

// Create the first file and test next candidate
file_put_contents($realSeqDir . '/file.txt', 'a');
$path2 = constructSequentialFilePath($seqDir, 'file.txt');
assertEquals(
    $realSeqDir . '/file_1.txt',
    $path2,
    'constructSequentialFilePath: second file'
);

// Create the second file and test next candidate
file_put_contents($realSeqDir . '/file_1.txt', 'b');
$path3 = constructSequentialFilePath($seqDir, 'file.txt');
assertEquals(
    $realSeqDir . '/file_2.txt',
    $path3,
    'constructSequentialFilePath: third file'
);

// 11. Unwritable directory
chmod($seqDir, 0444);
assertException(function () use ($seqDir) {
    constructSequentialFilePath($seqDir, 'x.txt');
}, 'constructSequentialFilePath: unwritable');
// Restore permissions to allow cleanup (optional)
chmod($seqDir, 0755);

rrmdir($base);
rrmdir($realSeqDir);

echo "All tests passed. Temporary directories cleaned up.\n";
