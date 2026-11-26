# Backend progress

## Complete

- Laravel 13 application with Inertia React pages in one deployable project.
- Loan enquiry endpoint with server-side validation, consent/privacy version capture, encrypted personal fields at rest, idempotency, throttling, generated reference numbers and configured email notifications.
- Contact form endpoint with validation, consent, encrypted personal fields and message body, honeypot protection, idempotency, throttling and company email notifications.
- PIB RSS ingestion command with bounded request time, response-size and item limits, safe XML parsing, approved host allowlist, finance-category filtering, URL-based deduplication and failure logging.
- Published headline/category/detail props, five-minute cache, hourly overlap-protected scheduler, and source-attributed title-only rendering.
- SQLite local setup and migrations; production MySQL and SMTP can be configured through Laravel environment settings.
- Feature coverage for enquiry persistence, validation, idempotency, encryption, mail notifications, news filtering, ingestion and source attribution.
- Baseline security response headers for MIME sniffing, framing, referrers and browser permissions; the default seeder creates no sample users or customer records.
- Enquiry status tracking and authorized-shell operational commands: privacy-minimized list, single-record view and validated status update (`new`, `contacted`, `closed`).
- Enabled product catalogue and verified public business settings are supplied from environment-driven config, with inactive categories excluded from public product props.
- Verification completed after these changes: seven local migrations are applied. See `TEST_REPORT.md` for the current suite results and environment notes.
- Role-gated admin access with rate-limited authentication, password reset flow, TOTP MFA for privileged roles, staff management, and audited security events.
- Enquiry CRM with assignment, scoped access, masked PII for restricted roles, encrypted notes, status history, scheduled follow-ups and dashboard metrics.
- Task assignment, explicit loan application tracking with stage history, scoped customer profiles with enquiry/follow-up/note/status history, audited communication preferences and privacy-request capture, product visibility controls, partner records, and source-attributed news management.
- Date-filtered status/category/application reports with privacy-minimized, formula-safe CSV export, plus a searchable audit log.
- Education CMS for plain-text articles, FAQs and approved YouTube links, managed public business details, per-user in-app notifications, new-enquiry/contact/assignment/application/news alerts, and one-time scheduled follow-up/task reminders.
- HMAC exact-match lookup indexing for existing encrypted enquiries without merging customer records, exposed through `php artisan crm:index-enquiries`.
- Admin routes and React pages are included in the production build. Current local checks: `npm run typecheck`, `npm run build`, and full `php artisan test --compact` (**48 tests, 407 assertions passed**). See `ADMIN_TEST_REPORT.md` for limitations.

## Production configuration still required

- Set production MySQL, durable cache, HTTPS, verified sender address, SMTP and `SAHU_ENQUIRY_TO`; default local mail is `log` and does not deliver customer notifications.
- Configure the server scheduler to invoke `php artisan schedule:run` every minute and monitor Laravel logs.
- Supply verified business identity, contact, service and grievance details, and approve privacy/legal copy.
- Confirm and document the business's personal-data retention period before collecting real customer enquiries.
- Keep RBI ingestion disabled unless written permission is obtained; configure a commercial feed only with an approved license and credentials.
- Production MySQL migration, SMTP/password-reset delivery, durable session/cache configuration, scheduler, and deployment remain unverified. No production host, domain, or deployment credentials are configured.
- Pint completed successfully. Browser/mobile QA remains unverified: the browser tool timed out and Playwright could not start its local web server in this sandbox.
- SMS/WhatsApp reminders, lender API integration, automated approvals, and legal privacy fulfilment are not implemented. Customer identities are intentionally not merged.
- Product creation and per-product FAQs/SEO/related products, logo upload, business-approved disclosures, rich-text editing/search/preview, homepage feature editing, and full agent conversion reports remain incomplete.
