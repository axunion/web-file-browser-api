# Web File Browser API - AI Coding Instructions

## Architecture Overview

PHP-based REST API for file management with security-first design using a **dual-path architecture**:

- **`public/web-file-browser-api/`** - HTTP endpoints (`list/`, `upload/`, `rename/`, `upload-images/`)
- **`src/web-file-browser-api/`** - Core business logic (utilities, validation, processing)

All operations are sandboxed within `public/data/` (active files) and `public/trash/` (deleted items).

## Code Style (PSR-12)

- PHP 8.1+ with strict types: `declare(strict_types=1);`
- Follow PSR-12 Extended Coding Style Guide
- 4-space indentation (see `.editorconfig`)
- Opening braces on same line: `function name(): void {`
- Multi-line parameters: each on separate line, aligned
- Control structures: space before parentheses `if ($condition) {`
- UTF-8 throughout (filenames, JSON responses)

```php
function validateFileName(string $name): void {
    // Implementation
}
```

## Comments and Messages

- **Code comments**: English only, essential information only (no obvious comments)
- **Error messages**: English, user-facing and consistent across all endpoints
- **Git commits**: **MUST be in English**. Write concise, imperative mood descriptions (e.g., "Add file validation", "Fix path resolution bug"). Never use Japanese or other languages.

## Security Patterns (CRITICAL)

### 1. Path Resolution
**Always** use `resolveSafePath()` to prevent directory traversal:
```php
$target = resolveSafePath($dataDir, $userPath);  // Never use user input directly
```

### 2. File Validation
**Every** file operation requires `validateFileName()`:
```php
validateFileName($fileName);  // Validates length, characters, Windows reserved names
```

### 3. Sequential Naming
Use `constructSequentialFilePath()` to prevent overwrites:
```php
$destPath = constructSequentialFilePath($target, $file['name']);  // auto-numbered if exists
```

## API Response Pattern

All endpoints use consistent JSON structure via `sendJson()`:
```php
function sendJson(array $payload, int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Usage
sendJson(['status' => 'success', 'data' => $result], 200);
sendJson(['status' => 'error', 'message' => $error], 400);
```

## Core Utilities

### Directory Structure (`get_directory_structure.php`)
Returns `DirectoryItem[]` with `ItemType` enum:
- Directories first, then files
- Natural case-insensitive sort (`strnatcasecmp`)
- Skips symlinks for security

### File Operations (`move_file.php`, `rename_file.php`)
Cross-device move support with automatic fallback to copy+unlink.


## File Upload Constraints

- **Standard upload (`upload/`)**: 100MB max, JPEG/PNG/PDF only
- **Image batch upload (`upload-images/`)**: 10 files max, 10MB per file, 30MB total, JPEG/PNG only
- MIME validation via `finfo`, uses `is_uploaded_file()` and `move_uploaded_file()`

## Testing

Simple assertion-based tests without external frameworks:
```php
assertEquals($expected, $actual, 'Test description');
assertException(fn() => riskyOperation(), 'Should throw');
```

Run from project root:
```bash
php test/filepath_utils.test.php
# Or all tests
for test in test/*.test.php; do php "$test"; done
```

## Deployment

GitHub Actions FTP deployment to separate server directories:
- Manual trigger with dry-run option
- Matrix strategy: deploys `src/` and `public/` to different paths
- See `.github/workflows/deploy.yml`

## Cross-Origin Configuration

The `.htaccess` handles CORS, HTTPS redirects, and compression. All endpoints support cross-origin requests with:
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, OPTIONS`
