# Changelog

All notable NovaNuke changes will be documented here.

## [Unreleased]

### Added

- Initial PHP 8.3 and Composer project definition.
- Environment and application configuration.
- Lightweight service container.
- Request, response, router and HTTP kernel.
- Centralized production-safe error handling.
- PDO connection factory and migration runner.
- Twig view rendering and starter page.
- Apache front controller configuration.
- Initial PHPUnit test suite.
- Web installer with server requirement checks.
- CSRF token and hardened session foundations.
- Atomic environment configuration writer.
- Initial user, profile, role, permission and settings tables.
- Initial Super Administrator and six built-in roles.
- Installation lock that removes installer routes after setup.
- Installer validation and environment writer tests.

### Changed

- NovaNuke now routes unconfigured installations exclusively to the installer.
- Migration recording accounts for MySQL implicit DDL commits.
