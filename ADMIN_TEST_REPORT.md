# Admin test report

## Automated checks

Admin tests cover:

- Session login/logout, inactive staff, protected routes, and role/permission boundaries
- Mandatory MFA enrollment for privileged roles, RFC 6238 code generation, and security checks
- Agent and Sales Manager scoping, masked fields, customer profile history, communication preference, and privacy-request audit events
- Status history, encrypted notes, scheduled follow-ups, task and application workflows, and compatibility with `enquiries:status`
- Product visibility management and privacy-safe report export
- Article, FAQ and video publishing; strict YouTube host validation; business setting updates; notification ownership; one-time due-work reminders

Most recent full suite: **48 tests passed, 407 assertions passed**. `npm run typecheck`, `npm run build`, and `vendor/bin/pint --dirty --format agent` passed after the latest changes.

CRM migrations were applied locally; 13 existing enquiries were indexed without merging or deletion. The five configured products were synchronized to the local database. Existing enquiry shell commands remain available. Route discovery lists 68 admin routes.

## Not yet verified or implemented

- Browser/mobile QA and accessibility review could not run: the browser tool timed out connecting to the local server, and Playwright timed out waiting for its configured local web server before tests started.
- Production MySQL migration, SMTP/password-reset delivery, durable session/cache configuration, scheduler operation, and deployment need production infrastructure and credentials.
- Logo upload, configurable loan categories/disclosures, product creation plus product-level FAQs/SEO/related products, richer partner contact/product mapping, article search/filter/preview and homepage sections, and video thumbnail controls are not implemented.
- Reports support date/category/status/source/staff/lender filters and daily/weekly/monthly aggregates, application stages, follow-up completion, source, lender submissions and status funnel. Agent performance is assigned enquiry volume rather than a full conversion/response-time scorecard.
- Privacy requests can be recorded with an audit event and timestamp; legal response/retention/deletion workflow requires business-approved policy and is not automated.
- Email reminders are optional for due work when configured; there is no SMS/WhatsApp provider integration or lender API.

The CRM platform is implemented and the PHP suite, TypeScript check, frontend production build, and Pint pass. It is not 100% complete against the master prompt: product/content/settings gaps above, browser QA, and production setup remain outstanding. Do not describe it as production-ready until those are resolved.
