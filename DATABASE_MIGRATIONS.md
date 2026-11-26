# Database migrations

The CRM migration is additive and preserves existing users and enquiry rows. It adds admin roles and account-security columns, exact-search fingerprint columns, customer profiles, enquiry history/notes, follow-ups, tasks, applications, audit logs, products, partners, content tables, settings, and database notifications.

## Safe rollout

1. Back up the production database and verify the restore procedure.
2. Confirm the release's migration status and database compatibility.
3. Deploy code and run `php artisan migrate --force` during the approved release window.
4. Check that prior enquiry references and row counts remain intact.
5. Run `php artisan crm:index-enquiries` to backfill missing HMAC lookup values and create one separate customer profile per enquiry. It does not merge uncertain identities or delete records.
6. Provision the first administrator with `php artisan admin:create` and verify MFA enrollment.

Never run `migrate:fresh`, `db:wipe`, or a destructive reset against business data. Do not rotate `APP_KEY` without planning both decryption and lookup-index re-generation. The migration's `down()` is intended for disposable environments only; production rollback requires a reviewed recovery plan.
The education CMS adds the `educational_faqs` table for managed public question-and-answer content. Apply it with `php artisan migrate --force`; it is additive and does not alter enquiry records.
