# Security checklist

## Implemented in the application

- Laravel CSRF handling and server-side Form Request validation for public write endpoints.
- Rate limits, contact honeypot, consent capture and idempotency keys.
- Encryption casts for enquiry/contact personal fields and message bodies.
- No seeded sample users or customer records.
- PIB requests use bounded time/size, HTTPS host allowlist and safe XML parsing; public news pages show attributed titles and source links.
- Standard response headers for MIME sniffing, framing, referrers and browser permissions.
- Public route props exclude secrets and customer records; unknown pages return HTTP 404.
- Enquiry list command does not show PII; single-record CLI review is only for authorized operators with server shell access.

## Required before launch

- Set a production-only `APP_KEY`; restrict secret access and never commit `.env`.
- Enforce HTTPS, secure session cookies, `APP_DEBUG=false`, least-privilege database and host accounts.
- Configure business-owned mail, recipient restrictions, SPF/DKIM/DMARC and test delivery.
- Approve privacy notice, lawful purpose, contact consent, processor arrangements and a deletion/retention schedule. Encryption does not replace these controls.
- Encrypt and access-restrict backups; test restore; define incident response and credential rotation.
- Review dependencies and run production security checks. Configure monitoring without placing customer PII in logs.
- Obtain permission for sources with reuse restrictions and licenses for commercial feeds before enabling them.
- Complete HTTPS, keyboard/accessibility and release smoke checks on production.
