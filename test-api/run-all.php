<?php

declare(strict_types=1);

/**
 * API Test Runner
 *
 * Starts PHP built-in server and runs all API tests.
 * Usage: php test-api/run-all.php
 *
 * For Docker environments:
 * docker run --rm -it -v $PWD:/app -w /app php:8.4-apache php test-api/run-all.php
 */

// Indicate that server is managed by this runner
// MUST be set before requiring TestSetup.php
putenv('TEST_SERVER_MANAGED=1');

require_once __DIR__ . '/TestSetup.php';

const TEST_DIR = __DIR__;

// Start server
try {
    $serverPid = startTestServer();
} catch (Throwable $e) {
    echo colorOutput("Error: " . $e->getMessage() . "\n", 'red');
    exit(1);
}

// Find all test files
$testFiles = glob(TEST_DIR . '/*.test.php');
if (empty($testFiles)) {
    echo colorOutput("No test files found\n", 'yellow');
    stopTestServer($serverPid);
    exit(0);
}

// Run tests
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

echo colorOutput("Running API tests...\n\n", 'blue');
echo str_repeat('=', 60) . "\n\n";

foreach ($testFiles as $testFile) {
    $testName = basename($testFile);
    echo colorOutput("Testing: $testName\n", 'blue');

    $output = [];
    $returnCode = 0;
    exec("php " . escapeshellarg($testFile) . " 2>&1", $output, $returnCode);

    $totalTests++;

    if ($returnCode === 0) {
        echo colorOutput("✓ PASSED\n\n", 'green');
        $passedTests++;
    } else {
        echo colorOutput("✗ FAILED\n", 'red');
        echo colorOutput("Output:\n", 'yellow');
        echo implode("\n", $output) . "\n\n";
        $failedTests++;
    }
}

// Shutdown server
echo str_repeat('=', 60) . "\n";
echo colorOutput("Shutting down server...\n", 'blue');
stopTestServer($serverPid);

// Summary
echo "\n";
echo colorOutput("Test Summary:\n", 'blue');
echo "Total:  $totalTests\n";
echo colorOutput("Passed: $passedTests\n", 'green');

if ($failedTests > 0) {
    echo colorOutput("Failed: $failedTests\n", 'red');
    exit(1);
} else {
    echo colorOutput("All tests passed!\n", 'green');
    exit(0);
}
