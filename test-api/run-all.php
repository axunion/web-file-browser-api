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

const TEST_DIR = __DIR__;
const PUBLIC_DIR = __DIR__ . '/../public';
const SERVER_HOST = '127.0.0.1';
const SERVER_PORT = 8000;

// Color output helpers
function colorOutput(string $text, string $color): string
{
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m",
    ];
    return $colors[$color] . $text . $colors['reset'];
}

// Start PHP built-in server
echo colorOutput("Starting PHP built-in server...\n", 'blue');

// Set environment variable to disable HTTPS redirect during testing
putenv('TESTING=true');

$serverCmd = sprintf(
    'TESTING=true php -S %s:%d -t %s > /dev/null 2>&1 & echo $!',
    SERVER_HOST,
    SERVER_PORT,
    escapeshellarg(PUBLIC_DIR)
);

$serverPid = (int) shell_exec($serverCmd);
if ($serverPid === 0) {
    echo colorOutput("Failed to start server\n", 'red');
    exit(1);
}

echo colorOutput("Server started (PID: $serverPid)\n", 'green');

// Wait for server to be ready
echo "Waiting for server to be ready...\n";
$maxAttempts = 20;
$attempt = 0;
$serverReady = false;

while ($attempt < $maxAttempts && !$serverReady) {
    $attempt++;
    usleep(500000); // 500ms

    $ch = curl_init('http://' . SERVER_HOST . ':' . SERVER_PORT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Accept 200, 301, or 404 as "server is ready"
    if ($result !== false && in_array($httpCode, [200, 301, 404], true)) {
        $serverReady = true;
    }
    curl_close($ch);
}

if (!$serverReady) {
    echo colorOutput("Server failed to start within timeout\n", 'red');
    if (function_exists('posix_kill')) {
        posix_kill($serverPid, 15); // SIGTERM = 15
    } else {
        shell_exec("kill $serverPid 2>/dev/null");
    }
    exit(1);
}

echo colorOutput("Server is ready!\n\n", 'green');

// Find all test files
$testFiles = glob(TEST_DIR . '/*.test.php');
if (empty($testFiles)) {
    echo colorOutput("No test files found\n", 'yellow');
    if (function_exists('posix_kill')) {
        posix_kill($serverPid, 15); // SIGTERM = 15
    } else {
        shell_exec("kill $serverPid 2>/dev/null");
    }
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
if (function_exists('posix_kill')) {
    posix_kill($serverPid, 15); // SIGTERM = 15
} else {
    shell_exec("kill $serverPid 2>/dev/null");
}

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
