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
 * Assert that a callable throws a RuntimeException.
 *
 * @param callable $fn      The function to test
 * @param string   $message Optional description of the test
 * @return void
 */
function assertException(callable $fn, string $message = ''): void
{
    try {
        $fn();
        echo "FAIL: $message - No exception thrown\n";
        exit(1);
    } catch (RuntimeException $e) {
        echo "PASS: $message - Caught exception: {$e->getMessage()}\n";
    } catch (Exception $e) {
        echo "PASS: $message - Caught exception: " . get_class($e) . " (" . $e->getMessage() . ")\n";
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
