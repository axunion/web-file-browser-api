# Web File Browser API - Development Guidelines

## Core Principles

### 1. Security First
- **Never trust user input**: All external data must be validated and sanitized
- **Principle of least privilege**: Operations are sandboxed to specific directories
- **Defense in depth**: Multiple layers of validation (path resolution, filename validation, MIME type checking)
- **Fail securely**: Invalid operations must fail explicitly with clear error messages

### 2. Separation of Concerns
- **Single Responsibility Principle**: Each class handles one aspect of functionality
- **Shared utilities**: Common functionality provided via bootstrap.php helper functions
- **Clear boundaries**: HTTP handling, business logic, and file system operations are separated

### 3. Code Quality Standards
- **Type safety**: Use strict types and explicit return type declarations
- **Fail fast**: Validate inputs early and throw exceptions for invalid states
- **Keep it simple**: Prefer clarity over cleverness
- **Consistent conventions**: Follow PSR-12 coding standards throughout the codebase

## Architecture

### Bootstrap System
All endpoints include `bootstrap.php` which provides:
- Dependency loading (all core classes in correct order)
- Base directory constants (`API_DATA_DIR`, `API_TRASH_DIR`)
- Helper functions (validateMethod, resolvePath, sendSuccess, sendError, handleError)
- CORS handling (when enabled in Config.php)

Note: At startup the bootstrap will search upward from the executing script (a few levels) for a directory containing `data` or `trash` and use that as the web root. When found it defines `API_DATA_DIR` and `API_TRASH_DIR`. Endpoints must only `require_once` the bootstrap and must not redefine those constants. For uncommon layouts, use an environment variable or adjust `Config.php`.

### Endpoint Structure
Endpoints use procedural style for clarity:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/bootstrap.php';

validateMethod(['POST']);

try {
    $input = getInput(INPUT_POST, 'param', '');
    $path = resolvePath($input);
    
    // Business logic here
    
    sendSuccess(['result' => 'data']);
} catch (Throwable $e) {
    handleError($e);
}
```

### Error Handling
- Use custom exceptions: `PathException`, `ValidationException`, `DirectoryException`
- `RuntimeException` for business logic errors
- `handleError()` automatically logs and sends appropriate HTTP status codes
- Never expose internal implementation details in error messages

### File System Operations
- All paths must be resolved through `PathSecurity::resolveSafePath()` before use
- Use atomic operations where possible
- Handle cross-device operations gracefully (copy + delete fallback)
- Clean up resources properly (file handles, temporary files)

## Security Guidelines

### Path Security
- Resolve all user-provided paths to absolute paths within allowed boundaries
- Prevent directory traversal attacks through canonicalization
- Reject suspicious patterns (null bytes, excessive separators)
- Use `resolvePath()` for data directory, `resolvePathWithTrash()` for trash support

### File Validation
- Validate filenames: `PathSecurity::validateFileName()`
  - Length limits (255 chars)
  - Character restrictions (no `<>:"/\|?*` or control chars)
  - Platform-specific reserved names (CON, PRN, etc.)
  - No trailing spaces or dots
- Verify MIME types using file content (`finfo`), not extensions
- Use `PathSecurity::constructSequentialFilePath()` to prevent overwrites

### Upload Security
- Verify files are actually uploaded with `is_uploaded_file()`
- Enforce size limits (configured in `Config.php`)
- Whitelist allowed MIME types
- Move uploaded files atomically with `move_uploaded_file()`

## Configuration

Edit `src/web-file-browser-api/Config.php` for:
- Upload size limits (single/batch)
- Allowed MIME types
- CORS settings
- Batch upload limits (file count, total size)

## Development Practices

### Code Style
- Follow PSR-12 Extended Coding Style Guide
- 4 spaces for PHP indentation (enforced by `.editorconfig`)
- Use meaningful names that express intent
- Keep functions small and focused
- Comments in English, only when code cannot be self-documenting

### Testing
- **Unit tests** (`test/`): Test individual classes in isolation
- **API tests** (`test-api/`): Test HTTP endpoints via actual requests
  - Can be run individually or via `run-all.php`
  - Server is automatically started/stopped by `TestSetup.php`
  - Individual tests: `php test-api/list.test.php` (no manual server setup needed)
  - All tests: `php test-api/run-all.php`
- Write tests for security-critical functions
- Test edge cases and error conditions
- Use simple assertions without heavy frameworks

Run tests:
```bash
php test/run-all.php                  # Unit tests
php test-api/run-all.php              # All API tests
php test-api/upload-images.test.php   # Individual API test
```

### Adding New Endpoints

1. Create directory under `public/web-file-browser-api/`
2. Add `index.php` with bootstrap inclusion
3. Use helper functions for validation and responses
4. Handle all exceptions with `handleError()`
5. Add corresponding API test in `test-api/`

### Adding New Utilities

1. Create class in `src/web-file-browser-api/`
2. Use `final class` and `static` methods for stateless utilities
3. Use type hints for all parameters and return values
4. Throw exceptions for invalid inputs
5. Add unit test in `test/`

### Adding Tests

**Unit tests**:
- Place in `test/` directory, name as `ClassName.test.php`
- Test individual classes and methods
- Clean up temporary files/directories

**API tests**:
- Place in `test-api/` directory, name as `endpoint-name.test.php`
- Include `TestSetup.php` first for automatic server management
- Use `ApiTestHelpers` for HTTP requests
- Test both success and error cases
- Test security validations (path traversal, invalid inputs)
- Clean up uploaded files using `ApiTestHelpers::registerUploadedFile()`

**Example API test structure**:
```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/TestSetup.php';      // Auto-starts server for standalone run
require_once __DIR__ . '/ApiTestHelpers.php';

echo "Testing endpoint...\n";

const DATA_DIR = __DIR__ . '/../public/data';

// Test case
$response = ApiTestHelpers::post('/endpoint/', ['param' => 'value']);
ApiTestHelpers::assertSuccess($response);

// Register uploaded files for cleanup
if (isset($response['json']['files'])) {
    foreach ($response['json']['files'] as $file) {
        ApiTestHelpers::registerUploadedFile(DATA_DIR, $file);
    }
}

echo "All tests passed!\n";
```

**Test cleanup best practices**:
- Uploaded files: Use `ApiTestHelpers::registerUploadedFile()` to auto-cleanup on shutdown
- Temp files: Use `ApiTestHelpers::createTempFile()` which auto-registers cleanup
- Partial uploads on error: Check for new files after error assertions and clean manually

## Git Workflow

- Commits in English, imperative mood (e.g., "Add validation", "Fix bug")
- Keep commits focused and atomic
- Test before committing

## Docker Environment

API tests can run both natively and within Docker containers:
- `TestSetup.php` automatically manages PHP built-in server lifecycle
- HTTPS redirect disabled during tests via `TESTING=true` environment variable
- Use `127.0.0.1` for server networking
