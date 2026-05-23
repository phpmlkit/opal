# Contributing to phpmlkit/opal

## Requirements

- PHP 8.2+
- `ext-ffi`

## Setup

```bash
composer install
```

This downloads the correct libvips binary for your platform into `lib/`.

## Development Commands

| Command                  | Description                    |
|--------------------------|--------------------------------|
| `composer test`          | Run all tests                  |
| `composer test:pretty`   | Run tests with testdox output  |
| `composer test:coverage` | Run tests with coverage report |
| `composer cs:fix`        | Auto-fix code style            |
| `composer cs:check`      | Check code style (dry-run)     |
| `composer lint`          | Run PHPStan analysis           |

## Coding Standards

- Follow PSR-12 and `@PhpCsFixer` rules — run `composer cs:fix` before committing.
- Declare `strict_types=1` in every file.
- All new public API must be fully typed (PHP 8.2+ type system).
- All new methods must include PHPDoc with parameter and return types.
- Immutable operations: transformations return new instances, never mutate `$this`.

## Tests

- Tests live in `tests/` and use PHPUnit 10.
- Name test methods with `test` prefix or use the `#[Test]` attribute.
- Run `composer test` to verify nothing is broken.

## Pull Requests

1. Ensure all tests pass and code style is clean.
2. Run `composer lint` (PHPStan level 8) — no new errors.
3. Add tests for any new functionality.
4. Keep commits small and use [conventional commits](https://www.conventionalcommits.org/).