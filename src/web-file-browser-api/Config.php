<?php

declare(strict_types=1);

/**
 * Centralized configuration for the web file browser API.
 *
 * All constants related to file uploads, size limits, and allowed MIME types
 * are defined here to ensure consistency across all endpoints.
 */
final class Config
{
    /**
     * Maximum file size for single file uploads (in bytes).
     * Default: 100 MB
     */
    public const SINGLE_FILE_MAX_SIZE = 100 * 1024 * 1024;

    /**
     * Maximum file size for batch image uploads (in bytes).
     * Default: 10 MB per file
     */
    public const BATCH_FILE_MAX_SIZE = 10 * 1024 * 1024;

    /**
     * Maximum number of files allowed in batch uploads.
     * Default: 10 files
     */
    public const BATCH_MAX_FILES = 10;

    /**
     * Maximum total size for batch uploads (in bytes).
     * Default: 30 MB total
     */
    public const BATCH_MAX_TOTAL_SIZE = 30 * 1024 * 1024;

    /**
     * Allowed MIME types for single file uploads.
     * Supports JPEG images, PNG images, and PDF documents.
     */
    public const SINGLE_UPLOAD_ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    /**
     * Allowed MIME types for batch image uploads.
     * Only JPEG and PNG images are supported.
     */
    public const BATCH_UPLOAD_ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
    ];

    /**
     * Enable CORS (Cross-Origin Resource Sharing) headers.
     * Set to true to allow cross-origin requests from web applications.
     */
    public const ENABLE_CORS = false;

    /**
     * Allowed origins for CORS requests.
     * Use '*' to allow all origins (not recommended for production).
     * Use specific domains like 'https://example.com' for better security.
     */
    public const CORS_ALLOWED_ORIGIN = '*';

    /**
     * Allowed HTTP methods for CORS requests.
     */
    public const CORS_ALLOWED_METHODS = 'GET, POST, OPTIONS';

    /**
     * Allowed headers for CORS requests.
     */
    public const CORS_ALLOWED_HEADERS = 'Content-Type, Authorization';

    /**
     * Max age for CORS preflight cache (in seconds).
     */
    public const CORS_MAX_AGE = 3600;

    /**
     * Prevent instantiation of this utility class.
     */
    private function __construct() {}
}
