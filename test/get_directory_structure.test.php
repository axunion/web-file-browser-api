<?php

require_once __DIR__ . '/../src/get_directory_structure.php';

function assert_equal($actual, $expected, $message)
{
    if ($actual !== $expected) {
        echo "Assertion failed: $message\n";
        echo "  Expected: " . var_export($expected, true) . "\n";
        echo "  Actual: " . var_export($actual, true) . "\n";
    } else {
        echo "Test passed: $message\n";
    }
}

function remove_directory($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) {
                    remove_directory($dir . DIRECTORY_SEPARATOR . $object);
                } else {
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        rmdir($dir);
    }
}

function run_tests()
{
    // Test 1: Empty directory
    $tempDir = setup_test_directory();
    $structure = get_directory_structure($tempDir);
    assert_equal($structure, [], "Empty directory test");
    remove_directory($tempDir);

    // Test 2: Directory with files
    $tempDir = setup_test_directory();
    file_put_contents($tempDir . DIRECTORY_SEPARATOR . 'file1.txt', 'content');
    file_put_contents($tempDir . DIRECTORY_SEPARATOR . 'file2.txt', 'content');
    $structure = get_directory_structure($tempDir);
    assert_equal(count($structure), 2, "Directory with files test");
    assert_equal($structure[0]['type'], 'file', "File1 type check");
    assert_equal($structure[0]['name'], 'file1.txt', "File1 name check");
    assert_equal($structure[1]['type'], 'file', "File2 type check");
    assert_equal($structure[1]['name'], 'file2.txt', "File2 name check");
    remove_directory($tempDir);

    // Test 3: Directory with subdirectory
    $tempDir = setup_test_directory();
    mkdir($tempDir . DIRECTORY_SEPARATOR . 'subdir');
    $structure = get_directory_structure($tempDir);
    assert_equal(count($structure), 1, "Directory with one subdirectory test");
    assert_equal($structure[0]['type'], 'directory', "Subdirectory type check");
    assert_equal($structure[0]['name'], 'subdir', "Subdirectory name check");
    remove_directory($tempDir);

    // Test 4: Non-existent directory
    try {
        get_directory_structure('/path/to/non/existent/directory');
        echo "Test failed: Exception not thrown for non-existent directory\n";
    } catch (Exception $e) {
        echo "Test passed: Exception thrown for non-existent directory\n";
    }
}

function setup_test_directory(): string
{
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_dir_' . uniqid();
    mkdir($tempDir);
    return $tempDir;
}

run_tests();
