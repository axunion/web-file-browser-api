<?php

declare(strict_types=1);

define('TESTING_MODE', true);

require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/bootstrap.php';

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

// 2b. Trailing ".." segments must not resolve to the base's parent
foreach (['..', 'subdir/../..', '../'] as $dotDotPath) {
    assertException(function () use ($base, $dotDotPath) {
        PathSecurity::resolveSafePath($base, $dotDotPath);
    }, "PathSecurity::resolveSafePath: trailing dot-dot ({$dotDotPath})", PathException::class);
}

// 2c. Segments that merely contain dots are not treated as ".."
foreach (['a..b', 'subdir/...'] as $dottedPath) {
    PathSecurity::resolveSafePath($base, $dottedPath);
    echo "PASS: PathSecurity::resolveSafePath: dotted segment allowed ({$dottedPath})\n";
}

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

// 9b. Reserved internal lock filename cannot be used as a user-facing name
assertException(function () {
    PathSecurity::validateFileName('.seq_lock');
}, 'PathSecurity::validateFileName: reserved lock filename', ValidationException::class);

// 9c. Names beginning with a dot are rejected (would create hidden files,
// e.g. an .htaccess inside the web-accessible data directory)
assertException(function () {
    PathSecurity::validateFileName('.htaccess');
}, 'PathSecurity::validateFileName: leading dot (.htaccess)', ValidationException::class);
assertException(function () {
    PathSecurity::validateFileName('.hidden');
}, 'PathSecurity::validateFileName: leading dot (.hidden)', ValidationException::class);

// 9c2. Invalid UTF-8 is rejected (preg_match /u returns false, not 1)
assertException(function () {
    PathSecurity::validateFileName("bad\xFF.jpg");
}, 'PathSecurity::validateFileName: invalid UTF-8', ValidationException::class);

// 9d. Interior dots remain valid
PathSecurity::validateFileName('name.with.dots.txt');
echo "PASS: PathSecurity::validateFileName: interior dots valid\n";

// ---------- PathSecurity::constructSequentialFilePath Tests ----------

// Setup temporary directory for sequential tests
$seqDir = sys_get_temp_dir() . '/seq_' . uniqid();
mkdir($seqDir, 0777, true);

// Resolve real path of seqDir for accurate comparisons
$realSeqDir = realpath($seqDir) ?: $seqDir;

// 10. Initial candidate
$path1 = PathSecurity::constructSequentialFilePath($seqDir, 'file.txt', function (string $path): void {
    file_put_contents($path, 'a');
});
assertEquals(
    $realSeqDir . '/file.txt',
    $path1,
    'PathSecurity::constructSequentialFilePath: initial file'
);

// Create the first file and test next candidate
$path2 = PathSecurity::constructSequentialFilePath($seqDir, 'file.txt', function (string $path): void {
    file_put_contents($path, 'b');
});
assertEquals(
    $realSeqDir . '/file_1.txt',
    $path2,
    'PathSecurity::constructSequentialFilePath: second file'
);

// Create the second file and test next candidate
$path3 = PathSecurity::constructSequentialFilePath($seqDir, 'file.txt', function (string $path): void {
    file_put_contents($path, 'c');
});
assertEquals(
    $realSeqDir . '/file_2.txt',
    $path3,
    'PathSecurity::constructSequentialFilePath: third file'
);

// 11. Invalid directory (non-existent): throws PathException without leaking the path
try {
    PathSecurity::constructSequentialFilePath('/no/such/directory', 'x.txt', function (string $path): void {
    });
    echo "FAIL: constructSequentialFilePath: no exception for invalid directory\n";
    exit(1);
} catch (PathException $e) {
    assertEquals(
        false,
        str_contains($e->getMessage(), '/no/such/directory'),
        'PathSecurity::constructSequentialFilePath: exception message does not leak path'
    );
}

// 12. Extensionless file: README -> README_1 on collision
$readmePath = PathSecurity::constructSequentialFilePath($seqDir, 'README', function (string $path): void {
    file_put_contents($path, 'readme');
});
assertEquals(
    $realSeqDir . '/README',
    $readmePath,
    'PathSecurity::constructSequentialFilePath: extensionless initial file'
);
$readmePath2 = PathSecurity::constructSequentialFilePath($seqDir, 'README', function (string $path): void {
    file_put_contents($path, 'readme 2');
});
assertEquals(
    $realSeqDir . '/README_1',
    $readmePath2,
    'PathSecurity::constructSequentialFilePath: extensionless collision gets _1'
);

// 13. Extension case normalization: File.JPG -> File.jpg
$jpgPath = PathSecurity::constructSequentialFilePath($seqDir, 'Photo.JPG', function (string $path): void {
    file_put_contents($path, 'jpg');
});
assertEquals(
    $realSeqDir . '/Photo.jpg',
    $jpgPath,
    'PathSecurity::constructSequentialFilePath: uppercase extension lowercased'
);

rrmdir($base);
rrmdir($realSeqDir);

echo "All PathSecurity tests passed. Temporary directories cleaned up.\n";
