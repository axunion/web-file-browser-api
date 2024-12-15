<?php

require_once __DIR__ . '/../src/delete_file.php';

/**
 * Test case: Successfully delete a file.
 */
function test_delete_file_success(): void
{
    echo "Test: Successfully delete a file...\n";

    $test_directory = sys_get_temp_dir();
    $test_file = 'test_file.txt';
    $test_path = $test_directory . DIRECTORY_SEPARATOR . $test_file;

    file_put_contents($test_path, 'Test content');

    try {
        delete_file($test_directory, $test_file);
        assert(!file_exists($test_path), "The file should have been deleted.");
        echo "  Passed!\n";
    } catch (Exception $e) {
        echo "  Failed: " . $e->getMessage() . "\n";
    }
}

/**
 * Test case: Attempt to delete a file that does not exist.
 */
function test_delete_file_nonexistent_file(): void
{
    echo "Test: Attempt to delete a nonexistent file...\n";

    $test_directory = sys_get_temp_dir();
    $test_file = 'nonexistent_file.txt';

    try {
        delete_file($test_directory, $test_file);
        echo "  Failed: Exception was not thrown for a nonexistent file.\n";
    } catch (RuntimeException $e) {
        assert(strpos($e->getMessage(), 'does not exist') !== false, "Expected exception message about nonexistent file.");
        echo "  Passed!\n";
    }
}

/**
 * Test case: Attempt to delete a file in an invalid directory.
 */
function test_delete_file_invalid_directory(): void
{
    echo "Test: Attempt to delete a file in an invalid directory...\n";

    $invalid_directory = '/invalid_directory';
    $test_file = 'test_file.txt';

    try {
        delete_file($invalid_directory, $test_file);
        echo "  Failed: Exception was not thrown for an invalid directory.\n";
    } catch (RuntimeException $e) {
        assert(strpos($e->getMessage(), 'does not exist') !== false, "Expected exception message about invalid directory.");
        echo "  Passed!\n";
    }
}

/**
 * Test case: Attempt to delete a non-writable file.
 */
function test_delete_file_non_writable(): void
{
    echo "Test: Attempt to delete a non-writable file...\n";

    $test_directory = sys_get_temp_dir();
    $test_file = 'non_writable_file.txt';
    $test_path = $test_directory . DIRECTORY_SEPARATOR . $test_file;

    file_put_contents($test_path, 'Test content');
    chmod($test_path, 0444);

    try {
        delete_file($test_directory, $test_file);
        echo "  Failed: Exception was not thrown for a non-writable file.\n";
    } catch (RuntimeException $e) {
        assert(strpos($e->getMessage(), 'not writable') !== false, "Expected exception message about non-writable file.");
        echo "  Passed!\n";
    } finally {
        chmod($test_path, 0666);
        unlink($test_path);
    }
}

test_delete_file_success();
test_delete_file_nonexistent_file();
test_delete_file_invalid_directory();
test_delete_file_non_writable();
