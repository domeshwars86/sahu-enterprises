# Admin authentication and account security

- Laravel session authentication protects `/admin/*`; there is no public registration.
- Sign-in checks active status and an assigned admin role, regenerates the session ID, and rate-limits attempts by normalized email and IP.
- Logout invalidates the session and regenerates the CSRF token.
- Privileged `Super Admin` and `Admin` roles must enroll a TOTP authenticator and verify a code at sign-in. Codes use the RFC 6238 time-based format; no third-party QR service receives the secret.
- Sign-in, sign-out, failed sign-in, MFA enablement, and MFA results are audit events. Metadata uses keyed fingerprints; passwords and reset tokens are not logged.
- Use `/admin/forgot-password` to request a reset link. Laravel's password broker sends the message through the configured mail provider. Keep reset tokens short-lived and configure a secure production mail sender.
- Staff accounts are provisioned by an administrator at `/admin/users`. Deactivated accounts are rejected at sign-in and on subsequent protected requests.

## Recovery

An owner who loses the authenticator should use the trusted server/database recovery process and preserve an active Super Admin. The application does not currently issue recovery codes or provide an in-app MFA reset flow. Do not delete the last Super Admin.

Use HTTPS, secure session cookies, a durable protected session store, backups, and production `APP_DEBUG=false`. The application does not currently enforce a separate configurable idle timeout beyond the MFA re-verification window.
