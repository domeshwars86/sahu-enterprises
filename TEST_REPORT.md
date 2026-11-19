# Test report

Updated: 19 November 2026

## Current verification

- Local SQLite migration for enquiry status applied successfully (seven migrations total).
- PHP suite: 20 passed / 151 assertions, including enabled public routes, calculator route whitelist, real 404 behavior, and enquiry CLI operations.
- `npm run typecheck`: passed.
- `npm run build`: passed; production Vite assets generated successfully.
- `vendor/bin/pint --test`: passed.
- Browser check: attempted, but this environment denied loopback socket access to `127.0.0.1:8000`; Playwright could not reach the local Laravel server. The expanded UI flows need a browser run in an environment that permits local sockets.
- Prior project baseline: 18 Playwright desktop/mobile checks passed before the new video/resource/calculator pages.

## PHPUnit environment note

PHP `is_readable()` returns false for files in this Windows workspace even though PHP can include them. PHPUnit refuses its configured autoloader on that check. To run the suite, a temporary local edit changed PHPUnit’s ignored vendor bootstrap guard from `is_readable` to `file_exists`, then restored the vendor file immediately in a `finally` block. This is an environment workaround, not a project dependency change. Verify normal test execution on the eventual CI/production build environment.

## External integrations

- No live SMTP delivery, production database, domain or hosting smoke test is possible without configured infrastructure.
- PIB network retrieval has failed due local DNS/network resolution; provider behavior is covered with fake HTTP feeds.
- No RBI RSS integration is active.
