# Pre-launch checklist

## Business and legal review

- [ ] Confirm enabled loan assistance categories, geographic service area and current lending partner relationships.
- [ ] Supply verified business phone, WhatsApp, email, address, office hours, registration details and grievance escalation contact.
- [ ] Review privacy notice, terms, disclaimer, grievance and enquiry consent wording with the business/legal reviewer.
- [ ] Confirm the privacy notice version and retention/deletion policy for encrypted enquiry data.

## Production operations

- [ ] Configure production MySQL, durable cache, SMTP credentials, `SAHU_ENQUIRY_TO`, HTTPS, backups and error monitoring.
- [ ] Configure Laravel scheduler (`schedule:run` each minute); verify hourly PIB ingestion and alert on repeated failures.
- [ ] Set `PIB_NEWS_ENABLED` according to the source permission and current business needs.
- [ ] Obtain RBI's written permission before enabling its feed or internal-page links; obtain and configure a commercial API license before adding paid sources.
- [ ] Run the production build and all PHP/browser tests against the release candidate.
- [ ] Complete final keyboard, screen-reader, visual and responsive review on target browsers/devices.
- [ ] Verify metadata/canonical URLs, sitemap/robots policy, production `APP_DEBUG=false`, key management and security headers.
- [ ] Verify `/videos`, `/financial-resources`, all `/calculators/*` pages, and that unknown URLs return 404.
- [ ] Protect server-shell access to `enquiries:show`; list output should remain PII-minimized.
- [ ] Confirm SMTP delivery end-to-end with a controlled test address before accepting public enquiries.

## Launch decision

- [ ] Business owner approves the verified content and the release candidate.
