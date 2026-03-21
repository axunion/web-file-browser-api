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
- **POST /move** - Move file/directory to a different location

## Security Guidelines

- All user-provided paths must go through `resolvePath()` or `resolvePathWithTrash()` (calls `PathSecurity::resolveSafePath()`)
- Validate filenames with `PathSecurity::validateFileName()` before any create/rename operation
- Verify MIME types via `finfo` (content analysis) — never trust file extensions
- Verify uploads with `is_uploaded_file()` before moving
- Never expose internal filesystem paths in responses or error messages

## Error Handling

Exception types and their HTTP status mappings (handled by `handleError()`):
- `PathException` → 400 (invalid/unsafe path)
- `ValidationException` → 400 (invalid input)
- `DirectoryException` → 400 (directory operation failure)
- All other `Throwable` → 500

## Adding New Code

**New endpoint**: Create `public/web-file-browser-api/{name}/index.php` following the Endpoint Pattern above.

**New utility class**: Add to `src/web-file-browser-api/`, use `declare(strict_types=1)`, explicit types throughout.

**New tests**: Unit tests in `test/{ClassName}.test.php`, API tests in `test-api/{endpoint-name}.test.php`. Always test success, error, and path-traversal cases.

**API spec**: Update `openapi.yaml` in the same commit whenever you add or modify an endpoint (parameters, response shape, error conditions, or constraints). Also update `docs/api-usage.md` if the change affects the fetch() patterns, upload limits, or collision-handling behaviour documented there.

## Git Workflow

- Commit messages: English, imperative mood ("Add move endpoint", not "Added" or "Adding")
- Atomic commits: one logical change per commit
- Run tests before committing

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
