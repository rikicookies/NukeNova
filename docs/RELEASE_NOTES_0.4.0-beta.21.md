# NovaNuke 0.4.0-beta.21

## RC fixture completeness correction

Beta 21 completes the temporary clean-distribution fixture introduced in Beta 20. The fixture now includes the structural `app/` and `storage/sessions/` directories required by the existing distribution checklist.

This is a test-only correction. It does not change database schema or runtime behavior.

For an installed site:

```bash
composer test:checkpoint
composer check:site
```

After applying the patch, complete the normal supported upgrade so the recorded Core version matches Beta 21.
