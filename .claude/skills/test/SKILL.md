---
name: test
description: Run the full check suite (unit tests, API tests, PHPStan) using whatever PHP runtime is available on this machine
---

# Run Tests

Run all project checks: unit tests, API tests, and PHPStan static analysis.

## Step 1: Detect a PHP runtime

The project requires PHP >= 8.2 with the `fileinfo`, `intl`, `mbstring`, and `curl`
extensions. Developers are free to provide PHP however they like — do NOT assume a
specific setup. Detect one in this order and use the first that works:

1. **Local PHP** — `php -v 2>/dev/null | grep -q '^PHP'` succeeds in a
   non-interactive shell: run commands as plain `php …`. (Do not rely on
   `command -v php` alone — it can match a shell alias that wraps docker and
   breaks non-interactively.)
2. **Docker** — no local `php` but `docker info` succeeds (if docker is installed
   but the daemon is down and colima is available, run `colima start` first).
   Run exactly `sh .claude/skills/test/run-docker.sh` — no `cd`, pipes, or redirects —
   from the repo root: it runs all of Step 2 in one container and may be
   pre-approved to run outside the sandbox. For any other command, prefix it with

   ```bash
   docker run --rm -v "$PWD:/app" -w /app php:8.5-apache
   ```

   Never pass `-it` (fails in non-interactive shells). Note: files the tests
   create under `public/data`/`public/trash` will be owned by root.
3. **Neither** — stop and ask the user how they run PHP for this project.

## Step 2: Run the checks

Using the runtime from Step 1, run in order:

```bash
# 1. Unit tests
php test/run-all.php

# 2. API tests (starts and stops its own PHP server, also inside a container)
php test-api/run-all.php

# 3. PHPStan level 8
php vendor/bin/phpstan analyse --no-progress --memory-limit=512M
```

If `vendor/` is missing, install dev dependencies first: `composer install`
(with docker use the `composer:2` image, since `php:8.5-apache` has no composer).

## Step 3: Report

Report pass/fail per suite. On failure, show the failing test output verbatim —
do not summarize it away.
