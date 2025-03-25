# Code Guidelines for Claude

## Build Commands
- `composer test` - Run all tests
- `composer test-dox` - Run tests with readable output
- `composer test-coverage` - Run tests with coverage (HTML report)
- `composer test-coverage-txt` - Run tests with coverage (text report)
- `vendor/bin/pest tests/Unit/SomeTest.php` - Run a single test
- `vendor/bin/pest --filter=testMethodName` - Run a specific test method

## Code Style
- PHP 8.0+ with strict types
- PSR-4 autoloading
- Follow type declarations (parameter types, return types)
- Use union types for parameters where needed (e.g., `callable|object|string`)
- Ensure methods throw appropriate exceptions (`TypeError` for invalid arguments)
- Static functions with verb-noun naming (`closure()`, `bind()`, `call()`)
- Document public functions with PHPDoc, especially exceptions (`@throws`)
- Test coverage for all edge cases
- Keep functions focused and concise
- Follow existing formatting (spaces, indentation, brackets)

## Architecture
- Fluent functional API for modifying closures
- Support for FQCN strings, invokable classes, and regular closures
- Clear error messages with proper context