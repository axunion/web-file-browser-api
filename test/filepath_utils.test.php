<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/web-file-browser-api/filepath_utils.php';

/**
 * 指定したテストケースを実行し、結果を出力する。
 *
 * @param string   $name         テスト名
 * @param callable $testFunction テスト関数
 * @return void
 */
function run_test(string $name, callable $testFunction): void
{
    try {
        $testFunction();
        echo "[PASS] {$name}\n";
    } catch (Exception $e) {
        echo "[FAIL] {$name}: " . $e->getMessage() . "\n";
    }
}

/**
 * 期待する値と実際の値が一致するか検証する。
 *
 * @param mixed  $expected
 * @param mixed  $actual
 * @param string $message  エラーメッセージ（任意）
 * @return void
 * @throws Exception
 */
function assertEqual($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new Exception("Assertion failed: {$message}. Expected " . var_export($expected, true) . ", got " . var_export($actual, true));
    }
}

/**
 * 指定したクロージャが例外をスローするか検証する。
 *
 * @param callable $testFunction
 * @param string|null $expectedMessage 例外メッセージの一部（オプション）
 * @return void
 * @throws Exception
 */
function assertException(callable $testFunction, ?string $expectedMessage = null): void
{
    try {
        $testFunction();
    } catch (Exception $e) {
        if ($expectedMessage !== null && strpos($e->getMessage(), $expectedMessage) === false) {
            throw new Exception("Expected exception message to contain '{$expectedMessage}', got '{$e->getMessage()}'");
        }
        return;
    }
    throw new Exception("Expected exception was not thrown.");
}

/**
 * validate_and_resolve_path: 正常系テスト
 */
function test_validate_and_resolve_path_valid(): void
{
    // 一時ディレクトリを作成し、その中にテスト用ファイルを作成
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_validate_path_' . uniqid();
    mkdir($tempDir, 0777, true);
    $filePath = $tempDir . DIRECTORY_SEPARATOR . 'test.txt';
    file_put_contents($filePath, "Hello world");

    $relativePath = 'test.txt';
    $resolvedPath = validate_and_resolve_path($tempDir, $relativePath);
    $expected = realpath($filePath);
    assertEqual($expected, $resolvedPath, "validate_and_resolve_path should return the correct resolved path");

    // 後始末
    unlink($filePath);
    rmdir($tempDir);
}

/**
 * validate_and_resolve_path: 存在しないファイルを指定した場合
 */
function test_validate_and_resolve_path_nonexistent(): void
{
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_validate_path_' . uniqid();
    mkdir($tempDir, 0777, true);

    assertException(function () use ($tempDir) {
        validate_and_resolve_path($tempDir, 'nonexistent.txt');
    }, 'The specified path does not exist.');

    rmdir($tempDir);
}

/**
 * validate_and_resolve_path: ベースディレクトリ外を指定した場合
 */
function test_validate_and_resolve_path_outside(): void
{
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_validate_path_' . uniqid();
    mkdir($tempDir, 0777, true);

    // 相対パスでディレクトリトラバーサルを試みる
    assertException(function () use ($tempDir) {
        validate_and_resolve_path($tempDir, '../outside.txt');
    }, 'The specified path is outside the base directory.');

    rmdir($tempDir);
}

/**
 * validate_and_resolve_path: 無効なベースディレクトリを指定した場合
 */
function test_validate_and_resolve_path_invalid_base(): void
{
    $nonExistentDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nonexistent_dir_' . uniqid();
    assertException(function () use ($nonExistentDir) {
        validate_and_resolve_path($nonExistentDir, 'somefile.txt');
    }, 'The base directory does not exist or is not a valid directory.');
}

/**
 * validate_file_name: 正常なファイル名
 */
function test_validate_file_name_valid(): void
{
    // エラーが発生しなければOK
    validate_file_name('valid_filename.txt');
}

/**
 * validate_file_name: 空文字列の場合
 */
function test_validate_file_name_empty(): void
{
    assertException(function () {
        validate_file_name('');
    }, 'cannot be empty');
}

/**
 * validate_file_name: 不正な文字が含まれる場合
 */
function test_validate_file_name_invalid_characters(): void
{
    assertException(function () {
        validate_file_name('invalid:name.txt');
    }, 'contains invalid characters');
}

/**
 * validate_file_name: 長すぎるファイル名の場合
 */
function test_validate_file_name_too_long(): void
{
    $longName = str_repeat('a', 256) . '.txt';
    assertException(function () use ($longName) {
        validate_file_name($longName);
    }, 'exceeds the maximum allowed length');
}

/**
 * construct_sequential_file_path: ファイルが存在しない場合
 */
function test_construct_sequential_file_path_first(): void
{
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_seq_path_' . uniqid();
    mkdir($tempDir, 0777, true);
    $filename = 'test.txt';

    $path = construct_sequential_file_path($tempDir, $filename);
    $expectedPath = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'test.txt';
    assertEqual($expectedPath, $path, "First call should return the original filename if it does not exist");

    rmdir($tempDir);
}

/**
 * construct_sequential_file_path: 既にファイルが存在する場合
 */
function test_construct_sequential_file_path_with_existing_files(): void
{
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_seq_path_' . uniqid();
    mkdir($tempDir, 0777, true);
    $filename = 'test.txt';

    // まず元のファイルを作成
    $file1 = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'test.txt';
    file_put_contents($file1, "file1");

    // test.txt が存在するので、最初の候補は test_1.txt となるはず
    $seqPath1 = construct_sequential_file_path($tempDir, $filename);
    $expectedPath1 = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'test_1.txt';
    assertEqual($expectedPath1, $seqPath1, "Sequential file path should append _1 if original exists");

    // 次に test_1.txt も作成しておく
    file_put_contents($seqPath1, "file2");

    // 次は test_2.txt となるはず
    $seqPath2 = construct_sequential_file_path($tempDir, $filename);
    $expectedPath2 = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'test_2.txt';
    assertEqual($expectedPath2, $seqPath2, "Sequential file path should increment the counter");

    // 後始末
    unlink($file1);
    unlink($seqPath1);
    rmdir($tempDir);
}


/**
 * --- テスト実行 ---
 */

run_test("validate_and_resolve_path_valid", 'test_validate_and_resolve_path_valid');
run_test("validate_and_resolve_path_nonexistent", 'test_validate_and_resolve_path_nonexistent');
run_test("validate_and_resolve_path_outside", 'test_validate_and_resolve_path_outside');
run_test("validate_and_resolve_path_invalid_base", 'test_validate_and_resolve_path_invalid_base');

run_test("validate_file_name_valid", 'test_validate_file_name_valid');
run_test("validate_file_name_empty", 'test_validate_file_name_empty');
run_test("validate_file_name_invalid_characters", 'test_validate_file_name_invalid_characters');
run_test("validate_file_name_too_long", 'test_validate_file_name_too_long');

run_test("construct_sequential_file_path_first", 'test_construct_sequential_file_path_first');
run_test("construct_sequential_file_path_with_existing_files", 'test_construct_sequential_file_path_with_existing_files');

echo "\nAll tests completed.\n";
