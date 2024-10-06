<?php

require_once __DIR__ . '/../src/get_directory_structure.php';

$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_dir_' . uniqid();
mkdir($tempDir);

function assert_true($condition, $message)
{
    if (!$condition) {
        echo "Assertion failed: $message\n";
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
    global $tempDir;

    $structure = get_directory_structure($tempDir);
    assert_true(empty($structure), "Empty directory test");

    file_put_contents($tempDir . DIRECTORY_SEPARATOR . 'file1.txt', 'content');
    file_put_contents($tempDir . DIRECTORY_SEPARATOR . 'file2.txt', 'content');
    $structure = get_directory_structure($tempDir);
    assert_true(count($structure) == 2, "Directory with files test");
    assert_true($structure[0]['type'] == 'file' && $structure[0]['name'] == 'file1.txt', "File1 check");
    assert_true($structure[1]['type'] == 'file' && $structure[1]['name'] == 'file2.txt', "File2 check");

    mkdir($tempDir . DIRECTORY_SEPARATOR . 'subdir');
    file_put_contents($tempDir . DIRECTORY_SEPARATOR . 'subdir' . DIRECTORY_SEPARATOR . 'file.txt', 'content');
    $structure = get_directory_structure($tempDir);
    assert_true(count($structure) == 3, "Directory with subdirectory test");
    assert_true($structure[2]['type'] == 'directory' && $structure[2]['name'] == 'subdir', "Subdirectory check");
    assert_true(count($structure[2]['contents']) == 1, "Subdirectory content check");

    try {
        get_directory_structure('/path/to/non/existent/directory');
        echo "Test failed: Exception not thrown for non-existent directory\n";
    } catch (Exception $e) {
        echo "Test passed: Exception thrown for non-existent directory\n";
    }

    remove_directory($tempDir);
}


run_tests();
