# Web File Browser API

A secure, lightweight PHP API for file management operations. Built with security-first principles and class-based architecture, requiring no external frameworks.

## Features

- 📁 **Directory Listing**: Browse directories with type-safe scanning
- 📤 **File Upload**: Single and batch uploads with MIME type validation
- ✏️ **File & Directory Rename**: Safe renaming with comprehensive validation
- 📦 **File Move**: Move files and directories to different locations
- 🗑️ **File Delete**: Move files to trash with safe path resolution
- 🔒 **Security**: Path traversal prevention, input validation, sandboxed operations
- 🧪 **Tested**: Comprehensive test suite for security-critical functions

## Requirements

- PHP 8.1 or higher
- Web server (Apache/Nginx)
- Write permissions for `data/` and `trash/` directories (located beside the deployed API directory)

Bootstrap behaviour: at runtime the bootstrap searches parent directories of the executing script for a `data` or `trash` directory and treats that parent as the web root. As long as `data` and `trash` exist beside the deployed API directory, the physical directory names do not matter.

## API Endpoints

All endpoints return JSON with `status` field (`success` or `error`).

| Method | Path | Description |
|--------|------|-------------|
| GET | `/list` | List directory contents |
| POST | `/upload` | Single file upload |
| POST | `/upload-images` | Batch image upload |
| POST | `/rename` | Rename file or directory |
| POST | `/delete` | Move file to trash |
| POST | `/move` | Move file or directory |

Full API specification: see [`docs/openapi.yaml`](docs/openapi.yaml). Open it in [Swagger Editor](https://editor.swagger.io/) for interactive documentation.

Frontend integration guide with fetch() examples: [`docs/api-usage.md`](docs/api-usage.md).

## Deployment

### Directory Structure

The repository uses generic directory names (`api/`) that are decoupled from the server's URL path. The actual URL prefix is determined by where you deploy, not by the repository:

```
Repository    →  Server (example)
public/api/   →  /home/user/public_html/my-app/api/   (URL: /my-app/api/)
src/          →  /home/user/src/                       (not web-accessible)
```

`.htaccess` files are co-located inside each deployed directory so they are included automatically:
- `public/api/.htaccess` — HTTPS redirect, CORS headers, compression, disables directory listing
- `src/.htaccess` — blocks all HTTP access (`Require all denied`)

> **Important**: if `SRC_DIR` is inside the web server's document root, verify that `src/.htaccess` is deployed and that your Apache configuration allows `.htaccess` overrides (`AllowOverride All`).

### GitHub Actions (FTP deploy)

Deployment is triggered automatically on push to `main`. Configure these repository secrets:

| Secret | Description | Example value |
|--------|-------------|---------------|
| `FTP_SERVER` | FTP hostname | `ftp.example.com` |
| `FTP_USERNAME` | FTP username | `user@example.com` |
| `FTP_PASSWORD` | FTP password | — |
| `SRC_DIR` | Server path for `src/` | `/home/user/src/` |
| `PUBLIC_DIR` | Server path for `public/api/` | `/home/user/public_html/my-app/api/` |

`PUBLIC_DIR` determines the URL path of the API. `data/` and `trash/` directories are created automatically at runtime beside the `api/` directory.

A manual dry-run is available under **Actions → Deploy → Run workflow** (dry-run defaults to `true`).

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
