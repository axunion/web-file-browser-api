<?php

require_once __DIR__ . '/../src/web-file-browser-api/validate_and_resolve_path.php';

/**
 * Run tests for validate_and_resolve_path
 */
function run_tests()
{
    echo "Running tests for validate_and_resolve_path...\n";

    // Prepare test directories
    $base_dir = __DIR__ . '/test_base_dir';
    $sub_dir = $base_dir . '/subdir';
    $unwritable_dir = $base_dir . '/unwritable';

    // Setup the test environment
    setup_test_environment($base_dir, $sub_dir, $unwritable_dir);

    // Test cases
    $test_cases = [
        // Valid relative path to a subdirectory
        [
            'description' => 'Valid relative path to subdir',
            'base_dir' => $base_dir,
            'path' => 'subdir',
            'expected' => $sub_dir,
        ],
        // Valid absolute path to a subdirectory
        [
            'description' => 'Valid absolute path to subdir',
            'base_dir' => $base_dir,
            'path' => $sub_dir,
            'expected_exception' => 'The specified path does not exist.',
        ],
        // Non-existent directory
        [
            'description' => 'Non-existent path',
            'base_dir' => $base_dir,
            'path' => 'nonexistent',
            'expected_exception' => 'The specified path does not exist.',
        ],
        // Unwritable directory
        [
            'description' => 'Unwritable directory',
            'base_dir' => $base_dir,
            'path' => 'unwritable',
            'expected_exception' => 'The specified path is not writable.',
        ],
    ];

    // Execute test cases
    foreach ($test_cases as $case) {
        try {
            $result = validate_and_resolve_path($case['base_dir'], $case['path']);

            if (isset($case['expected'])) {
                assert_equals($case['expected'], $result, $case['description']);
            } else {
                echo "FAIL: {$case['description']} - Expected exception but got result.\n";
            }
        } catch (RuntimeException $e) {
            if (isset($case['expected_exception'])) {
                assert_equals($case['expected_exception'], $e->getMessage(), $case['description']);
            } else {
                echo "FAIL: {$case['description']} - Unexpected exception: {$e->getMessage()}\n";
            }
        }
    }

    // Clean up the test environment
    cleanup_test_environment($base_dir);

    echo "Tests completed.\n";
}

/**
 * Setup the test environment by creating required directories
 */
function setup_test_environment(string $base_dir, string $sub_dir, string $unwritable_dir)
{
    if (!file_exists($base_dir)) {
        mkdir($base_dir, 0777, true);
    }

    if (!file_exists($sub_dir)) {
        mkdir($sub_dir, 0777, true);
    }

    if (!file_exists($unwritable_dir)) {
        mkdir($unwritable_dir, 0555); // Make directory unwritable
    }
}

/**
 * Clean up the test environment by removing all test directories
 */
function cleanup_test_environment(string $base_dir)
{
    if (file_exists($base_dir)) {
        exec("chmod -R 0777 {$base_dir}");
        exec("rm -rf {$base_dir}");
    }
}

/**
 * A simple assertion function to compare expected and actual values
 */
function assert_equals($expected, $actual, string $description)
{
    if ($expected === $actual) {
        echo "PASS: {$description}\n";
    } else {
        echo "FAIL: {$description} - Expected: {$expected}, Got: {$actual}\n";
    }
}

run_tests();
