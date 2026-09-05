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
- Username/email login and POST-only logout.
- Authenticated session manager with session ID regeneration.
- Protected Super Administrator dashboard.
- Suspended and deleted account enforcement.
- Login attempt throttling and generic credential errors.
- Login history migration and last-access recording.
- `composer migrate` command for installed sites.
- Login validation and safe redirect tests.
- Password recovery request and reset screens.
- Hashed, expiring, single-use password reset tokens.
- Development log mailer with a production safety lock.
- Authentication versioning to invalidate prior sessions after password changes.
- Generic recovery responses that do not reveal registered email addresses.
- Password reset request throttling.
- Reset token, password policy and log mailer tests.
- Immediate validation screen for consumed or expired password reset links.
- Public registration, closed by default.
- Configurable email verification and 24-hour one-time verification tokens.
- Automatic Member role assignment for public registrations.
- Super Administrator user-registration settings screen.
- Registration validation and verification mail tests.
- Server-enforced role and permission authorization service.
- Sixteen initial core and module permission definitions.
- User role assignment, suspension and reactivation screens.
- Role permission management screen.
- Protection for the final active Super Administrator.
- Persistent database-backed rate limits for login, recovery and registration.
- Administrative activity log and viewer.
- Authorization developer documentation and role-safety tests.
- Module manifests, detection and compatibility checks.
- Module installation, activation, deactivation, updating and controlled uninstallation.
- Module-owned migration history and dependency protection.
- Synchronous prioritized event dispatcher for hooks.
- Namespaced Twig view paths for modules.
- Administrative modules panel and audit events.
- Official Welcome lifecycle demonstration module.
- Module development documentation and manifest/event tests.

### Changed

- NovaNuke now routes unconfigured installations exclusively to the installer.
- Migration recording accounts for MySQL implicit DDL commits.
