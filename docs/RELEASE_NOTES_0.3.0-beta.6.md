# NovaNuke 0.3.0-beta.6

## Production hardening — release and deployment smoke checks

- `php bin/cms release:smoke` runs before application bootstrap and does not require a database connection.
- It verifies the release checklist, current version metadata, bundled module manifests, migration file safety and the private-storage Apache deny rule.
- `release:check` now requires the deployment-critical `public/uploads/.htaccess`, `storage/private/.htaccess`, and installation/production documentation.
- The release process now separates package smoke checks from environment checks (`production:check`) and database/application checks.

No database migration is required.
