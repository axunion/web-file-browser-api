<?php

require_once __DIR__ . '/../src/web-file-browser-api/save_uploaded_file.php';

/**
 * Test construct_sequential_file_path function.
 */
function test_construct_sequential_file_path()
{
    echo "Running test_construct_sequential_file_path...\n";

    $testDirectory = sys_get_temp_dir();
    $testFilename = 'test_file';
    $testExtension = 'txt';
    $firstFilePath = "{$testDirectory}/{$testFilename}.{$testExtension}";
    $secondFilePath = "{$testDirectory}/{$testFilename}_1.{$testExtension}";
    $thirdFilePath = "{$testDirectory}/{$testFilename}_2.{$testExtension}";

    // Create a dummy file for testing
    file_put_contents($firstFilePath, 'Dummy content');

    // Run the function and check if the first duplicate gets a "_1" suffix
    $generatedPath1 = construct_sequential_file_path($testDirectory, $testFilename, $testExtension);

    if ($generatedPath1 === $secondFilePath) {
        echo "  Passed: First duplicate correctly named '{$secondFilePath}'.\n";
    } else {
        echo "  Failed: Expected '{$secondFilePath}', got '{$generatedPath1}'.\n";
    }

    // Create another dummy file with "_1" suffix
    file_put_contents($secondFilePath, 'Dummy content');

    // Run the function again and check if the next duplicate gets a "_2" suffix
    $generatedPath2 = construct_sequential_file_path($testDirectory, $testFilename, $testExtension);

    if ($generatedPath2 === $thirdFilePath) {
        echo "  Passed: Second duplicate correctly named '{$thirdFilePath}'.\n";
    } else {
        echo "  Failed: Expected '{$thirdFilePath}', got '{$generatedPath2}'.\n";
    }

    // Clean up test files
    unlink($firstFilePath);
    unlink($secondFilePath);
}

test_construct_sequential_file_path();
