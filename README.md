# NovaNuke

NovaNuke is a lightweight, modular CMS inspired by the simplicity of PHP-Nuke and built from scratch for modern PHP.

## Current status

Phase 2 foundation is in progress. The current skeleton includes:

- PSR-4 autoloading;
- environment-based configuration;
- a small service container;
- HTTP request and response objects;
- a router with friendly parameterized URLs;
- centralized error handling and logs;
- PDO connection factory;
- a minimal migration runner;
- Twig rendering with automatic HTML escaping;
- Apache front-controller rules;
- PHPUnit unit tests.

Authentication, the installer, modules, themes, blocks and content are intentionally not implemented yet.

## Requirements

- PHP 8.3 or newer;
- Composer 2;
- MySQL 8+ or a compatible MariaDB release;
- PHP extensions: PDO, PDO MySQL, JSON, Mbstring and OpenSSL.

## Local setup

```bash
composer install
cp .env.example .env
composer test
composer serve
```

Open `http://127.0.0.1:8080`.

Do not expose the project root to the web. The document root must be the `public/` directory.

## Security baseline

- Debug output is disabled by default in production.
- Twig escapes HTML output automatically.
- PDO emulated prepared statements are disabled.
- Secrets belong in `.env`, which is excluded from Git.
- The public entry point is isolated in `public/`.

CSRF, authentication, authorization, session hardening and rate limiting arrive in Phase 3.

## Tests

Run:

```bash
composer test
```

No test result should be reported as passing unless it was run under PHP 8.3+.
