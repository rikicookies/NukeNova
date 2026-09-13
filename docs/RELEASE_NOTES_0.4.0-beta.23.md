# NovaNuke 0.4.0-beta.23

## RC preparation — verified private-file restore

Beta 23 closes part of the RC backup/restore acceptance gap by adding a deliberately conservative private-file restore path.

```bash
php bin/cms backup:restore-files --archive=PATH --destination=PATH
```

The archive is fully verified before extraction. The destination must be empty, and the command has no overwrite or force option. This prevents a restore test from silently replacing a working installation.

Database SQL restore remains an explicit database-administration operation. NovaNuke does not guess which database should be destroyed or replaced.

No database migration or application feature change is introduced by Beta 23.
