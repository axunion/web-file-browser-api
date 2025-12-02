# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A lightweight, security-first PHP API for file management operations. No external framework dependencies, strict typing throughout, class-based architecture with comprehensive test coverage.

## Commands

### Testing
```bash
# Run all unit tests
php test/run-all.php

# Run all API tests (auto-starts PHP server on port 8000)
php test-api/run-all.php

# Run individual API test (auto-starts server, no manual setup needed)
php test-api/list.test.php
```

No build or lint process - pure PHP.

## Architecture

### Request Flow
```
HTTP Request → Endpoint (public/web-file-browser-api/{endpoint}/index.php)
    ↓
Bootstrap (loads dependencies, discovers data/trash dirs, handles CORS)
    ↓
Validation Layer (PathSecurity, UploadValidator)
    ↓
Business Logic (DirectoryScanner, FileOperations)
    ↓
JSON Response (sendSuccess/sendError helpers)
```

### Key Components

**PathSecurity** (`src/web-file-browser-api/PathSecurity.php`) - Security-critical path validation:
- `resolveSafePath()` - Prevents directory traversal attacks
- `validateFileName()` - Enforces platform-specific filename rules
- `constructSequentialFilePath()` - Prevents overwrites with file locking

**Bootstrap** (`src/web-file-browser-api/bootstrap.php`) - Central orchestrator:
- Auto-discovers data/trash directories by walking up from executing script
- Provides helpers: `resolvePath()`, `resolvePathWithTrash()`, `validateMethod()`, `sendSuccess()`, `sendError()`, `handleError()`
- Maps exceptions to HTTP status: PathException/ValidationException/DirectoryException → 400, others → 500

**Config** (`src/web-file-browser-api/Config.php`) - Upload limits, allowed MIME types, CORS settings

### Endpoint Pattern
All endpoints follow this structure:
```php
require_once __DIR__ . '/../../../src/web-file-browser-api/bootstrap.php';
validateMethod(['POST']);
try {
    $input = getInput(INPUT_POST, 'key', 'default');
    $path = resolvePath($input);  // or resolvePathWithTrash() for trash support
    // Business logic
    sendSuccess(['result' => 'data']);
} catch (Throwable $e) {
    handleError($e);
}
```

### Current Endpoints
- **GET /list** - List directory contents
- **POST /upload** - Single file upload
- **POST /upload-images** - Batch image upload
- **POST /rename** - Rename file/directory
- **POST /delete** - Move to trash

## Conventions

- `declare(strict_types=1)` in every PHP file
- All parameters and return types explicitly typed
- PSR-12 style with 4-space indentation
- Validate inputs early, throw exceptions for invalid states

## Testing

- **Unit tests** (`test/`): Direct class testing with simple assertions
- **API tests** (`test-api/`): HTTP requests via curl, auto-manages server startup/shutdown
- `ApiTestHelpers.php` provides `get()`, `post()`, assertions, file cleanup tracking

## Environment Variables

- `TESTING=true` - Disables HTTPS redirects (set automatically by test runner)
- `TEST_SERVER_MANAGED=1` - Disables auto-server startup (used by run-all.php)
