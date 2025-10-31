<?php

declare(strict_types=1);

/**
 * Test Setup and Server Management
 *
 * This file provides server management for both individual test files
 * and the test runner (run-all.php).
 *
 * - Individual tests: Automatically starts/stops server
 * - run-all.php: Sets TEST_SERVER_MANAGED=1 to manage server itself
 */

const SERVER_HOST = '127.0.0.1';
const SERVER_PORT = 8000;
const PUBLIC_DIR = __DIR__ . '/../public';

/**
 * Start PHP built-in server
 *
 * @return int Server process ID
 */
function startTestServer(): int
{
    echo "Starting PHP built-in server...\n";

    $serverCmd = sprintf(
        'TESTING=true php -S %s:%d -t %s > /dev/null 2>&1 & echo $!',
        SERVER_HOST,
        SERVER_PORT,
        escapeshellarg(PUBLIC_DIR)
    );

    $serverPid = (int) shell_exec($serverCmd);
    if ($serverPid === 0) {
        throw new RuntimeException('Failed to start server');
    }

    echo "Server started (PID: $serverPid)\n";

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
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result !== false && in_array($httpCode, [200, 301, 404], true)) {
            $serverReady = true;
        }
        curl_close($ch);
    }

    if (!$serverReady) {
        stopTestServer($serverPid);
        throw new RuntimeException('Server failed to start within timeout');
    }

    echo "Server is ready!\n\n";

    return $serverPid;
}

/**
 * Stop test server
 *
 * @param int $pid Server process ID
 */
function stopTestServer(int $pid): void
{
    if (function_exists('posix_kill')) {
        posix_kill($pid, 15); // SIGTERM
    } else {
        shell_exec("kill $pid 2>/dev/null");
    }
}

/**
 * Color output helper
 *
 * @param string $text Text to colorize
 * @param string $color Color name
 * @return string Colored text
 */
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

// Auto-start server for individual test files
// run-all.php sets TEST_SERVER_MANAGED=1 to disable this
if (getenv('TEST_SERVER_MANAGED') !== '1') {
    try {
        $GLOBALS['__TEST_SERVER_PID'] = startTestServer();

        register_shutdown_function(function () {
            if (isset($GLOBALS['__TEST_SERVER_PID'])) {
                echo "\nShutting down server...\n";
                stopTestServer($GLOBALS['__TEST_SERVER_PID']);
            }
        });
    } catch (Throwable $e) {
        echo colorOutput("Error: " . $e->getMessage() . "\n", 'red');
        exit(1);
    }
}
