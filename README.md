# Sahu Enterprises

A single Laravel 13 application for Sahu Enterprises, an Indian DSA and loan-assistance business, with an Inertia React and strict TypeScript public website. It covers enabled vehicle finance assistance, a calculator library, customer enquiries, attributed finance headlines and financial education resources.

## Stack

- PHP 8.3+, Laravel 13, Eloquent, Laravel Mail/Cache/Scheduler
- React 19, TypeScript strict mode, Inertia.js 3, Vite 8 and Tailwind CSS 4
- SQLite for local development; MySQL for production

## Local setup (PowerShell)

```powershell
composer install
npm install
Copy-Item .env.example .env
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite | Out-Null }
php artisan key:generate
php artisan migrate
npm run dev
```

In another terminal, run `php artisan serve`. For a production asset bundle run `npm run build`.

## Enquiries and email

The `/apply` and `/contact` forms post to Laravel and store submissions after server-side validation and required consent. Personal fields and contact message text are encrypted at rest. Configure SMTP and `SAHU_ENQUIRY_TO` in `.env` to deliver notifications; default local mail is written to the Laravel log. Set a correct `APP_URL`, manage the privacy notice/version and define the business's data retention policy before launch.

Public responses include baseline security headers for framing, MIME sniffing, referrer sharing and browser permissions. The database seeder intentionally creates no sample users or customer data.

## Finance news

The site ingests official PIB press-release headlines and displays headline, publisher, category, date and a direct link. Run one import with `php artisan news:ingest-pib`. In local development, `php artisan schedule:work` runs the hourly import; production must invoke `php artisan schedule:run` each minute. Set `PIB_NEWS_ENABLED=false` to pause the importer. See [NEWS_INTEGRATION.md](NEWS_INTEGRATION.md) for reuse limits, RBI permission status and source/API setup.

In local `APP_ENV=local`, clearly labeled educational fixtures appear when no live headline is available. Other environments show a truthful empty state.

## Enquiry operations

On an authorized server shell, `php artisan enquiries:list` shows recent references, category and follow-up status without customer contact information. Use `php artisan enquiries:show SAH-...` only when an authorized operator needs to review that record. Update follow-up with `php artisan enquiries:status SAH-... contacted` (or `new` / `closed`). Protect shell access and do not copy PII into tickets or logs.

## Calculator library

`/calculators` lists general and product-context fixed-term EMI tools, balance-transfer comparison, down-payment, tenure comparison and amortization. Revolving credit and overdraft are not modeled as standard term loans. Every estimate is illustrative; a lender sets actual terms.

## Checks

```powershell
php artisan test
npm run typecheck
npm run build
npm run test:e2e
```

The Playwright suite expects the local Laravel server at `http://127.0.0.1:8000` and `APP_ENV=local` for the labeled fixture test.

## Deployment

Deploy this Laravel application as one unit. Point the web server document root to `public/`, enable HTTPS, set `APP_DEBUG=false`, configure MySQL, SMTP, cache and `SAHU_ENQUIRY_TO`, then run `php artisan migrate --force` and `npm run build` during release. Configure the scheduler and queue worker. No production host or custom domain is configured; see `HOSTING_SETUP.md`, `DOMAIN_SETUP.md` and `DEPLOYMENT.md`. SSR is not configured; the current Inertia app uses client rendering. Review [PRE_LAUNCH_CHECKLIST.md](PRE_LAUNCH_CHECKLIST.md) before accepting enquiries.
# Admin and CRM

The admin workspace at `/admin/login` includes role-gated enquiry, follow-up, task, application, staff, scoped customer profile, product, partner, news, education content, business settings, notifications, reporting, and audit-log screens. Apply migrations, run `php artisan crm:index-enquiries` for an existing database, then provision the first Super Admin with `php artisan admin:create` and enroll MFA at first sign-in. No administrator or sample customer is seeded by default. Read [ADMIN_SETUP.md](ADMIN_SETUP.md), [ADMIN_AUTHENTICATION.md](ADMIN_AUTHENTICATION.md), and [CRM_WORKFLOWS.md](CRM_WORKFLOWS.md). The master prompt is not yet fully complete and there is no production deployment; see [ADMIN_TEST_REPORT.md](ADMIN_TEST_REPORT.md) for open work.
