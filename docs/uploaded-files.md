# Uploaded-file lifecycle

All application uploads are stored on Laravel's `public` disk under
`storage/app/public`. The public disk is configured to throw on write failure,
so a database record is not silently saved with a missing file path.

## Display and retrieval

- Public-facing images use `/media/{path}` and are streamed by Laravel. This
  removes the deployment dependency on a `public/storage` symbolic link.
- The public image endpoint only permits the application image directories and
  verifies that the stored file has an image MIME type.
- Member documents, member custom-field files, loan attachments, and loan
  custom-field files use authenticated, branch-aware controller routes. They
  are not exposed by the public image endpoint.
- ID-card images are embedded from storage as data URLs and therefore do not
  depend on a public filesystem link.
- Backup downloads remain on the configured backup disk and use their
  dedicated authenticated download action.

## Server requirements

The PHP/web-server account must have read and write access to
`storage/app/public` and Laravel's other writable storage directories. A
deployment should run `php artisan optimize:clear` after changing filesystem
or URL configuration.

The traditional `php artisan storage:link` link may still be created for
compatibility, but current application upload/view flows do not rely on it.
No migration, move, rename, or deletion of existing uploaded files is required
by this change.
