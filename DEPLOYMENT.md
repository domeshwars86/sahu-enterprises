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
# Admin and CRM release notes

Deploy the additive CRM migration with a database backup in place: `php artisan migrate --force`. Never reset the production database. For an existing enquiry table, run `php artisan crm:index-enquiries` after migration to backfill exact-search fingerprints and one enquiry-linked customer record per existing enquiry. Create the owner account securely with `php artisan admin:create`; the command does not have default credentials. Configure SMTP for password resets and use HTTPS with secure session cookies. The privileged first login requires TOTP MFA enrollment.

The local admin portal now covers authentication, MFA, role-gated dashboard/enquiry workflows, follow-ups, tasks, loan application tracking, staff, customers, products, partner records, sourced news, reports, and audit logs. This does not mean a production deployment has occurred. Review `ADMIN_TEST_REPORT.md` for unconfigured services and remaining product gaps before launch.
