<?php

declare(strict_types=1);

require_once __DIR__ . '/PathSecurity.php';

/**
 * Validates uploaded files (MIME type, size, etc.).
 */
final class UploadValidator
{
    private const UPLOAD_ERROR_MESSAGES = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    ];

    public function __construct(
        private readonly array $allowedMimeTypes,
        private readonly int $maxFileSize
    ) {}

    /**
     * Validate a single uploaded file.
     *
     * @param array $file File array from $_FILES
     * @return void
     * @throws RuntimeException If validation fails
     */
    public function validate(array $file): void
    {
        $this->checkUploadError($file['error']);
        $this->checkIsUploaded($file['tmp_name']);
        PathSecurity::validateFileName($file['name']);
        $this->checkFileSize($file['size']);
        $this->checkMimeType($file['tmp_name']);
    }

    /**
     * Validate batch upload constraints.
     *
     * @param array $files      Files array from $_FILES
     * @param int   $maxFiles   Maximum number of files allowed
     * @param int   $maxTotalSize Maximum total size allowed
     * @return void
     * @throws RuntimeException If validation fails
     */
    public function validateBatch(array $files, int $maxFiles, int $maxTotalSize): void
    {
        $count = count($files['name']);

        if ($count === 0) {
            throw new RuntimeException('No files uploaded.');
        }

        if ($count > $maxFiles) {
            throw new RuntimeException("Too many files. Maximum is {$maxFiles}.");
        }

        $totalSize = array_sum($files['size']);

        if ($totalSize > $maxTotalSize) {
            $limitMB = $maxTotalSize / (1024 * 1024);
            throw new RuntimeException("Total upload size exceeds {$limitMB} MB.");
        }
    }

    /**
     * Upload a validated file to target directory with sequential naming.
     *
     * @param string $targetDir Target directory path
     * @param array  $file      File array from $_FILES
     * @return string           Final file path after upload
     * @throws RuntimeException If upload fails
     */
    public function uploadFile(string $targetDir, array $file): string
    {
        if (!is_dir($targetDir) || !is_writable($targetDir)) {
            throw new RuntimeException('Target path is not a writable directory.');
        }

        $destPath = PathSecurity::constructSequentialFilePath($targetDir, $file['name']);

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $err = error_get_last()['message'] ?? 'Unknown error';
            throw new RuntimeException("Failed to move uploaded file: {$err}");
        }

        return $destPath;
    }

    private function checkUploadError(int $errorCode): void
    {
        if ($errorCode !== UPLOAD_ERR_OK) {
            $message = self::UPLOAD_ERROR_MESSAGES[$errorCode] ?? 'Unknown upload error.';
            throw new RuntimeException($message);
        }
    }

    private function checkIsUploaded(string $tmpName): void
    {
        if (!is_uploaded_file($tmpName)) {
            throw new RuntimeException('Invalid uploaded file.');
        }
    }

    private function checkFileSize(int $size): void
    {
        if ($size > $this->maxFileSize) {
            $limitMB = $this->maxFileSize / (1024 * 1024);
            throw new RuntimeException("File exceeds {$limitMB}MB limit.");
        }
    }

    private function checkMimeType(string $tmpName): void
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);

        if (!in_array($mime, $this->allowedMimeTypes, true)) {
            throw new RuntimeException('File type not allowed.');
        }
    }
}
