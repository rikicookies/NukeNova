# Media library

NovaNuke 0.1.0-alpha.17 adds the optional Media module. Install and enable it from `/admin/modules`; authorized users can then manage reusable images at `/admin/media`.

The first version intentionally accepts images only: JPEG, PNG and WebP up to 10 MB and 12,000 pixels per dimension. NovaNuke verifies the upload result, server-detected MIME type, decoded image metadata, extension, size and dimensions. It generates the public filename and stores it below `public/uploads/media/YYYY/MM/`. Original filenames are metadata only.

The upload directory contains an Apache rule that disables directory indexes, handlers and executable extensions. Nginx deployments should retain the upload-deny location documented in `docs/PRODUCTION.md`. PHP must be able to create and write `public/uploads/media/`.

News and Pages offer uploaded paths in their image fields when Media is active. Their `media.usage.checking` listeners prevent deletion while any content row, including a soft-deleted one, still references an image. Future modules can protect their references by listening to that event and calling `add()` with a stable source name and count.

Back up `public/uploads/` along with the database. Disabling or uninstalling the module without deleting its data does not remove uploaded files. The current module uninstall process does not delete physical media automatically; removing orphaned files remains a deliberate administrator operation.
