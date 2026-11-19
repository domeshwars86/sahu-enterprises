# Deployment

Deployment has not occurred: this workspace contains no configured production host, domain, remote or deployment credentials. No public URL is available to verify.

## Release procedure once hosting is provisioned

1. Configure the canonical HTTPS `APP_URL`, production `APP_ENV`, `APP_DEBUG=false`, production-generated `APP_KEY`, MySQL, SMTP, `SAHU_ENQUIRY_TO`, and cache/queue settings through the hosting secret manager.
2. Build from the release commit: `composer install --no-dev --prefer-dist --optimize-autoloader`, `npm ci`, `npm run typecheck`, `npm run build`.
3. Back up the database and verify restore procedure before schema changes.
4. Run `php artisan migrate --force`, then `php artisan optimize` and reload PHP workers.
5. Start/reload supervised queue workers if used; configure the one-minute scheduler trigger.
6. Check the HTTPS homepage, activated loan routes, `/videos`, `/financial-resources`, `/sitemap.xml`, 404 behavior and headers.
7. Submit a controlled enquiry/contact to an approved test mailbox, confirm persistence and email, then remove test records using the approved retention procedure.
8. Check logs/health, backup completion, scheduler execution, certificate renewal and rollback readiness.

Never report a deployment as successful until the public host is reachable and smoke checks pass. The local build is not proof of a live deployment.
