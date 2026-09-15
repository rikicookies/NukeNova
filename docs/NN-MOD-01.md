# NN-MOD-01 — Module lifecycle isolation

NN-MOD-01 makes module startup fail closed without changing the public module API or CMS version.

## Lifecycle

`ModuleManager::bootEnabled()` still resolves dependencies, registers all eligible providers, and then boots them in stable dependency order. Runtime mutations are now associated with the module slug while `register()` and `boot()` execute.

The tracked runtime surfaces are:

- container bindings and instances;
- HTTP routes;
- event listeners;
- translation namespaces and cached catalogues;
- Twig namespaces and globals.

Successful `register()` changes remain provisional until `boot()` succeeds. A registration or boot exception removes that module's provisional state, records `last_error`, and allows unrelated healthy modules to continue. A later healthy override is not reverted when an earlier module fails.

Twig paths are available during `boot()`, but their previous path list is retained for rollback. Twig globals are staged and published only after successful boot because Twig does not expose a safe removal API after publication.

## Operational behavior

An enabled module that fails startup remains enabled in persistent configuration and receives a diagnostic in `modules.last_error`; it is not falsely made available in the current request. Administrators can correct the module and retry startup or explicitly disable it. NN-MOD-01 does not run migrations, change module enablement, or introduce runtime uninstalling.

## Validation

Run:

```bash
php vendor/bin/phpunit tests/Unit/ModuleMutationIsolationTest.php
set "NOVANUKE_RUN_INTEGRATION=1"&& php vendor/bin/phpunit tests/Integration/ModuleFaultIsolationIntegrationTest.php
composer test:checkpoint
composer test:integration
```

On Linux, replace the second command's environment prefix with `NOVANUKE_RUN_INTEGRATION=1`.
