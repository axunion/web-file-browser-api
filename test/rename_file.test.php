<?php

require_once __DIR__ . '/../src/rename_file.php';

/**
 * Helper function to create a temporary directory for testing.
 *
 * @return string The path to the temporary directory.
 */
function create_temp_directory(): string
{
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rename_test_' . uniqid();
    mkdir($tempDir);
    return $tempDir;
}

/**
 * Test the rename_file function.
 */
function test_rename_file()
{
    $testDir = create_temp_directory();

    try {
        // Test Case 1: Successfully rename a file
        $originalFile = $testDir . DIRECTORY_SEPARATOR . 'test.txt';
        $renamedFile = 'renamed.txt';
        file_put_contents($originalFile, 'Test content'); // Create a test file

        try {
            $newName = rename_file($testDir, 'test.txt', $renamedFile);
            assert($newName === $renamedFile, 'Test Case 1 Failed: Renamed file name does not match.');
            assert(file_exists($testDir . DIRECTORY_SEPARATOR . $renamedFile), 'Test Case 1 Failed: Renamed file does not exist.');
            echo "Test Case 1 Passed: File renamed successfully.\n";
        } catch (RuntimeException $e) {
            echo "Test Case 1 Failed: " . $e->getMessage() . "\n";
        }

        // Test Case 2: File does not exist
        try {
            rename_file($testDir, 'non_existent.txt', 'new_name.txt');
            echo "Test Case 2 Failed: Exception not thrown for non-existent file.\n";
        } catch (RuntimeException $e) {
            assert(strpos($e->getMessage(), "does not exist") !== false, 'Test Case 2 Failed: Incorrect exception message.');
            echo "Test Case 2 Passed: Correct exception thrown for non-existent file.\n";
        }

        // Test Case 3: Invalid new file name
        try {
            rename_file($testDir, $renamedFile, 'invalid:name.txt');
            echo "Test Case 3 Failed: Exception not thrown for invalid file name.\n";
        } catch (RuntimeException $e) {
            assert(strpos($e->getMessage(), "contains invalid characters") !== false, 'Test Case 3 Failed: Incorrect exception message.');
            echo "Test Case 3 Passed: Correct exception thrown for invalid file name.\n";
        }

        // Clean up the renamed file
        unlink($testDir . DIRECTORY_SEPARATOR . $renamedFile);
    } finally {
        // Clean up: Remove the temporary directory
        array_map('unlink', glob($testDir . DIRECTORY_SEPARATOR . '*'));
        rmdir($testDir);
    }
}

test_rename_file();
