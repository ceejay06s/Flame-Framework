# Production-readiness execution checklist

This is the handoff/status document for continuing the work described in [production-readiness-plan.md](./production-readiness-plan.md). Check items only after implementation and verification. An unchecked operational item may require a repository owner, hosting administrator, or production-service access and cannot be completed by editing code alone.

## Continuation instructions

For the next contributor or LLM:

1. Read this file and [the full plan](./production-readiness-plan.md) before changing code.
2. Inspect `git status` and preserve existing user changes.
3. Resume at the first incomplete phase that is not marked **Blocked**. Do not mark an item complete based only on documentation.
4. Ask the repository owner for the decisions listed under [Owner decisions](#owner-decisions-required) before making compatibility-breaking choices.
5. Run `php -n tests/run.php` and PHP syntax checks after PHP changes. Use the project's configured PHP version as well once its `php.ini` is diagnosed.
6. Never use live production credentials or data in tests. Report commands and actual outcomes; do not claim unrun tests passed.
7. Update this checklist with files changed, verification results, blockers, and the next safe task before handing work off.

## Current checkpoint

- **Current phase:** Phase 0 — containment (partially complete); Phase 1 — test foundation (started).
- **Safe development runtime found:** PHP 8.2.29 at `C:\wamp64\bin\php\php8.2.29\php.exe`.
- **Known validation:** `php.exe -n tests\run.php` passed 5 tests; `php.exe -n -l` passed on the touched PHP files.
- **Runtime caveat:** Invoking this PHP executable with its normal WAMP configuration did not return promptly in this environment. `-n` runs successfully, but omits normal `php.ini` extensions/settings. Diagnose the WAMP configuration before relying on database, Composer, cURL, or sockets tests.
- **No production deployment has been tested.**

## Phase 0 — Contain exposure and define the release target

- [ ] Inspect repository history and CI/deployment settings for secrets without copying discovered values into reports; rotate any credentials that were committed or exposed.
- [ ] Record approved PHP version, OS/container, web server, database engine/version, deployment model, and required integrations. See [Owner decisions](#owner-decisions-required).
- [ ] Select and test a currently supported PHP 8.x target, then update the documented minimum only after dependency compatibility is verified.
- [x] Make the standalone WebSocket test CLI-only, explicitly opt-in, loopback-only, and payload-redacted in [`systems/console/test.php`](../systems/console/test.php).
- [x] Stop printing WebSocket message payloads and per-second idle messages in [`systems/console/WebSocket.php`](../systems/console/WebSocket.php).
- [x] Add Apache rewrite deny rules for application, system, console, docs, tests, dotfiles, and root manifests in [`.htaccess`](../.htaccess).
- [x] Add a `public/` Apache front-controller rule and disable directory listings in [`public/.htaccess`](../public/.htaccess).
- [x] Disable displayed errors/startup errors and enable PHP error logging in [`app/config/core.php`](../app/config/core.php).
- [ ] Verify public-file denial and route rewriting against the actual production web-server configuration. Apache `.htaccess` rules do not secure Nginx or servers with `AllowOverride` disabled.

**Phase status:** Partial. Repository changes above are in place; history review, secret rotation, hosting decisions, and deployed web-server verification require owner/administrator action.

## Phase 1 — Repeatable quality and security checks

- [x] Add a dependency-free CLI test entry point in [`tests/run.php`](../tests/run.php).
- [x] Cover JWT normal/tampered/expired cases, cron matching/invalid expressions, scheduler duplicate-run suppression, and basic secure session settings.
- [x] Verify the initial suite on PHP 8.2.29 with `-n`: 5 passed, 0 failed.
- [x] Fix a pre-existing parse error in [`systems/Libs/Mail.php`](../systems/Libs/Mail.php) found by the project syntax check.
- [ ] Add a selected PHP test framework and expand unit coverage after the supported PHP target is chosen.
- [ ] Add isolated PDO integration tests for each officially supported database driver.
- [ ] Add CI for syntax, tests, dependency auditing, secret scanning, and supported PHP versions.
- [ ] Add static analysis and code-style checks with agreed baselines.
- [ ] Verify dependency installation and security audit with the normal PHP/Composer environment.

**Phase status:** Started; current five-case runner is only a smoke-test foundation, not a complete automated test suite.

## Phase 2 — Harden HTTP entry, routing, and request handling

- [x] Replace unconstrained URI-derived controller/action dispatch with explicit route registration for the sample app.
- [x] Restrict dispatch to configured public, non-static controller actions declared on the concrete controller; add GET/POST route method constraints.
- [x] Normalize paths and reject traversal, encoded separators, control characters, and unknown routes; return 404/405 for unknown paths/methods.
- [x] Restrict `Router::redirect()` to local targets and reject open redirects/header injection.
- [x] Add unit coverage for route matching, method restrictions, duplicates, unsafe paths, and redirect validation.
- [ ] Add centralized production-safe error handling with protected, sanitized logs.
- [ ] Add request validation, payload limits, output encoding, and safe content-type handling.
- [ ] Add CSRF protection for browser/session state changes and configure CORS with explicit origin allowlists.
- [ ] Add security headers and a tested per-route authentication/authorization policy.

**Phase status:** Partial. Dynamic convention-based routes were intentionally removed after the owner accepted breaking changes. Sample home/login/register routes are explicit; register any application-specific route before deployment.

## Phase 3 — Harden sessions, authentication, and identity integrations

- [x] Enable strict session mode, cookie-only sessions, disable URL session IDs, set HttpOnly/SameSite=Lax, and allow secure-cookie/path configuration in [`systems/Libs/Session.php`](../systems/Libs/Session.php).
- [x] Add smoke tests for session flags and falsey session values.
- [ ] Confirm production `SESSION_COOKIE_SECURE=1` when TLS terminates at a trusted reverse proxy; do not infer TLS from untrusted forwarded headers.
- [ ] Set and test session idle/absolute expiry, logout, and privilege-change invalidation.
- [ ] Implement login/token rate limiting with a storage strategy suitable for the deployment.
- [ ] Design backward-compatible password rehash/migration. Do not rotate `APP_KEY` or invalidate existing password hashes without an approved migration.
- [ ] Remove or redesign remember-me tokens as revocable, hashed, expiring credentials before enabling the feature.
- [ ] Verify OAuth grant configuration, client handling, redirects, revocation, and token response behavior against the installed locked dependency.
- [ ] Decide whether Firebase authentication is in scope; implement server-side verification if selected.

**Phase status:** Partial; secure cookie/session defaults are in place, but identity lifecycle and compatibility work remains.

## Phase 4 — Database correctness, migrations, and operational safety

- [ ] Choose the production database/PDO driver and test connection settings with its real supported driver.
- [ ] Add versioned schema migrations and deployment/rollback procedures.
- [ ] Separate runtime and migration DB accounts; verify least privilege.
- [ ] Audit query call sites for untrusted SQL/identifier concatenation.
- [ ] Add transactions and test atomicity for multi-step writes.
- [ ] Verify model CRUD behavior and driver-specific queries against disposable integration databases.
- [ ] Define constraints, indexes, data retention, and audit requirements.
- [ ] Configure encrypted backups and perform a restore drill.
- [ ] Redact sensitive SQL parameters and personal data from logs/errors.

**Phase status:** Not started. Existing connection/query helpers do not constitute a migration or operations strategy.

## Phase 5 — Production deployment and runtime hardening

- [ ] Configure HTTPS, web-server body/time limits, trusted proxy handling, and HSTS only after HTTPS is proven.
- [ ] Deploy with `public/` as the only document root; implement equivalent access denials for the chosen server.
- [ ] Restrict service account, filesystem writes, PHP-FPM access, and network egress.
- [ ] Configure supported production PHP patch version, protected error logs, and OPcache where appropriate.
- [ ] Add health checks, monitoring, alerting, log retention/access rules, and release rollback.
- [ ] Pin frontend dependencies and define client build/release process if those clients are used.

**Phase status:** Not started; depends on hosting/provider decisions.

## Phase 6 — Optional integrations and launch sign-off

- [ ] Decide which optional integrations are enabled for the first release.
- [ ] Test mail against a controlled service, including timeout, errors, TLS, sender identity, and secret redaction.
- [ ] Test scheduled task time zone/DST, idempotency, lock semantics, retries, and multi-host behavior.
- [ ] Keep the current WebSocket implementation disabled externally until authentication, origin checks, frame validation, size/rate limits, error handling, and supervision are implemented and tested; otherwise replace it with a maintained server.
- [ ] Test frontend CORS, authentication, authorization, error handling, and secret isolation.
- [ ] Complete the staging test plan in [`testing-and-requirements.md`](./testing-and-requirements.md).
- [ ] Complete final review, record accepted residual risks, verify restore evidence, and obtain launch approval.

**Phase status:** Not started.

## Owner decisions required

The following are blockers to safe compatibility-breaking work:

1. Production PHP version and hosting/runtime platform.
2. Production database engine/version and which drivers must remain supported. **Owner selected MySQL; version is not yet specified.**
3. Whether deployed users/password hashes/sessions must remain compatible. **Owner allows breaking changes generally; confirm existing user data migration before changing hashes or sessions.**
4. Which of OAuth, JWT, Firebase, SMTP/cURL mail, scheduler, and WebSockets are required at launch. **Not decided.**
5. Existing routes/API contracts that must not change. **Owner accepts breaking changes; confirm route inventory before deployment.**
6. TLS termination/proxy topology and session-cookie requirements.
7. Backup retention and recovery targets.

## Handoff log

### 2026-11-22 — Initial implementation

- Updated the WebSocket test script to require CLI, explicit opt-in, valid loopback address, and a non-privileged configurable port. Removed received-message dumps and the idle-loop log spam.
- Added Apache root deny rules and `public/.htaccess` front-controller rules. These require verification with the actual Apache configuration and do not replace Nginx/provider rules.
- Disabled displayed PHP errors and enabled error logging in core configuration.
- Hardened PHP session startup with strict mode, cookie-only behavior, HttpOnly, SameSite=Lax, configurable secure cookies, and configurable session storage path.
- Fixed a pre-existing nested-method parse error in the cURL mail transport after source lint exposed it.
- Replaced request-derived route dispatch with an explicit route allowlist and method constraints; added path normalization and route tests. The sample app routes are in `app/config/router.php`.
- Added `tests/run.php`; latest PHP 8.2.29 `-n` run: **9 passed, 0 failed**. PHP syntax lint passed for the latest routing changes and `git diff --check` passed.
- No dependencies were installed; Composer and the normal WAMP PHP configuration were not verified.
- Next: resolve the PHP configuration/runtime, add CI and broader tests, and gather remaining owner decisions for production hosting, password compatibility, and integrations.
