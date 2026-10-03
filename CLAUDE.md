# CLAUDE.md

## Project

Security-first, framework-free PHP (>=8.2) file-management API. Look things up rather
than relying on this file: `README.md` (deployment, `src/config.local.php`, test
commands), `docs/openapi.yaml` (endpoint contract), `src/bootstrap.php` (request helpers,
exception-to-status mapping), and any `public/api/*/index.php` (endpoint pattern).

## Security Guidelines

- All user-provided paths go through `resolvePath()` or `resolvePathWithTrash()`
- Validate filenames with `PathSecurity::validateFileName()` before any create/rename
- Verify MIME types via `finfo` (content analysis) — never trust file extensions
- Verify uploads with `is_uploaded_file()` before moving
- Never access `$_GET`, `$_POST`, `$_FILES` directly in endpoints — use `getInput()`
- Never expose internal filesystem paths in responses or error messages
- API key auth is enforced only when a key is configured (env `API_KEY` or
  `src/config.local.php`); don't create that file locally, or `test-api` fails with 401

## Conventions

- New endpoint: `public/api/{name}/index.php`, mirroring an existing one. Update
  `docs/openapi.yaml` in the same commit whenever an endpoint's parameters, responses,
  errors, or constraints change, and `docs/api-usage.md` when fetch patterns, upload
  limits, or collision handling change.
- Every PHP file: `declare(strict_types=1)`, explicit parameter/return types, PSR-12.
  Validate inputs early and throw for invalid states.

## Testing

Run everything (unit, API, PHPStan level 8) with the `test` skill, which also detects the
PHP runtime. Unit tests go in `test/{ClassName}.test.php`, API tests in
`test-api/{endpoint-name}.test.php`; cover success, error, and path-traversal cases. In
API tests, require `TestSetup.php` before `ApiTestHelpers.php` and register uploaded
files with `registerUploadedFile()`. Don't persist a throwaway check written only to
confirm one change; keep a regression test only for a flow with evidence it can break.

## Subagents

`.claude/agents/` defines `reviewer` (conventions and correctness), `security-reviewer`
(the Security Guidelines above), and `tester` (runs the `test` skill; edits test files
only). All are read-only or test-only; use the built-in `Explore` for codebase lookup.
Nearly every real change touches the risk areas — `src/` and `public/api/`.

- **Trivial** (typos, one-liners, doc/config tweaks): implement directly, no agents.
- **Contained** (self-contained change in one area): implement directly, then run
  `reviewer` and `tester` in parallel without asking — plus `security-reviewer` when
  `src/` or `public/api/` is touched.
- **Large, ambiguous, or high-risk**: propose `/goal` to the user instead of starting,
  with a condition naming the agents, e.g. "implement X; done when reviewer and
  security-reviewer report no findings and tester passes" — the evaluator only checks
  the condition text, so omitting them ends the loop right after implementation.

The main conversation always writes the code; there is no implementer agent, because
the agents' value is judging work they didn't write.
