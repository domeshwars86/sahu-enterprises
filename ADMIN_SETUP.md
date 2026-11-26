# Admin portal setup

## Local setup

1. Configure `.env` with a stable `APP_KEY`, database connection, session store, and mail provider.
2. Apply additive schema changes with `php artisan migrate --force`. Never use `migrate:fresh` or reset a database containing customer enquiries.
3. Create the first administrator from a trusted server console with `php artisan admin:create`. The command asks for the name, email, and a confirmed password through hidden console input. It does not print the password or create a predictable account.
4. Open `/admin/login`. A Super Admin is required to enroll an authenticator app before reaching the dashboard. Save the key in the owner's authenticator before completing enrollment.
5. Configure production SMTP before relying on password reset emails.
6. On an existing database, run `php artisan crm:index-enquiries` once after deploying the CRM migration. It backfills keyed exact-search fingerprints and creates a separate customer profile for each enquiry; it does not merge or delete enquiries.

No admin user is seeded automatically. Do not share setup keys, reset links, or passwords in tickets or logs.

## Current routes

- Login: `/admin/login`
- Dashboard: `/admin`
- Enquiries: `/admin/enquiries`
- Follow-ups: `/admin/follow-ups`
- Tasks: `/admin/tasks`
- Loan applications: `/admin/applications`
- Customers: `/admin/customers`
- Customer profile: `/admin/customers/{id}`
- Products: `/admin/products`
- Lending partners: `/admin/partners`
- News headlines: `/admin/news`
- Educational articles and videos: `/admin/content`
- FAQ management: `/admin/content`
- Public learning resources: `/resources` and `/videos`
- Business settings: `/admin/settings`
- Notifications: `/admin/notifications`
- Reports: `/admin/reports`
- Audit log: `/admin/audit-logs`
- Team management: `/admin/users`

Only authenticated, active staff with a CRM role can access the protected workspace. The project currently has no public admin registration. Article publishing, business-setting changes, customer contact preference changes, privacy-request capture, and sensitive exports are audited. The due-work scheduler creates in-app reminders; optional email can be enabled for due-work alerts with a configured mail provider. SMS/WhatsApp are not configured.

## Database deployment

Back up the production database before deployment. Review `php artisan migrate:status`, apply the additive migrations, verify the enquiry row count, and run the indexing command. Do not rotate `APP_KEY` after lookup fingerprints are generated without planning a re-index; encrypted enquiry fields also depend on the key.

The local CRM is implemented across the routes above. See `ADMIN_TEST_REPORT.md` for production setup and feature gaps that remain outside the current implemented scope.
