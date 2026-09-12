# NovaNuke 0.2.0-alpha.54

## Module Contract Guardrails

Alpha.54 begins Phase 4 — Module Contracts / Internal API by enforcing the rules already documented for module API 1.0.

### Included
- Module manifests now require complete semantic versions for the module, CMS/PHP minimums and dependency minimums.
- Module permissions must belong to the declaring module namespace and duplicate permission/event declarations are rejected.
- Provider classes must belong to the PHP namespace for their own module directory.
- Router registration now rejects duplicate route names and exact HTTP-method/path collisions before a module can shadow an existing handler.
- GET and POST may continue to share a path because their method sets do not overlap.
- A bundled-module contract test validates every shipped manifest/provider against API 1.0.

### Compatibility
`ModuleApi::VERSION` remains **1.0**. `ModuleInterface`, `ModuleContext` and existing public method signatures are unchanged. This release tightens validation of invalid module packages rather than introducing a new extension API.

There is no database migration, permission schema change, Composer dependency or Blocks change.

### Upgrade
Run `php bin/cms upgrade:check --from=0.2.0-alpha.53`, the normal unit/integration/release checks, then `php bin/cms upgrade:complete --from=0.2.0-alpha.53`.
