# Demo Content

The optional **Demo Content** module installs the fictional **NovaTech Community** dataset. It is intended for local development, screenshots, theme work and acceptance testing. It is not production content.

Dataset ID: `novatech-community-v1`

## Install

1. Install and enable the content modules you want to demonstrate under **Admin → Modules**.
2. Install and enable **Demo Content**.
3. Open **Admin → System → Demo content** as the Super Administrator.
4. Review the detected module coverage, select the confirmation checkbox and press **Install demo content**.

The write uses POST, CSRF protection, `settings.manage`, an additional Super Administrator check and an Activity Log entry. The stable dataset ID prevents duplicate installation and existing rows are never updated or replaced.

The installer always creates 12 fictional accounts and three VIP examples: two active and one expired. When their corresponding modules are active it also creates News, Pages, Downloads, Web Links, threaded Comments with reactions, Polls with votes, Friends/pending/block examples and Private Messages. Notifications receive ordinary Friends and Private Messages events. Search uses its normal providers, and Statistics derives totals from ordinary records.

No Wiki dataset is installed. No Blocks are created or changed.

## Demo accounts

Every fictional account initially uses this development-only password:

```text
NovaDemo!2026-Explore
```

The accounts are marked to require a password change at sign-in. Their email addresses use the reserved local demonstration domain `demo.novanuke.test`; no real person or mailbox is represented.

Example roles include Administrator, Editor, Moderator and Member. The dataset also demonstrates Public, Member and VIP content. `LinusTorvaldo` and `GraceHopperX` receive active VIP access; `ScriptKiddie42` has an expired VIP record.

## Module coverage

Demo content is inserted only through active module services and repositories. Consequently, modules that are inactive when the dataset is installed receive no demo records. Install and enable the desired modules before installing the dataset.

Downloads are safe external HTTPS references to official documentation; the module does not generate misleading executables or write arbitrary files. Web Links likewise use valid HTTP/HTTPS destinations.

## Current lifecycle

NovaNuke currently provides **Install** only. The ownership tables `demo_content_datasets` and `demo_content_items` prepare a future controlled **Remove** or **Reset** operation. Do not delete records by hand if you intend to use that future lifecycle.

If installation fails, NovaNuke attempts to remove only records already recorded as belonging to this dataset. Existing site content is not selected for cleanup.

## Production warning

Do not enable Demo Content on a public production site. If it was installed while developing a site, remove the development database or wait for the controlled removal feature before deploying that database. At minimum, disable the module and ensure none of its known fictional accounts can authenticate.

## Future removal/reset design

A future implementation can use the ownership tables to preview affected records, remove them in dependency order and optionally reinstall the same dataset version. That operation must remain Super-Administrator-only, require POST + CSRF + explicit confirmation, create an Activity Log entry and never infer ownership from names or slugs.
