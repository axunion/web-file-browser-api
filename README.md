# Web File Browser API

A security-first, framework-free PHP API for managing files: list, upload, rename, move, and delete (to trash).

## Requirements

- PHP 8.2+ with `fileinfo` (`intl` and `mbstring` recommended for Unicode filename handling)
- Apache with `.htaccess` overrides enabled (`AllowOverride All`)

## API

All endpoints return JSON with a `status` field (`success` or `error`).

| Method | Path | Description |
|--------|------|-------------|
| GET | `/list` | List directory contents |
| POST | `/upload` | Upload a single file |
| POST | `/upload-images` | Upload a batch of images |
| POST | `/rename` | Rename a file or directory |
| POST | `/move` | Move a file or directory |
| POST | `/delete` | Move a file or directory to trash |

- Spec: [`docs/openapi.yaml`](docs/openapi.yaml) (viewable in [Swagger Editor](https://editor.swagger.io/))
- Frontend guide with `fetch()` examples, limits, and allowed types: [`docs/api-usage.md`](docs/api-usage.md)

## Deployment

Pushing to `main` deploys via FTP (GitHub Actions) after tests pass:

| Repository | Server | Notes |
|------------|--------|-------|
| `public/` | `PUBLIC_DIR` | API is served at `<PUBLIC_DIR URL>/api/` |
| `src/` | `SRC_DIR` | Keep outside the document root; `src/.htaccess` denies HTTP access as a fallback |

Required repository secrets: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `PUBLIC_DIR`, `SRC_DIR`.
A manual dry run is available under **Actions → Deploy → Run workflow**.

`data/` and `trash/` are created beside `api/` on first request if missing; the web server needs write access there.
`public/.htaccess` refuses requests for script-like files (`.php`, `.phtml`, etc.) under them so uploads can never execute; on a non-Apache server, add an equivalent rule.

### Configuration

Optional. Place `src/config.local.php` on the server manually (it is gitignored and never deployed):

```php
<?php

declare(strict_types=1);

return [
    'api_key' => 'replace-with-a-long-random-secret',
    'cors_allowed_origin' => 'https://example.com', // default: '*'
];
```

- `api_key`: when set, every request must send it in the `X-Api-Key` header (CORS preflight is exempt), or the API returns 401. The `API_KEY` environment variable takes precedence. With no key, authentication is disabled.
- `cors_allowed_origin`: value of `Access-Control-Allow-Origin`.

Don't create this file in a local working copy — an `api_key` there makes the API tests fail with 401.

## Development

```bash
php test/run-all.php                                             # Unit tests
php test-api/run-all.php                                         # API tests (starts its own server)
composer install && vendor/bin/phpstan analyse --memory-limit=512M  # PHPStan level 8
```

Individual API tests can also be run directly, e.g. `php test-api/upload.test.php`.
