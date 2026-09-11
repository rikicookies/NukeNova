# NovaNuke 0.2.0-alpha.34

Alpha.34 begins the stabilization roadmap with a repeatable clean-install baseline and the optional NovaTech Community demonstration dataset.

## Added

- Optional **Demo Content** module and stable `novatech-community-v1` dataset.
- Fictional accounts and realistic content for active News, Pages, Downloads, Web Links, Comments, Polls, Friends and Private Messages modules.
- Active and expired VIP examples, normal event-driven notifications, provider-based Search results and derived Statistics.
- Dataset ownership tables for duplicate prevention and a future controlled Remove/Reset lifecycle.
- Fresh-database integration coverage that installs and enables every bundled module and verifies all module migrations were executed.
- A repeatable Laragon clean-install and acceptance checklist.

Demo Content creates no Wiki pages and does not create or change Blocks.

## Upgrade from alpha.33

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and all of `storage/private/`.
2. Replace the application files with this release.
3. Run `composer install` and `php bin/cms cache:clear`.
4. Open Admin → Modules, install and enable **Demo Content** only if a fictional development dataset is wanted.
5. Install desired content modules before installing the dataset; inactive modules are skipped.

There is no new core migration or Composer dependency. Installing Demo Content runs its module migration, which creates two ownership tables, through the normal module lifecycle.

## Focused tests

```bat
vendor\bin\phpunit --testsuite Unit --filter "DemoContent|ReleaseVersion"
composer test:integration
```

Then run:

```bat
composer test
php bin/cms release:check
```

## Acceptance

- Complete [CLEAN_INSTALL_CHECKLIST.md](CLEAN_INSTALL_CHECKLIST.md) twice with separate empty databases.
- Confirm every bundled module installs, enables and has no pending migration.
- Install Demo Content and verify the recorded counts and Public/Member/VIP visibility.
- Confirm a second dataset installation is rejected.
- Activate Default, Classic and NovaModern and inspect the homepage plus representative content detail pages.

## Known limitation

Demo Content currently supports Install only. Remove/Reset is designed around recorded ownership but intentionally deferred until its destructive workflow receives separate review and tests.
