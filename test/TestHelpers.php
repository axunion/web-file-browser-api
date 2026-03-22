<?php

declare(strict_types=1);

/**
 * Common test helper functions.
 *
 * This file provides shared assertion and utility functions for all test files,
 * eliminating code duplication across the test suite.
 */

/**
 * Assert that two values are equal.
 *
 * @param mixed  $expected Expected value
 * @param mixed  $actual   Actual value
 * @param string $message  Optional description of the test
 * @return void
 */
function assertEquals($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        echo "FAIL: $message - Expected '" . var_export($expected, true) . "', got '" . var_export($actual, true) . "'\n";
        exit(1);
    }
    echo "PASS: $message\n";
}

/**
 * Assert that a callable throws an exception of the expected type.
 *
 * @param callable $fn              The function to test
 * @param string   $message         Optional description of the test
 * @param string   $expectedClass   Fully-qualified exception class name to require (optional)
 * @return void
 */
function assertException(callable $fn, string $message = '', string $expectedClass = ''): void
{
    try {
        $fn();
        echo "FAIL: $message - No exception thrown\n";
        exit(1);
    } catch (Throwable $e) {
        if ($expectedClass !== '' && !($e instanceof $expectedClass)) {
            echo "FAIL: $message - Expected $expectedClass, got " . get_class($e) . ": {$e->getMessage()}\n";
            exit(1);
        }
        $label = $expectedClass !== '' ? $expectedClass : get_class($e);
        echo "PASS: $message - Caught $label: {$e->getMessage()}\n";
    }
}

/**
 * Recursively delete a directory and its contents.
 *
 * @param string $dir Directory path to remove
 * @return void
 */
function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            rrmdir($path);
        } else {
            @unlink($path);
        }
    }

    @rmdir($dir);
}
