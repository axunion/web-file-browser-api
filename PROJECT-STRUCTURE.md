# Project Structure

## 📁 Directory Structure

```
web-file-browser-api/
├── .editorconfig
├── .github/
│   ├── copilot-instructions.md     # Development guidelines and architecture patterns
│   └── workflows/
│       └── deploy.yml
├── .gitignore
├── LICENSE
├── PROJECT-STRUCTURE.md             # This file
├── run-tests.sh                     # Test runner script
│
├── public/
│   ├── data/                        # User files directory
│   │   ├── sample.txt
│   │   └── directory/
│   ├── trash/                       # Deleted files directory
│   └── web-file-browser-api/        # API endpoints
│       ├── list/
│       │   └── index.php
│       ├── upload/
│       │   └── index.php
│       ├── rename/
│       │   └── index.php
│       └── upload-images/
│           └── index.php
│
├── src/
│   └── web-file-browser-api/        # Core classes
│       ├── RequestHandler.php       # Abstract base class for endpoints
│       ├── PathSecurity.php         # Path validation and security
│       ├── DirectoryScanner.php     # Directory listing
│       ├── FileOperations.php       # File move and rename operations
│       ├── UploadValidator.php      # Upload validation and handling
│       └── Exceptions.php           # Custom exception definitions
│
└── test/                            # Unit tests
    ├── PathSecurity.test.php
    ├── DirectoryScanner.test.php
    ├── FileOperations.move.test.php
    ├── FileOperations.rename.test.php
    └── UploadValidator.test.php
```

## 📊 Code Statistics

### Core Classes (`src/web-file-browser-api/`)
| File | Lines | Purpose |
|------|-------|---------|
| `RequestHandler.php` | 105 | Abstract base class for HTTP endpoints |
| `PathSecurity.php` | 140 | Path resolution, validation, sequential naming |
| `DirectoryScanner.php` | 86 | Directory scanning with ItemType enum |
| `FileOperations.php` | 105 | File move and rename operations |
| `UploadValidator.php` | 136 | Upload validation and handling |
| `Exceptions.php` | 17 | Custom exception definitions |
| **Total** | **589** | Core infrastructure |

### API Endpoints (`public/web-file-browser-api/`)
| Endpoint | Lines | Purpose |
|----------|-------|---------|
| `list/index.php` | 31 | List directory contents |
| `upload/index.php` | 35 | Upload single file (JPEG/PNG/PDF, 100MB max) |
| `rename/index.php` | 42 | Rename file with validation |
| `upload-images/index.php` | 64 | Batch upload images (10 files, 30MB max) |
| **Total** | **172** | Public API surface |

### Tests (`test/`)
| Test File | Coverage |
|-----------|----------|
| `PathSecurity.test.php` | Path resolution, filename validation, sequential naming |
| `DirectoryScanner.test.php` | Directory scanning, item types, sorting |
| `FileOperations.move.test.php` | File moving, cross-device support |
| `FileOperations.rename.test.php` | File renaming with validation |
| `UploadValidator.test.php` | Upload validation, batch processing |

## �️ Architecture Overview

### Class-Based Design

All API endpoints extend the `RequestHandler` abstract base class, providing:
- Consistent HTTP method validation
- Unified error handling
- Secure path resolution
- Standard JSON response format

```php
require_once 'RequestHandler.php';

final class MyHandler extends RequestHandler
{
    protected array $allowedMethods = ['POST'];
    
    protected function process(): void
    {
        $path = $this->resolvePath($userPath);
        PathSecurity::validateFileName($name);
        $this->sendSuccess(['data' => $result]);
    }
}

(new MyHandler())->handle();
```

### Key Components

1. **RequestHandler** - Base class for all endpoints
   - HTTP method validation
   - Error handling and logging
   - JSON response formatting
   - Path resolution helpers

2. **PathSecurity** - Security and validation
   - Safe path resolution (prevents directory traversal)
   - Filename validation (platform-safe names)
   - Sequential file naming (prevents overwrites)

3. **DirectoryScanner** - Directory operations
   - Efficient directory listing
   - Type-safe item representation
   - Natural sorting (directories first)

4. **FileOperations** - File manipulation
   - Safe file moving (cross-device support)
   - File renaming with validation
   - Atomic operations

5. **UploadValidator** - Upload handling
   - MIME type validation
   - File size limits
   - Batch upload support

## 🚀 Development

### Running Tests

```bash
# Run all tests
./run-tests.sh

# Run individual test
php test/PathSecurity.test.php
```

### Adding New Endpoints

1. Create a new endpoint class extending `RequestHandler`
2. Implement the `process()` method
3. Define allowed HTTP methods
4. Use built-in security and validation helpers

Example:
```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/RequestHandler.php';

final class DeleteHandler extends RequestHandler
{
    protected array $allowedMethods = ['POST'];
    
    protected function process(): void
    {
        $path = $this->getInput(INPUT_POST, 'path', '');
        $target = $this->resolvePath($path);
        
        if (!is_file($target)) {
            throw new RuntimeException('File not found.');
        }
        
        if (!unlink($target)) {
            throw new RuntimeException('Failed to delete file.');
        }
        
        $this->sendSuccess();
    }
}

(new DeleteHandler())->handle();
```

## 📚 Documentation

- **Development Guidelines**: `.github/copilot-instructions.md`
- **Deployment Workflow**: `.github/workflows/deploy.yml`
- **Test Suite**: `run-tests.sh`
