---
name: new-endpoint
description: Scaffold a new API endpoint with tests
argument-hint: "<endpoint-name>"
---

Create a new API endpoint. Ask for: endpoint name, HTTP method, description.

Create these files following existing patterns:

1. `public/web-file-browser-api/$ARGUMENTS/index.php`
   - Follow endpoint pattern in CLAUDE.md
2. `test-api/$ARGUMENTS.test.php`
   - Include TestSetup.php, ApiTestHelpers.php
   - Test success, error, and security cases

After creating, run the test to verify.
