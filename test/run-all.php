<?php

declare(strict_types=1);

/**
 * Test Runner - Execute all test files in the test directory
 *
 * This script automatically discovers and runs all *.test.php files,
 * providing a simple, framework-free test execution system.
 *
 * Usage: php test/run-all.php
 */

// ANSI color codes for better output readability
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_YELLOW = "\033[33m";
const COLOR_RESET = "\033[0m";

/**
 * Find all test files in the test directory.
 *
 * @return array<string> List of test file paths
 */
function discoverTestFiles(): array
{
    $testDir = __DIR__;
    $files = scandir($testDir);
    $testFiles = [];

    foreach ($files as $file) {
        // Match files ending with .test.php
        if (preg_match('/\.test\.php$/', $file)) {
            $testFiles[] = $testDir . DIRECTORY_SEPARATOR . $file;
        }
    }

    sort($testFiles);
    return $testFiles;
}

/**
 * Execute a single test file.
 *
 * @param string $testFile Path to the test file
 * @return bool True if test passed, false otherwise
 */
function executeTestFile(string $testFile): bool
{
    $basename = basename($testFile);

    echo "\n";
    echo str_repeat('-', 60) . "\n";
    echo "Running: " . COLOR_YELLOW . $basename . COLOR_RESET . "\n";
    echo str_repeat('-', 60) . "\n";

    // Execute the test file as a separate PHP process
    $command = 'php ' . escapeshellarg($testFile) . ' 2>&1';
    $output = [];
    $exitCode = 0;

    exec($command, $output, $exitCode);

    // Display output
    foreach ($output as $line) {
        echo $line . "\n";
    }

    // Return success status
    return $exitCode === 0;
}

/**
 * Display test summary.
 *
 * @param int $passed Number of tests passed
 * @param int $failed Number of tests failed
 * @return void
 */
function displaySummary(int $passed, int $failed): void
{
    $total = $passed + $failed;

    echo "\n";
    echo str_repeat('=', 60) . "\n";
    echo "Test Summary\n";
    echo str_repeat('=', 60) . "\n";
    echo "Total:  $total\n";
    echo "Passed: " . COLOR_GREEN . $passed . COLOR_RESET . "\n";
    echo "Failed: " . COLOR_RED . $failed . COLOR_RESET . "\n";
    echo "\n";

    if ($failed === 0) {
        echo COLOR_GREEN . "✓ All tests passed!" . COLOR_RESET . "\n";
    } else {
        echo COLOR_RED . "✗ Some tests failed." . COLOR_RESET . "\n";
    }

    echo "\n";
}

// Main execution
function main(): int
{
    echo str_repeat('=', 60) . "\n";
    echo "Web File Browser API - Test Suite\n";
    echo str_repeat('=', 60) . "\n";

    // Discover test files
    $testFiles = discoverTestFiles();

    if (empty($testFiles)) {
        echo COLOR_RED . "No test files found." . COLOR_RESET . "\n";
        return 1;
    }

    echo "Found " . count($testFiles) . " test file(s)\n";

    // Execute each test
    $passed = 0;
    $failed = 0;

    foreach ($testFiles as $testFile) {
        if (executeTestFile($testFile)) {
            $passed++;
        } else {
            $failed++;
        }
    }

    // Display summary
    displaySummary($passed, $failed);

    // Return appropriate exit code
    return $failed === 0 ? 0 : 1;
}

// Run the test suite
exit(main());
