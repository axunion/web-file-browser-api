---
name: security-review
description: Review changed code for security issues (path traversal, input validation, etc.)
allowed-tools: Bash, Read, Grep, Glob
context: fork
agent: Explore
---

Review the current git diff for security issues specific to this project:

1. Run `git diff` to see changes
2. Check for:
   - User paths not going through `resolvePath()` / `resolvePathWithTrash()`
   - Missing `PathSecurity::validateFileName()` calls before file creation/rename
   - MIME types checked by extension instead of `finfo`
   - Missing `is_uploaded_file()` verification
   - Internal paths leaked in error messages
   - Missing `declare(strict_types=1)`
3. Report findings with file:line references and severity
