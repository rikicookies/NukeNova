# Testing NovaNuke

NovaNuke separates fast unit tests from MySQL/MariaDB integration tests. Neither suite reads the normal `.env` file or targets the configured `novanuke` database.

## Unit tests

```bash
composer test
```

This runs only the `Unit` suite and requires no database.

## Integration tests on Laragon

Copy the testing example once:

```bash
copy .env.testing.example .env.testing
```

The defaults use Laragon's common MySQL connection: `127.0.0.1:3306`, user `root` and an empty password. Adjust only `.env.testing` when your local database credentials differ. The real file is ignored by Git and release packages.

Run:

```bash
composer test:integration
```

Every integration test creates a new database named `novanuke_test_` followed by 16 random hexadecimal characters, executes the core migrations and removes that exact temporary database afterward. The harness refuses names outside that pattern. It never accepts the normal application database name as a deletion target.

The database account needs temporary `CREATE DATABASE` and `DROP DATABASE` privileges. This is appropriate for local Laragon development but commonly unavailable—and not recommended—on production shared hosting.

Run both suites locally with:

```bash
composer test:all
```

## Initial integration coverage

- all core migrations against real MySQL/MariaDB;
- password login, session identity, login history and `user.logged_in` dispatch;
- Administrator permissions including denial of `roles.manage`;
- hashed, expiring, single-use password-reset tokens and password version invalidation;
- cancellation of pending email-change tokens after password reset;
- Welcome module install, permission registration, activation and route boot;
- non-destructive uninstall preserving module data and migration history;
- destructive uninstall removing only module-owned schema.

SMTP remains a separate manual smoke test because reliable delivery depends on the real DNS, mailbox and hosting provider.

## Failure cleanup

If PHP or MySQL terminates abruptly, inspect local databases whose names match `novanuke_test_[a-f0-9]{16}`. Confirm the exact generated pattern before manually dropping an abandoned test database. Never automate wildcard deletion and never alter the normal `novanuke` database.
