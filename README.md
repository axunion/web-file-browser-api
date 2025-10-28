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

## Project Structure

```
web-file-browser-api/
├── public/
│   ├── data/                        # User files directory
│   ├── trash/                       # Deleted files directory
│   └── web-file-browser-api/        # API endpoints
│       ├── list/index.php           # Directory listing
│       ├── upload/index.php         # Single file upload
│       ├── rename/index.php         # File renaming
│       └── upload-images/index.php  # Batch image upload
├── src/web-file-browser-api/        # Core classes
│   ├── RequestHandler.php           # Base class for endpoints
│   ├── PathSecurity.php             # Path validation and security
│   ├── DirectoryScanner.php         # Directory operations
│   ├── FileOperations.php           # File manipulation
│   ├── UploadValidator.php          # Upload validation
│   └── Exceptions.php               # Custom exceptions
├── test/                            # Unit tests
│   ├── run-all.php                  # Test runner
│   └── *.test.php                   # Test files
└── test-api/                        # API integration tests
    ├── run-all.php                  # API test runner
    ├── ApiTestHelpers.php           # HTTP request helpers
    └── *.test.php                   # API endpoint tests
```

## API Endpoints

### List Directory Contents

```bash
POST /web-file-browser-api/list/
Content-Type: application/x-www-form-urlencoded

path=subdirectory
```

**Response:**
```json
{
  "status": "success",
  "data": [
    {"name": "example.txt", "type": "file"},
    {"name": "folder", "type": "directory"}
  ]
}
```

### Upload File

```bash
POST /web-file-browser-api/upload/
Content-Type: multipart/form-data

path=destination/path
file=@example.pdf
```

**Supported types:** JPEG, PNG, PDF (max 100MB)

### Rename File

```bash
POST /web-file-browser-api/rename/
Content-Type: application/x-www-form-urlencoded

path=old-name.txt
newName=new-name.txt
```

### Batch Upload Images

```bash
POST /web-file-browser-api/upload-images/
Content-Type: multipart/form-data

path=destination/path
files[]=@image1.jpg
files[]=@image2.png
```

**Limits:** Max 10 files, 30MB total, JPEG/PNG only

## Development

### Running Tests

#### Unit Tests

Test core classes and security-critical functions:

```bash
# Run all unit tests
php test/run-all.php

# Run specific test
php test/PathSecurity.test.php
```

#### API Integration Tests

Test actual HTTP endpoints in a Docker environment:

```bash
# Using Docker alias (recommended)
php test-api/run-all.php

# Or using shell script
./test-api.sh

# Run individual API test
php test-api/list.test.php
```

**Note**: API tests require Docker and automatically start/stop a PHP built-in server within the container. They test actual HTTP requests and responses, validating:
- Request/response format
- Security validations (path traversal, invalid inputs)
- Error handling
- File operations

### Adding New Endpoints

Create a new endpoint by extending `RequestHandler`:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/RequestHandler.php';

final class MyHandler extends RequestHandler
{
    protected array $allowedMethods = ['POST'];
    
    protected function process(): void
    {
        $input = $this->getInput(INPUT_POST, 'param', '');
        $path = $this->resolvePath($input);
        
        // Your logic here
        
        $this->sendSuccess(['result' => 'data']);
    }
}

(new MyHandler())->handle();
```

### Architecture Principles

- **Security First**: All inputs validated, paths sandboxed, defense in depth
- **Separation of Concerns**: Single responsibility per class
- **Type Safety**: Strict types, explicit return declarations
- **Fail Fast**: Early validation, explicit error handling

See `.github/copilot-instructions.md` for detailed development guidelines.

## Security Features

- **Path Traversal Prevention**: All paths resolved and validated before use
- **Filename Validation**: Character restrictions, length limits, reserved name checks
- **MIME Type Verification**: Content-based validation, not extension-based
- **Sequential Naming**: Automatic conflict resolution prevents overwrites
- **Upload Validation**: Size limits, file count limits, proper PHP upload handling

## Testing

The project uses a two-tier testing approach:

### Unit Tests (`test/`)

Framework-free tests for core classes:

- **Assertion-based tests**: Clear pass/fail criteria
- **Security-focused**: Tests for edge cases and attack vectors
- **Directly executable**: Run with PHP CLI, no setup required
- **Automatic discovery**: Test runner finds all `*.test.php` files

Tests cover:
- `PathSecurity`: Path resolution, traversal prevention, validation
- `DirectoryScanner`: Directory listing, sorting
- `FileOperations`: Rename, move operations
- `UploadValidator`: File validation, MIME type checking

### API Integration Tests (`test-api/`)

End-to-end tests for HTTP endpoints in Docker:

- **Real HTTP requests**: Uses cURL to test actual API responses
- **Automated server**: Test runner starts/stops PHP built-in server
- **Docker-compatible**: Runs in containerized environment
- **Security validation**: Tests path traversal, invalid inputs, error handling

Tests cover:
- Directory listing endpoint
- File rename endpoint
- Single file upload endpoint
- Batch image upload endpoint

**Docker Environment Support:**
- HTTPS redirect disabled during tests via `TESTING=true` environment variable
- Uses `127.0.0.1` for reliable container networking
- Requires `php:8.4-apache` Docker image

## Deployment

1. Upload files to your web server
2. Ensure `public/data/` and `public/trash/` are writable
3. Run unit tests on the server: `php test/run-all.php`
4. (Optional) Run API tests in Docker: `php test-api/run-all.php`
5. Configure web server to point to `public/` directory
6. Test endpoints via HTTP requests
