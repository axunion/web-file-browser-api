<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/web-file-browser-api/image_utils.php';

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

// Setup temporary directory for tests
$tmpDir = sys_get_temp_dir() . '/img_utils_test_' . uniqid();
mkdir($tmpDir, 0777, true);

try {
    // 1. Small JPEG (no resizing should occur)
    $src1 = $tmpDir . '/small.jpg';
    $img1 = imagecreatetruecolor(100, 50);
    imagejpeg($img1, $src1, 100);
    imagedestroy($img1);

    $dst1 = $tmpDir . '/small_out.jpg';
    compressResizeImage($src1, $dst1, 200, 200, 90, 6);
    list($w1, $h1) = getimagesize($dst1);
    assertEquals(100, $w1, 'JPEG small width unchanged');
    assertEquals(50,  $h1, 'JPEG small height unchanged');
    assertEquals(true, file_exists($dst1), 'JPEG small output exists');

    // 2. Large JPEG (should resize within 1920x1080)
    $src2 = $tmpDir . '/large.jpg';
    $img2 = imagecreatetruecolor(4000, 3000);
    imagejpeg($img2, $src2, 100);
    imagedestroy($img2);

    $dst2 = $tmpDir . '/large_out.jpg';
    compressResizeImage($src2, $dst2, 1920, 1080, 85, 6);
    list($w2, $h2) = getimagesize($dst2);
    assertEquals(true, $w2 <= 1920, 'JPEG large width <= 1920');
    assertEquals(true, $h2 <= 1080, 'JPEG large height <= 1080');

    // 3. Small PNG (no resizing)
    $src3 = $tmpDir . '/small.png';
    $img3 = imagecreatetruecolor(80, 60);
    imagepng($img3, $src3, 0);
    imagedestroy($img3);

    $dst3 = $tmpDir . '/small_out.png';
    compressResizeImage($src3, $dst3, 200, 200, 90, 6);
    list($w3, $h3) = getimagesize($dst3);
    assertEquals(80, $w3, 'PNG small width unchanged');
    assertEquals(60, $h3, 'PNG small height unchanged');

    // 4. Unsupported file type should throw
    $txt = $tmpDir . '/notimg.txt';
    file_put_contents($txt, 'not an image');
    assertException(
        fn() => compressResizeImage($txt, $tmpDir . '/out.txt'),
        'compressResizeImage: invalid image input'
    );

    echo "All tests passed.\n";
} finally {
    // Cleanup helper
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        @$todo($fileinfo->getRealPath());
    }
    @rmdir($tmpDir);
}
