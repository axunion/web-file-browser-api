# Web File Browser API

A secure, lightweight PHP API for file management operations. Built with security-first principles and class-based architecture, requiring no external frameworks.

## Features

- 📁 **Directory Listing**: Browse directories with type-safe scanning
- 📤 **File Upload**: Single and batch uploads with MIME type validation
- ✏️ **File & Directory Rename**: Safe renaming with comprehensive validation
- 🔒 **Security**: Path traversal prevention, input validation, sandboxed operations
- 🧪 **Tested**: Comprehensive test suite for security-critical functions

## Requirements

- PHP 8.1 or higher
- Web server (Apache/Nginx)
- Write permissions for `public/data/` and `public/trash/` directories

Bootstrap behaviour: at runtime the bootstrap searches parent directories of the executing script for a `data` or `trash` directory and treats that parent as the web root. As long as `data` and `trash` exist under the web root, the physical name of the web folder (for example `public`) does not matter. For non-standard layouts, override via environment variables or `Config.php`.

## API Endpoints

All endpoints return JSON with `status` field (`success` or `error`).

## Development

### Running Tests

```bash
# Unit tests (core classes)
php test/run-all.php

# API tests (HTTP endpoints)
php test-api/run-all.php              # Run all API tests
php test-api/upload-images.test.php   # Run individual test (auto-starts server)
```

**Note**: API tests automatically start/stop a PHP built-in server. Individual tests can be run standalone without manually starting a server.

## Architecture

- **Security First**: Path traversal prevention, input validation, sandboxed operations
- **Simple & Testable**: Hand-written tests, no frameworks, direct execution
- **Type Safe**: Strict types throughout, fail fast on invalid input

See `.github/copilot-instructions.md` for detailed development guidelines.
