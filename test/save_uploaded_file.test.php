<?php

require_once __DIR__ . '/../src/save_uploaded_file.php';

/**
 * Test validate_file_extension function.
 */
function test_validate_file_extension()
{
    echo "Running test_validate_file_extension...\n";

    try {
        validate_file_extension('jpg');
        validate_file_extension('png');
        echo "  Passed: Allowed extensions.\n";
    } catch (RuntimeException $e) {
        echo "  Failed: " . $e->getMessage() . "\n";
    }

    try {
        validate_file_extension('exe');
        echo "  Failed: Disallowed extension should throw exception.\n";
    } catch (RuntimeException $e) {
        echo "  Passed: " . $e->getMessage() . "\n";
    }
}

/**
 * Test validate_destination_directory function.
 */
function test_validate_destination_directory()
{
    echo "Running test_validate_destination_directory...\n";

    $validDirectory = sys_get_temp_dir();

    try {
        validate_destination_directory($validDirectory);
        echo "  Passed: Valid directory.\n";
    } catch (RuntimeException $e) {
        echo "  Failed: " . $e->getMessage() . "\n";
    }

    $invalidDirectory = '/path/to/nonexistent/directory';

    try {
        validate_destination_directory($invalidDirectory);
        echo "  Failed: Invalid directory should throw exception.\n";
    } catch (RuntimeException $e) {
        echo "  Passed: " . $e->getMessage() . "\n";
    }

    $nonWritableDirectory = sys_get_temp_dir() . '/non_writable_test';
    mkdir($nonWritableDirectory, 0444);

    try {
        validate_destination_directory($nonWritableDirectory);
        echo "  Failed: Non-writable directory should throw exception.\n";
    } catch (RuntimeException $e) {
        echo "  Passed: " . $e->getMessage() . "\n";
    } finally {
        chmod($nonWritableDirectory, 0755);
        rmdir($nonWritableDirectory);
    }
}

/**
 * Test construct_unique_file_path function.
 */
function test_construct_unique_file_path()
{
    echo "Running test_construct_unique_file_path...\n";

    $testDirectory = sys_get_temp_dir();
    $testFilename = 'test_file.txt';
    $testExtension = 'txt';
    $existingFile = $testDirectory . DIRECTORY_SEPARATOR . $testFilename;
    $uniquePath = construct_unique_file_path($testDirectory, $testFilename, $testExtension);

    file_put_contents($existingFile, 'Dummy content');

    if (!file_exists($uniquePath)) {
        echo "  Passed: Unique file path generated successfully.\n";
    } else {
        echo "  Failed: Generated file path already exists.\n";
    }

    unlink($existingFile);
}

test_validate_file_extension();
test_validate_destination_directory();
test_construct_unique_file_path();
