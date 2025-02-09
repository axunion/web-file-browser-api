<?php

require_once __DIR__ . '/../src/web-file-browser-api/move_file.php';

function test_move_file(): void
{
    $test_dir = __DIR__ . '/test_env';
    $destination_dir = $test_dir . '/trash';
    $test_file = $test_dir . '/test_file.txt';

    // Setup: Create test environment
    if (!is_dir($test_dir)) {
        mkdir($test_dir, 0777, true);
    }

    if (!is_dir($destination_dir)) {
        mkdir($destination_dir, 0777, true);
    }

    file_put_contents($test_file, 'Test content');

    try {
        // Test: Normal file move
        move_file($test_file, $destination_dir);

        if (!file_exists($destination_dir . '/test_file.txt')) {
            throw new Exception('Test failed: File was not moved file.');
        }

        echo "Test passed: File successfully moved file.\n";

        // Test: Moving non-existent file
        try {
            move_file($test_file, $destination_dir);
            throw new Exception('Test failed: Exception not thrown for non-existent file.');
        } catch (RuntimeException $e) {
            echo "Test passed: Exception thrown for non-existent file.\n";
        }

        // Test: Destination directory is not writable
        $readonly_dir = $destination_dir . '/readonly';
        mkdir($readonly_dir, 0777, true);
        chmod($readonly_dir, 0555); // Make read-only

        $readonly_file = $test_dir . '/readonly_file.txt';
        file_put_contents($readonly_file, 'Readonly test');

        try {
            move_file($readonly_file, $readonly_dir);
            throw new Exception('Test failed: Exception not thrown for non-writable destination directory.');
        } catch (RuntimeException $e) {
            echo "Test passed: Exception thrown for non-writable destination directory.\n";
        }
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
    } finally {
        // Cleanup
        chmod($readonly_dir, 0777);
        array_map('unlink', glob($readonly_dir . '/*') ?: []);
        rmdir($readonly_dir);

        array_map('unlink', glob($destination_dir . '/*') ?: []);
        rmdir($destination_dir);

        array_map('unlink', glob($test_dir . '/*') ?: []);
        rmdir($test_dir);
    }
}

// Run the test
test_move_file();
