---
paths:
  - "src/**/*.php"
---

# Security Rules

- All user-provided paths MUST be resolved through `PathSecurity::resolveSafePath()`
- Validate filenames with `PathSecurity::validateFileName()` before creation/rename
- Verify MIME types via `finfo` (file content analysis), never trust extensions
- Verify uploads with `is_uploaded_file()` before moving
- Use `PathSecurity::constructSequentialFilePath()` to prevent file overwrites
- Never expose internal filesystem paths in error messages or responses
- Throw typed exceptions (PathException, ValidationException, DirectoryException) for input errors
