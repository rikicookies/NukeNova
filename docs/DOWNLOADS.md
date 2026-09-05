# Downloads module

Downloads is an optional module for local private files and tracked external resources. Install and enable it at `/admin/modules`, then manage it at `/admin/downloads`.

## Storage and uploads

Local files are stored below `storage/private/downloads`, outside the public document root. The public URL never reveals the generated storage name. A controller verifies publication and viewer access before streaming the file with attachment, no-sniff and private no-store headers.

The first release accepts ZIP, PDF, TXT, PNG, JPEG and WebP files up to 50 MB. Validation uses all of the following:

- PHP upload status;
- server-reported byte size;
- allowed extension;
- MIME detected with Fileinfo;
- server-generated random storage name;
- administrator permission;
- real-path containment during delivery.

Browser-supplied MIME values and original paths are ignored. PHP code and unlisted extensions are rejected. Ensure PHP's `upload_max_filesize` and `post_max_size` are at least 50 MB if that maximum is required.

Replacing a local file, switching to an external source or soft-deleting a download does not immediately erase the old private file. This conservative behavior avoids destructive data loss. After creating and safely exporting both database and file backups, inspect unreferenced files with:

```bash
php bin/cms downloads:orphans
```

The default is a non-destructive dry run. Files still referenced by any download row—including soft-deleted rows—are retained. Generated files less than 24 hours old, symbolic links and filenames not matching NovaNuke's generated format are also protected. Delete only the eligible set with `php bin/cms downloads:orphans --delete` after reviewing the dry run.

## Publication and access

`downloads.manage` permits catalog management and drafts. `downloads.publish` is required to publish immediately or schedule a future release. Downloads may be public, available to any member, or restricted to selected roles. Access is enforced again at the delivery route.

External sources accept only HTTP and HTTPS URLs without embedded credentials or control characters. NovaNuke redirects the visitor and sends a `no-referrer` policy; it does not fetch the remote file on the server.

## Catalog

Public routes include:

- `/downloads` for newest items;
- `/downloads?order=popular` for popular items;
- `/downloads?order=name` for alphabetical ordering;
- `/downloads?q=term` for MySQL-backed name, description and author search;
- `/downloads/category/{slug}` for a category;
- `/downloads/{slug}` for details;
- `/downloads/{slug}/get` for controlled delivery.

Categories may have a parent. Featured status, version, author, license, requirements, image and publication date are displayed in the catalog or detail screen.

## Counting and reports

A keyed hash derived from the signed-in user or the guest IP/user-agent combination prevents an obvious repeated count for 24 hours. Raw visitor identity is not stored in module event rows. This reduces simple refresh inflation but is not presented as perfect analytics or fraud prevention.

Broken-download reports require CSRF, 5–500 characters, one report per identity/download and persistent rate limiting. Administrators resolve open reports from `/admin/downloads`.

Successful delivery dispatches `download.completed` with a `DownloadCompleted` value containing the download ID and whether the counter increased.
