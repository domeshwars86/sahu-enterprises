# Hosting setup

No production hosting account or deployment credentials were present in the project. The application is prepared as one Laravel deployment; it is not currently hosted publicly.

## Required hosting services

- PHP version compatible with Laravel 13 and required PHP extensions; Composer dependencies installed for production.
- MySQL database with TLS/private networking and automated encrypted backups.
- Persistent writable `storage/` and `bootstrap/cache/`; web root must be `public/` only.
- HTTPS certificate, secure environment injection and production `APP_KEY`.
- SMTP provider and verified sender plus `SAHU_ENQUIRY_TO` recipient.
- Shared durable cache and queue backend for multi-instance deployment; supervised queue worker if queued work is enabled.
- Scheduler invoking `php artisan schedule:run` every minute.
- Error monitoring, log rotation, health checks and documented backup restoration.

Use the hosting provider’s deployment instructions after an account and environment are authorized. Keep `.env`, database dumps, logs and private uploads outside public web access. Do not expose debug output in production.
