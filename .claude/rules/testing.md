---
paths:
  - "test/**/*.php"
  - "test-api/**/*.php"
---

# Testing Rules

- Always include `declare(strict_types=1)`
- Unit tests: require TestHelpers.php, clean up temp directories in all code paths
- API tests: require TestSetup.php first, then ApiTestHelpers.php
- Register uploaded files for cleanup: `ApiTestHelpers::registerUploadedFile()`
- Test these cases: success, error/edge cases, security validations (path traversal, invalid inputs)
- Use `ApiTestHelpers::createTempFile()` for temp files (auto-cleanup)
- Name convention: unit `{ClassName}.test.php`, API `{endpoint-name}.test.php`
