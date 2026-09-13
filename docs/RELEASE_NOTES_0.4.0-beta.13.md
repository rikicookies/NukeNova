# NovaNuke 0.4.0-beta.13

## Beta 2 — Memberships stabilization checkpoint

Beta 13 is the first accumulated checkpoint after the Beta 10–12 QA loop. It intentionally combines multiple corrections and regression tests into one package instead of releasing every small fix separately.

### Runtime corrections
- Custom-day grants only extend an actually active VIP grant; a future scheduled grant is no longer treated as current.
- Applying an immediate custom-day grant cancels an existing future VIP schedule cleanly.
- Extensions emit the dedicated `membership.extended` event rather than masquerading as a second assignment.
- Assigning Free from Membership Admin is logged as a revocation transition.

### Test hardening
- Extension and activation-marker source contracts are scoped to the method being tested.
- Membership integration coverage now exercises lifecycle, Lifetime, scheduling, activation/expiration, dry-runs, repeated actions, invalid input, replacement history, health integrity, repository history/overview, and legacy custom-day behavior.

### Checkpoint workflow

```bash
composer test:checkpoint
composer check:release
```

`test:checkpoint` runs the normal PHPUnit suite followed by the isolated integration suite. `check:release` runs install, production, and Membership integrity checks against the current environment.

No payment support is included. No Membership schema migration is required for Beta 13.
