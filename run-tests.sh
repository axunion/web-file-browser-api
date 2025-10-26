#!/bin/bash

# Run all refactored tests for the new class-based architecture

echo "=========================================="
echo "Running Refactored Test Suite"
echo "=========================================="
echo ""

TESTS=(
    "test/PathSecurity.test.php"
    "test/DirectoryScanner.test.php"
    "test/FileOperations.move.test.php"
    "test/FileOperations.rename.test.php"
    "test/UploadValidator.test.php"
)

FAILED=0
PASSED=0

for test in "${TESTS[@]}"; do
    echo "Running: $test"
    echo "------------------------------------------"

    if php "$test"; then
        ((PASSED++))
        echo "✓ PASSED"
    else
        ((FAILED++))
        echo "✗ FAILED"
    fi

    echo ""
done

echo "=========================================="
echo "Test Summary"
echo "=========================================="
echo "Passed: $PASSED"
echo "Failed: $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    echo "✓ All tests passed!"
    exit 0
else
    echo "✗ Some tests failed."
    exit 1
fi
