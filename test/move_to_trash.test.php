<?php

require_once __DIR__ . '/../src/web-file-browser-api/move_to_trash.php';

function test_move_to_trash(): void
{
    $test_dir = __DIR__ . '/test_env';
    $trash_dir = $test_dir . '/trash';
    $test_file = $test_dir . '/test_file.txt';

    // Setup: Create test environment
    if (!is_dir($test_dir)) {
        mkdir($test_dir, 0777, true);
    }

    if (!is_dir($trash_dir)) {
        mkdir($trash_dir, 0777, true);
    }

    file_put_contents($test_file, 'Test content');

    try {
        // Test: Normal file move
        move_to_trash($test_file, $trash_dir);

        if (!file_exists($trash_dir . '/test_file.txt')) {
            throw new Exception('Test failed: File was not moved to trash.');
        }

        echo "Test passed: File successfully moved to trash.\n";

        // Test: Moving non-existent file
        try {
            move_to_trash($test_file, $trash_dir);
            throw new Exception('Test failed: Exception not thrown for non-existent file.');
        } catch (RuntimeException $e) {
            echo "Test passed: Exception thrown for non-existent file.\n";
        }

        // Test: Trash directory is not writable
        $readonly_trash = $trash_dir . '/readonly';
        mkdir($readonly_trash, 0777, true);
        chmod($readonly_trash, 0555); // Make read-only

        $readonly_file = $test_dir . '/readonly_file.txt';
        file_put_contents($readonly_file, 'Readonly test');

        try {
            move_to_trash($readonly_file, $readonly_trash);
            throw new Exception('Test failed: Exception not thrown for non-writable trash directory.');
        } catch (RuntimeException $e) {
            echo "Test passed: Exception thrown for non-writable trash directory.\n";
        }
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
    } finally {
        // Cleanup
        chmod($readonly_trash, 0777);
        array_map('unlink', glob($readonly_trash . '/*') ?: []);
        rmdir($readonly_trash);

        array_map('unlink', glob($trash_dir . '/*') ?: []);
        rmdir($trash_dir);

        array_map('unlink', glob($test_dir . '/*') ?: []);
        rmdir($test_dir);
    }
}

// Run the test
test_move_to_trash();
