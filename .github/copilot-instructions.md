# Web File Browser API - Development Guidelines

## Core Principles

### 1. Security First
- **Never trust user input**: All external data must be validated and sanitized
- **Principle of least privilege**: Operations are sandboxed to specific directories
- **Defense in depth**: Multiple layers of validation (path resolution, filename validation, MIME type checking)
- **Fail securely**: Invalid operations must fail explicitly with clear error messages

### 2. Separation of Concerns
- **Single Responsibility Principle**: Each class handles one aspect of functionality
- **Abstract common patterns**: Shared behavior lives in base classes or utility classes
- **Dependency injection**: Classes receive dependencies rather than creating them
- **Clear boundaries**: HTTP handling, business logic, and file system operations are separated

### 3. Code Quality Standards
- **Type safety**: Use strict types and explicit return type declarations
- **Immutability where possible**: Prefer readonly properties and avoid state mutation
- **Fail fast**: Validate inputs early and throw exceptions for invalid states
- **Consistent conventions**: Follow PSR-12 coding standards throughout the codebase

## Architecture Patterns

### Request-Response Cycle
- All HTTP endpoints extend a common base handler
- Input validation happens before business logic
- Consistent JSON response format across all endpoints
- HTTP status codes reflect operation outcomes accurately

### Error Handling
- Use custom exceptions for domain-specific errors
- Catch and handle exceptions at the appropriate level
- Log security-relevant events
- Never expose internal implementation details in error messages

### File System Operations
- All paths must be resolved through security utilities before use
- Use atomic operations where possible
- Handle cross-device operations gracefully
- Clean up resources properly (file handles, temporary files)

## Security Guidelines

### Path Security
- Resolve all user-provided paths to absolute paths within allowed boundaries
- Prevent directory traversal attacks through canonicalization
- Reject suspicious patterns (null bytes, excessive separators)
- Validate that resolved paths stay within sandbox boundaries

### File Validation
- Validate filenames against length limits and character restrictions
- Check for platform-specific reserved names
- Verify MIME types using file content inspection, not extensions
- Use sequential naming to prevent overwrite conflicts

### Upload Security
- Verify files are actually uploaded (use PHP upload functions)
- Enforce size limits per file and per request
- Whitelist allowed MIME types
- Move uploaded files atomically to final destination

## Development Practices

### Code Style
- Follow PSR-12 Extended Coding Style Guide
- Use meaningful names that express intent
- Keep functions small and focused
- Comment only when code cannot be self-documenting

### Testing Philosophy
- Write tests for security-critical functions
- Test edge cases and error conditions
- Use simple assertion-based tests without heavy frameworks
- Tests should be executable directly via PHP CLI
- Maintain both unit tests (core classes) and API tests (HTTP endpoints)

### Test Structure
- **Unit tests** (`test/`): Test individual classes and functions in isolation
- **API tests** (`test-api/`): Test HTTP endpoints via actual requests in Docker
- Unit tests run anywhere PHP is available
- API tests require Docker environment and test real HTTP behavior
- API tests automatically manage server lifecycle

### Documentation
- Code comments in English, essential information only
- Error messages must be user-facing and actionable
- Git commits in English, imperative mood, concise
- Document "why" in comments, not "what" (code shows "what")

## Extension Guidelines

### Adding New Endpoints
- Extend the base request handler
- Declare allowed HTTP methods explicitly
- Use provided helper methods for input and path handling
- Follow established response patterns
- Implement proper error handling

### Adding New Utilities
- Ensure single responsibility
- Make functions static when no state is needed
- Use type hints for all parameters and return values
- Throw exceptions for invalid inputs
- Add corresponding tests

### Adding New Tests
- **Unit tests**: Place in `test/` directory, name as `ClassName.test.php`
- **API tests**: Place in `test-api/` directory, name as `endpoint-name.test.php`
- Use `ApiTestHelpers` for HTTP requests in API tests
- Verify both success and error cases
- Test security validations (path traversal, invalid inputs)
- Clean up test files and data after each test

### Modifying Security-Critical Code
- Understand the threat model before making changes
- Maintain backward compatibility for security validations
- Add tests covering new edge cases
- Document security implications in code review

## Communication Standards

- **Code comments**: English only, essential information
- **Error messages**: English, user-facing, consistent
- **Git commits**: English, imperative mood (e.g., "Add validation", "Fix bug")
- **Documentation**: Clear, concise, principle-focused

## Docker Environment

This project is designed to work with Docker:
- Use Docker alias: `docker run --rm -it -v $PWD:/app -w /app php:8.4-apache php`
- API tests run within Docker containers
- Test runner manages server lifecycle automatically
- HTTPS redirect disabled during tests via `TESTING=true` environment variable
- Use `127.0.0.1` instead of `localhost` for container networking
