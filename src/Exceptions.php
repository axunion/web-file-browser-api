<?php

declare(strict_types=1);

/**
 * Exception for directory-related errors.
 */
class DirectoryException extends RuntimeException {}

/**
 * Exception for path resolution and security errors.
 */
class PathException extends RuntimeException {}

/**
 * Exception for validation errors.
 */
class ValidationException extends RuntimeException {}
