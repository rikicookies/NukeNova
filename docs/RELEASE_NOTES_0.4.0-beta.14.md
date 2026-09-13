# NovaNuke 0.4.0-beta.14

This checkpoint repairs the failures and warnings exposed by the first Windows `composer test:checkpoint` run of Beta 13.

The failures were primarily stale or brittle source-contract tests rather than new runtime regressions: PHP interpolation inside test needles, a Beta 1 migration assertion that incorrectly rejected later Beta 2 migrations, old Wiki comment wiring expectations, formatting-sensitive VIP assertions, and a malformed inter-module dependency regex. Temporary test cleanup is also now recursive for Windows.

No new Membership feature, payment feature, or database migration is introduced by Beta 14.

Run `composer test:checkpoint` and send the complete result if anything remains.
