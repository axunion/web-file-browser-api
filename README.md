# Web File Browser API

A secure, lightweight PHP API for file management operations. Built with security-first principles and class-based architecture, requiring no external frameworks.

## Features

- 📁 **Directory Listing**: Browse directories with type-safe scanning
- 📤 **File Upload**: Single and batch uploads with MIME type validation
- ✏️ **File Rename**: Safe renaming with filename validation
- 🔒 **Security**: Path traversal prevention, input validation, sandboxed operations
- 🧪 **Tested**: Comprehensive test suite for security-critical functions

## Requirements

- PHP 8.1 or higher
- Web server (Apache/Nginx)
- Write permissions for `public/data/` and `public/trash/` directories

## API Endpoints

All endpoints return JSON with `status` field (`success` or `error`).

### List Directory Contents
`GET /web-file-browser-api/list/?path=subdirectory`

Returns: `{"status": "success", "list": [{"name": "file.txt", "type": "file"}, ...]}`

### Upload File
`POST /web-file-browser-api/upload/` (multipart/form-data)

Parameters: `path`, `file` | Supports: JPEG, PNG, PDF (max 100MB)

### Rename File
`POST /web-file-browser-api/rename/`

Parameters: `path` (directory), `name` (current name), `newName`

### Batch Upload Images
`POST /web-file-browser-api/upload-images/` (multipart/form-data)

Parameters: `path`, `images[]` | Limits: 10 files, 30MB total, JPEG/PNG only

## Development

### Running Tests

```bash
# Unit tests (core classes)
php test/run-all.php

# API tests (HTTP endpoints, requires Docker)
php test-api/run-all.php
```

### Adding New Endpoints

Create a new endpoint using the bootstrap file and helper functions:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/bootstrap.php';

validateMethod(['POST']);

try {
    // Get input parameters
    $param = getInput(INPUT_POST, 'param', '');
    
    // Resolve and validate path
    $path = resolvePath($param);
    
    // Your business logic here
    // ...
    
    sendSuccess(['result' => 'data']);
} catch (Throwable $e) {
    handleError($e);
}
```

**Available Helper Functions:**
- `validateMethod(array $methods)` - Validate HTTP method
- `resolvePath(string $path)` - Resolve path within data directory
- `resolvePathWithTrash(string $path)` - Resolve path with trash support
- `getInput(int $type, string $key, mixed $default)` - Get input safely
- `sendSuccess(array $data, int $code)` - Send JSON success response
- `sendError(string $message, int $code)` - Send JSON error response
- `handleError(Throwable $e)` - Handle exceptions uniformly

## Architecture

- **Security First**: Path traversal prevention, input validation, sandboxed operations
- **Simple & Testable**: Hand-written tests, no frameworks, direct execution
- **Type Safe**: Strict types throughout, fail fast on invalid input

See `.github/copilot-instructions.md` for detailed development guidelines.
