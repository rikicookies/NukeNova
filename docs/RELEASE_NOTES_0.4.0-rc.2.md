# NovaNuke 0.4.0-rc.2

## Shared-hosting installer bootstrap fix

RC.2 fixes the first release-blocking issue found during Bluehost acceptance.

On RC.1, a completely uninstalled web request could construct the normal installed-site Kernel dependencies before the installer form was shown. Those dependencies include Settings/Auth/Module services backed by PDO, so shared hosting attempted the development defaults (`root` with no password) and returned HTTP 500 before the user could enter real database credentials.

RC.2 makes installer-mode dispatch database-independent until `storage/installed.lock` exists.

Regression coverage now requires:

- `/install` renders without `.env`, installation lock or working database credentials;
- `/` redirects to `/install` under the same conditions;
- installed-site Kernel dependencies remain enabled only after installation;
- release packages include `composer.lock` for reproducible dependency installs.

No database migration is introduced.
