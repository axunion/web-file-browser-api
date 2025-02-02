<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/web-file-browser-api/validate_file_name.php';

/**
 * Runs all test cases and prints the results.
 */
function run_tests(): void
{
    echo "Running tests...\n";

    // Valid case: The function should not throw an exception
    assert_no_exception("valid_filename.txt");

    // Invalid case: Empty filename
    assert_exception("", "The file name '' cannot be empty.");

    // Invalid case: Filename exceeding 255 characters
    $long_name = str_repeat("a", 256);
    assert_exception($long_name, "The file name '{$long_name}' exceeds the maximum allowed length of 255 characters.");

    // Invalid cases: Filenames containing prohibited characters
    $invalid_names = [
        "file<name.txt" => "The file name 'file<name.txt' contains invalid characters.",
        "file>name.txt" => "The file name 'file>name.txt' contains invalid characters.",
        "file:name.txt" => "The file name 'file:name.txt' contains invalid characters.",
        "file/name.txt" => "The file name 'file/name.txt' contains invalid characters.",
        "file\\name.txt" => "The file name 'file\\name.txt' contains invalid characters.",
        "file|name.txt" => "The file name 'file|name.txt' contains invalid characters.",
        "file?name.txt" => "The file name 'file?name.txt' contains invalid characters.",
        "file*name.txt" => "The file name 'file*name.txt' contains invalid characters."
    ];

    foreach ($invalid_names as $name => $expectedMessage) {
        assert_exception($name, $expectedMessage);
    }

    echo "All tests passed!\n";
}

/**
 * Asserts that the function does not throw an exception.
 */
function assert_no_exception(string $file_name): void
{
    try {
        validate_file_name($file_name);
        echo "[PASS] No exception for '{$file_name}'\n";
    } catch (RuntimeException $e) {
        echo "[FAIL] Unexpected exception for '{$file_name}': " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Asserts that the function throws an exception with the expected message.
 */
function assert_exception(string $file_name, string $expected_message): void
{
    try {
        validate_file_name($file_name);
        echo "[FAIL] Expected exception for '{$file_name}', but none occurred.\n";
        exit(1);
    } catch (RuntimeException $e) {
        if ($e->getMessage() === $expected_message) {
            echo "[PASS] Correct exception for '{$file_name}': {$expected_message}\n";
        } else {
            echo "[FAIL] Incorrect exception message for '{$file_name}'.\n";
            echo "  Expected: {$expected_message}\n";
            echo "  Actual  : {$e->getMessage()}\n";
            exit(1);
        }
    }
}

// Run the tests
run_tests();
