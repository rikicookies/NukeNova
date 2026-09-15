# Known Issues and Technical Debt

## RC.4 reliability remediation

NN-MIG-01, NN-BACKUP-01, NN-MAIL-01, NN-THEME-01, NN-SEC-01, and NN-HTTP-01 are resolved in the RC.4 working tree. NN-MIG-01 adds durable migration recovery; NN-BACKUP-01 adds consistent InnoDB snapshots, explicit backup-set IDs, and real disposable SQL restore verification; NN-MAIL-01 separates structural configuration, production SMTP readiness, and durable manual delivery acceptance for registration verification, password reset, and email change; NN-THEME-01 validates and verifies a staging tree before an atomic rename swap with rollback to the previous public assets. Existing migration history remains valid. All six authorized RC.4 findings are now implemented in the working tree; RC.4 still must not be promoted until the final checkpoint/integration/release acceptance commands are executed and the six findings are re-evaluated against that exact artifact.

## Dynamic blocks are postponed

Dynamic Polls and Statistics blocks are treated as provisional in the `0.2.0-alpha` line. The core now registers mutable block regions before provider templates render and isolates provider exceptions, but the complete matrix of dynamic providers, page types and both bundled themes has not completed acceptance testing.

If a dynamic provider fails, NovaNuke should omit that block and write a sanitized log entry. A Blocks-only failure must not block unrelated feature work. Further improvements to provider behavior, sidebar regions or advanced block placement are postponed unless they prevent another prioritized feature from running.

## Responsive block order

NovaModern 1.1.0 keeps block columns readable on small screens, but their final responsive ordering has not been designed yet. Depending on which positions are populated, left-sidebar blocks may appear before the primary content and right-sidebar blocks after it. A future navigation/layout checkpoint will define configurable or content-first ordering without changing block provider logic.

- RC.4 NN-SEC-01: resolved — Comments list/create/edit/react/report now revalidate parent target audience; inaccessible comment IDs return uniform 404 for mutation endpoints.
- RC.4 NN-HTTP-01: resolved — `Response::redirect()` rejects scheme-relative URLs, backslashes and ASCII control/space characters while retaining normal local paths, queries and fragments.
