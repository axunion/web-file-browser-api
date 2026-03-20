---
paths:
  - "public/web-file-browser-api/**/*.php"
---

# Endpoint Rules

- Always include `declare(strict_types=1)` at the top
- Require bootstrap.php via relative path from __DIR__
- Use `validateMethod()` to restrict HTTP methods
- Wrap business logic in try/catch with `handleError()`
- Use `resolvePath()` or `resolvePathWithTrash()` for ALL user-provided paths
- Use `getInput()` for reading request parameters
- Return responses via `sendSuccess()` / `sendError()` only
- Never access `$_GET`, `$_POST`, `$_FILES` directly — use the bootstrap helpers
