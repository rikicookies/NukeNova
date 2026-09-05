# Performance

NovaNuke 0.1.0-beta.2 establishes a lightweight performance baseline suitable for shared hosting. It adds no daemon, queue, Redis server or external cache dependency.

## Request-level optimizations

- Settings are loaded once in a single query and retained only for the current PHP request. A write invalidates that snapshot immediately.
- Module and theme availability/inventory results are reused within a request and invalidated after every lifecycle mutation.
- Public menu items and their role restrictions are hydrated in three fixed queries instead of two extra queries per menu.
- Block role restrictions are hydrated in one batch query instead of one query per block.

These caches never cross requests, so deployments do not need cache-coordination infrastructure and another PHP worker cannot receive stale process memory. Twig's existing filesystem cache remains the only persistent rendered-code cache.

## Production recommendations

- Enable PHP OPcache and give it enough memory for the application plus installed modules.
- Use production mode with `APP_DEBUG=false`; clear NovaNuke/Twig caches after updating code or themes.
- Keep MySQL/MariaDB on the same host or low-latency network where practical.
- Serve theme assets and public media directly through Apache/Nginx, not PHP.
- Measure before adding indexes or caches. Slow-query logs are more useful than speculative abstractions.

Run `composer test:integration` after repository changes. `RepositoryPerformanceIntegrationTest` verifies cache invalidation and the batched menu result shape. Performance work must preserve authorization and visibility checks; cached data must never be shared between requests or users.
