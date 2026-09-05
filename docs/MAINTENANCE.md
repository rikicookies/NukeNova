# Scheduled data maintenance

NovaNuke can remove expired security records and operational data after fixed retention periods. The command never removes content, users or current authentication records.

## Retention rules

| Data | Retention |
| --- | --- |
| Expired rate-limit windows | Until expiration |
| Expired password-reset tokens | Until expiration |
| Expired email-verification tokens | Until expiration |
| Expired email-change tokens | Until expiration |
| Successful sign-in history | 180 days |
| Administrative activity logs | 365 days |
| Recorded search terms | 365 days |
| Aggregated daily statistics | 395 days |

Search and Statistics participate only while their modules are enabled. Each module owns its rule through the typed `maintenance.pruning` event.

## Run it manually

Always inspect the count first:

```bash
php bin/cms maintenance:prune --dry-run
php bin/cms maintenance:prune
```

The live command uses one database transaction. If any core or module cleanup fails, all deletions from that run are rolled back and the command exits with status `1`.

## Laragon

Open Laragon Terminal in the NovaNuke project and run the two commands above. For automatic local maintenance, use Windows Task Scheduler with the full paths to Laragon's PHP executable and NovaNuke's `bin/cms` file. Set the task's working directory to the NovaNuke project root.

Example arguments:

```text
C:\dev\www\novanuke\bin\cms maintenance:prune
```

Use the actual PHP executable selected by Laragon rather than relying on a different system PHP installation.

## Linux cron and shared hosting

A daily execution is sufficient. Replace both paths with those reported by the hosting account:

```cron
17 3 * * * /usr/local/bin/php /home/account/novanuke/bin/cms maintenance:prune >> /home/account/novanuke/storage/logs/maintenance.log 2>&1
```

Keep the project and log destination outside the public web directory. Some shared hosts expose cron as a control-panel form; enter the command portion there and select a daily schedule. Confirm the hosting CLI uses PHP 8.3 or newer.

Run `--dry-run` manually after installing or updating a module. Do not schedule dry-run mode as a substitute for actual cleanup.

## Module contract

An enabled module may listen for `maintenance.pruning`. It must count eligible rows in dry-run mode, delete exactly those rows in live mode and add its non-negative result under a module-prefixed name such as `example.audit`. It must not commit or roll back the shared transaction.
