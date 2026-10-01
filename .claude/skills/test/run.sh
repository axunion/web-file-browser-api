#!/bin/sh
# Run unit tests, API tests, and PHPStan level 8 inside the php:8.5-apache image.
# Takes no arguments, so it can be allow-listed as a fixed command.
set -u

root=$(cd "$(dirname "$0")/../../.." && pwd)

exec docker run --rm -v "$root:/app" -w /app php:8.5-apache sh -c '
status=0
echo "=== Unit tests"
php test/run-all.php || status=1
echo "=== API tests"
php test-api/run-all.php || status=1
echo "=== PHPStan"
php vendor/bin/phpstan analyse --no-progress --memory-limit=512M || status=1
exit $status
'
