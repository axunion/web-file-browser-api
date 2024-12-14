<?php

require_once __DIR__ . '/../src/delete_file.php';

/**
 * Test case: Successfully delete a file.
 */
function testDeleteFileSuccess(): void
{
    $testFilePath = __DIR__ . '/testfile.txt';

    file_put_contents($testFilePath, 'Test content');
    assert(file_exists($testFilePath), 'Test file should exist before deletion.');
    deleteFile($testFilePath);
    assert(!file_exists($testFilePath), 'Test file should be deleted.');
}

/**
 * Test case: File does not exist.
 */
function testDeleteFileFileDoesNotExist(): void
{
    $testFilePath = __DIR__ . '/nonexistentfile.txt';

    assert(!file_exists($testFilePath), 'Test file should not exist.');

    try {
        deleteFile($testFilePath);
        assert(false, 'Expected exception for non-existent file.');
    } catch (RuntimeException $e) {
        assert($e->getMessage() === "File '{$testFilePath}' does not exist.", 'Correct exception message should be thrown.');
    }
}

/**
 * Test case: Path is not a file.
 */
function testDeleteFileNotAFile(): void
{
    $testDirPath = __DIR__ . '/testdir';

    mkdir($testDirPath);
    assert(is_dir($testDirPath), 'Test directory should exist.');

    try {
        deleteFile($testDirPath);
        assert(false, 'Expected exception for non-file path.');
    } catch (RuntimeException $e) {
        assert($e->getMessage() === "The path '{$testDirPath}' is not a file.", 'Correct exception message should be thrown.');
    } finally {
        rmdir($testDirPath);
    }
}

/**
 * Test case: File is not writable.
 */
function testDeleteFileNotWritable(): void
{
    $testFilePath = __DIR__ . '/readonlyfile.txt';

    file_put_contents($testFilePath, 'Test content');
    chmod($testFilePath, 0444);
    assert(file_exists($testFilePath), 'Test file should exist.');
    assert(!is_writable($testFilePath), 'Test file should not be writable.');

    try {
        deleteFile($testFilePath);
        assert(false, 'Expected exception for non-writable file.');
    } catch (RuntimeException $e) {
        assert($e->getMessage() === "File '{$testFilePath}' is not writable.", 'Correct exception message should be thrown.');
    } finally {
        chmod($testFilePath, 0666);
        unlink($testFilePath);
    }
}

testDeleteFileSuccess();
testDeleteFileFileDoesNotExist();
testDeleteFileNotAFile();
testDeleteFileNotWritable();
