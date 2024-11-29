<?php

require_once __DIR__ . '/../src/get_directory_structure.php';

/**
 * Helper function to assert a condition
 *
 * @param bool $condition The condition to check
 * @param string $message The message to display
 */
function assert_true($condition, $message)
{
    if (!$condition) {
        echo "Assertion failed: $message\n";
    } else {
        echo "Test passed: $message\n";
    }
}

/**
 * Helper function to recursively remove a directory
 *
 * @param string $dir The directory path
 */
function remove_directory($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                $object_path = $dir . DIRECTORY_SEPARATOR . $object;
                if (is_dir($object_path) && !is_link($object_path)) {
                    remove_directory($object_path);
                } else {
                    unlink($object_path);
                }
            }
        }
        rmdir($dir);
    }
}

/**
 * Run the tests for the get_directory_structure function
 */
function run_tests()
{
    $tempDir = setup_test_directory();

    // Test 1: Empty directory
    $structure = get_directory_structure($tempDir);
    assert_true(empty($structure), "Empty directory test");
    remove_directory($tempDir);

    // Test 2: Directory with files and subdirectory
    $tempDir = setup_test_directory();

    // Create test files and a subdirectory
    $file1 = $tempDir . DIRECTORY_SEPARATOR . 'file1.txt';
    $file2 = $tempDir . DIRECTORY_SEPARATOR . 'file2.log';
    $subdir = $tempDir . DIRECTORY_SEPARATOR . 'subdir';

    file_put_contents($file1, 'content for file1');
    file_put_contents($file2, 'content for file2');
    mkdir($subdir);

    $structure = get_directory_structure($tempDir);

    // Assertions
    assert_true(count($structure) === 3, "Directory contains 3 items");

    $expectedItems = [
        ['name' => 'file1.txt', 'size' => filesize($file1)],
        ['name' => 'file2.log', 'size' => filesize($file2)],
        ['name' => 'subdir', 'size' => null],
    ];

    foreach ($expectedItems as $expected) {
        $item = array_filter($structure, fn($i) => $i->name === $expected['name']);
        assert_true(!empty($item), "Item {$expected['name']} exists in structure");
        $item = array_values($item)[0];
        assert_true($item->size === $expected['size'], "Item {$expected['name']} size is correct");
    }

    remove_directory($tempDir);

    // Test 3: Non-existent directory
    try {
        get_directory_structure('/path/to/non/existent/directory');
        echo "Test failed: Exception not thrown for non-existent directory\n";
    } catch (Exception $e) {
        echo "Test passed: Exception thrown for non-existent directory\n";
    }
}

/**
 * Set up a temporary test directory
 *
 * @return string The path to the test directory
 */
function setup_test_directory(): string
{
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_dir_' . uniqid();
    mkdir($tempDir);
    return $tempDir;
}

run_tests();
