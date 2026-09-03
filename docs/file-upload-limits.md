# File upload limits

The application applies feature-specific validation to uploaded files. Loan
supporting documents allow up to 10 files per request and up to 5 MB per file.

The web-server/PHP transport limits must be higher than the largest valid
aggregate request so Laravel can return normal validation errors. The project
ships these PHP settings in `public/.htaccess` (Apache module) and
`public/.user.ini` (PHP-CGI/FastCGI):

```ini
upload_max_filesize = 10M
post_max_size = 64M
max_file_uploads = 20
```

These transport settings do not increase application-level limits for other
upload areas.

If production uses Nginx or another reverse proxy, configure its request-body
limit to at least 64 MB as well. For Nginx, set `client_max_body_size 64M;` in
the applicable server block and reload Nginx. A proxy with a lower limit will
still return HTTP 413 before PHP or Laravel receives the request.

Some PHP-CGI/FastCGI installations cache `.user.ini` values for several
minutes. Restarting PHP-FPM applies the change immediately.
