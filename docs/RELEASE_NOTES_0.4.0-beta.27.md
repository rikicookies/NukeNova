# NovaNuke 0.4.0-beta.27

## RC acceptance batch — backup recovery and deployment aggregation

Beta 27 closes another Release Candidate acceptance gap.

`backup:verify` already proved archive integrity; Beta 27 adds:

```bash
php bin/cms backup:restore-check
```

The command verifies the latest database/file pair, checks that the pair is fresh enough for RC deployment acceptance, restores the verified file TAR into a disposable temporary directory, validates the restored totals, and removes the temporary directory afterward. It never writes into the active application tree.

`rc:deployment` now aggregates:

- installed-site health
- fresh verified backup pair + disposable file restore
- production configuration
- authorization
- Membership integrity
- Payment integrity
- mail configuration
- bundled theme validation
- deployment secret policy

Beta 27 also fixes a namespace regression in the aggregate Payment check that had not been exercised on the Laragon development site because `rc:deployment` is intended for a production-like target.

No database migration is introduced.
