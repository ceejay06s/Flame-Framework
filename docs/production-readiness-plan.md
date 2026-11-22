# Production-readiness and security plan

## Goal

Prepare Flame Framework and its sample application for a controlled production release by reducing security and operational risk, defining supported environments, and verifying critical behavior with repeatable tests. This is a prioritized implementation plan, not a certification or claim that the current code is production-ready.
For live progress, verified changes, and handoff instructions, see the [production-readiness execution checklist](./production-readiness-checklist.md).

## Current-state assessment

Based on the repository as it stands, production deployment should be blocked until the critical items below are addressed and verified.

- There is no comprehensive automated PHP test suite or CI workflow. A small dependency-free CLI smoke test now covers JWT, scheduler, and session basics.
- The standalone WebSocket test was restricted to CLI, explicit opt-in, and loopback binding, and message dumps were removed. The underlying WebSocket implementation still needs a security and protocol hardening review before external exposure.
- Session strict mode, cookie-only behavior, HttpOnly, SameSite, configurable secure-cookie behavior, and session path checks have been added. Session expiry, logout, and account lifecycle controls remain incomplete.
- Authentication helpers have a very long session expiry, no visible login throttling, and no password rehash/upgrade flow. `APP_KEY` must remain stable for legacy hashes.
- Routing now uses explicit route registration for the sample app, validates paths, constrains HTTP methods, rejects external redirects, and returns 404/405 for misses. Existing application routes must be reviewed and registered; authorization policies, CSRF, centralized errors, and security headers remain incomplete.
- The application has no framework-wide request validation, CSRF, authorization middleware, security-header policy, or centralized error/exception handling.
- Database helpers bind values, but SQL passed directly to query methods remains caller-controlled. Database permissions, migrations, backup/restore, and transaction usage need application-level standards and tests.
- Optional integrations (OAuth, JWT, email, scheduler, WebSocket, and frontend clients) are not equivalent to a fully configured production implementation. Each enabled integration needs its own supported configuration and tests.
- The docs currently state PHP 7.4 as a minimum. This is an end-of-life runtime in 2026; select and test a currently supported PHP 8.x release before deployment.

## Principles and release gates

1. **Do not deploy until the Critical and High items have an owner, implementation, and passing evidence.**
2. Keep secrets out of the repository, logs, browser bundles, and exception responses. Rotate any credentials that have ever been committed.
3. Use a currently supported PHP release, least-privilege service accounts, HTTPS, and production-safe error handling.
4. Make tests repeatable and isolated. Never run destructive test cases against production.
5. Enable only integrations that the application actually uses; disable or remove unused listeners, transports, and dependencies.
6. Document rollback, backup restoration, incident contacts, and key rotation before first launch.

## Prioritized work plan

### Phase 0 — Contain exposure and define the release target

**Priority:** P0 / immediate  
**Goal:** Remove accidental exposure and establish a supported deployment baseline.

- [ ] Inspect repository history and CI/deployment settings for credentials, private keys, tokens, database dumps, and personal data. Rotate exposed credentials; removing a secret from the current file is not sufficient.
- [ ] Confirm the target PHP 8.x minor version, operating system, web server, database engine/version, deployment model, and supported frontend/runtime integrations.
- [ ] Raise the documented PHP minimum to a currently supported version after dependency and application compatibility checks. Avoid declaring support for a version that is not tested.
- [ ] Remove or clearly quarantine development-only entry points, especially `systems/console/test.php`; require an explicit loopback-only configuration for local WebSocket testing and remove payload dumps.
- [ ] Ensure only `public/` is web-accessible. Add deployment checks that reject public access to `app/`, `systems/`, `console/`, Composer files, and environment/config files.
- [ ] Set production `display_errors=Off` and `display_startup_errors=Off`; route errors to protected logs with request IDs and sanitized messages.

**Exit criteria:** No known active secret remains; test/debug endpoints are inaccessible from the public deployment; the documented platform is agreed and supported.

### Phase 1 — Establish repeatable quality and security checks

**Priority:** P0  
**Goal:** Make regressions visible before changes are deployed.

- [ ] Add PHPUnit (or another selected PHP test runner) compatible with the supported PHP baseline and create unit tests for routing, model guards, sessions, authentication helpers, JWT validation, and scheduler behavior.
- [ ] Add isolated integration tests for each supported PDO driver that the project claims to support. Use disposable schemas and test credentials.
- [ ] Add test fixtures/factories and cleanup/transaction handling; tests must not depend on a developer's real database or external email account.
- [ ] Configure CI for the supported PHP version(s): dependency install from `app/composer.lock`, PHP syntax checks, static analysis, code style, unit tests, and selected database integration jobs.
- [ ] Add dependency auditing (Composer audit and npm audit where applicable) and a process for reviewing advisories and updating lock files.
- [ ] Add secret scanning and repository protection rules to CI and hosting.
- [ ] Make test failures block release. Preserve sanitized logs and test reports.

**Exit criteria:** CI runs from a clean checkout, fails on an intentional test failure, and has passing baseline tests on the chosen PHP runtime.

### Phase 2 — Harden HTTP entry, routing, and request handling

**Priority:** P0  
**Goal:** Ensure incoming requests reach only intended public actions and receive safe, consistent responses.

- [ ] Replace request-derived controller/action dispatch with an explicit route registry or a strict allowlist. Reject invalid path segments and never map arbitrary URI text to filesystem paths or callable methods.
- [ ] Restrict route dispatch to public actions; exclude constructors, lifecycle methods, internal helpers, and inherited framework methods.
- [ ] Add HTTP method constraints and consistent 404/405 behavior. Normalize path/query parsing and test encoded, empty, repeated, and malformed segments.
- [ ] Add centralized exception handling that returns generic production responses and logs diagnostic details only on the server.
- [ ] Add request size limits, content-type checks, input validation, and output-encoding conventions for templates and JSON endpoints.
- [ ] Add CSRF protection for state-changing browser/session requests, including token generation, validation, and SameSite cookie behavior. Exempt only deliberately designed non-cookie API endpoints.
- [ ] Set security headers appropriate to the app (at minimum `X-Content-Type-Options`, a suitable `Content-Security-Policy`, `Referrer-Policy`, and frame protection). Enable HSTS only when HTTPS is fully deployed.
- [ ] Configure CORS by explicit allowed origins, methods, and headers; do not use wildcard origins with credentials.
- [ ] Add per-endpoint authentication/authorization policies and deny by default for protected operations.

**Exit criteria:** Route tests prove only registered public actions can run; malformed requests do not cause file access or framework traces; CSRF and authorization tests pass.

### Phase 3 — Harden sessions, authentication, and identity integrations

**Priority:** P0  
**Goal:** Protect accounts, session tokens, OAuth clients, and signed tokens.

- [ ] Configure sessions before `session_start()`: strict mode, cookie-only sessions, secure cookies in HTTPS deployments, HttpOnly, SameSite, and an appropriate idle/absolute timeout.
- [ ] Make session path configurable and verify restrictive directory/file permissions. Do not share file sessions across hosts without a deliberate shared session store.
- [ ] Regenerate session IDs after authentication and privilege changes; implement logout that clears server-side state and expires the cookie.
- [ ] Replace the ten-year session lifetime with documented idle and absolute expiration policies. Add reauthentication for sensitive operations.
- [ ] Add rate limiting and uniform failure behavior for login and token endpoints; add monitoring and safe lockout/backoff controls.
- [ ] Use password hashing with `password_hash()` and verify/rehash with `password_needs_rehash()`. Plan migration from the current HMAC-wrapped password hashes without breaking existing accounts. Preserve the old key only during a controlled transition.
- [ ] Remove or redesign remember-me behavior until it uses random, hashed, revocable, expiring tokens with secure cookies and rotation. Never persist bearer session tokens as long-lived reusable credentials.
- [ ] Require strong independent `APP_KEY` and `JWT_SECRET` values from a secret manager; define rotation and recovery procedures.
- [ ] For JWT, enforce allowed algorithm, issuer, audience, expiration, and key rotation policy; set maximum token lifetime and avoid sensitive data in claims.
- [ ] For OAuth, configure only necessary grant types, exact redirect URI matching, client credential handling, token lifetimes, TLS, scopes, and revocation. Test grants against the exact locked server library version.
- [ ] If Firebase tokens are supported, verify them server-side using the trusted Firebase Admin SDK or a maintained verification integration; never trust a client-supplied identity claim.

**Exit criteria:** Session fixation, cookie flags, logout, expiry, failed login throttling, password migration, token misuse, and authorization tests pass.

### Phase 4 — Database correctness, migrations, and operational safety

**Priority:** P0/P1  
**Goal:** Protect application data and make schema changes recoverable.

- [ ] Select the production database and PDO driver; test connection, TLS/encryption settings, character set, timezone, and exception behavior on the actual deployment environment.
- [ ] Create versioned application migrations. Do not rely on runtime schema creation; run schema changes as a controlled deployment step with backups and rollback guidance.
- [ ] Use least-privilege DB accounts: runtime account should not own the database or have schema-administration privileges. Use a separate migration account.
- [ ] Audit every query for parameterized values and validated identifiers. Prohibit concatenating request data into SQL, sort clauses, table names, or operators.
- [ ] Add transaction support and define transaction boundaries for multi-step writes, authentication state changes, and token issuance/revocation.
- [ ] Review model semantics (`save`, update, delete, find) against the application's actual schema and verify driver-specific behavior, including SQL Server limits and affected-row behavior.
- [ ] Define constraints, foreign keys, indexes, uniqueness, retention, and audit history for application data and OAuth tables.
- [ ] Configure automated encrypted backups, retention, restore drills, and a documented recovery point/time objective.
- [ ] Avoid logging SQL parameters or personal data; redact sensitive values from exceptions and telemetry.

**Exit criteria:** Migrations are repeatable in staging, tests pass on the production database engine, least-privilege access is verified, and a restore drill succeeds.

### Phase 5 — Production deployment and runtime hardening

**Priority:** P1  
**Goal:** Reduce host, network, and configuration risk.

- [ ] Deploy behind a maintained web server/reverse proxy with HTTPS, valid certificates, secure forwarded-header handling, and request/body/time limits.
- [ ] Keep source, Composer metadata, secrets, logs, backups, and temporary files outside the public document root. Disable directory listing and PHP execution in upload/cache directories.
- [ ] Run PHP-FPM/web workers as a non-root dedicated user. Restrict filesystem permissions and network egress.
- [ ] Use production PHP settings: errors logged but not displayed, OPcache enabled as appropriate, dangerous unused extensions/functions reviewed, and supported PHP patch versions kept current.
- [ ] Configure health/readiness endpoints that reveal no secrets or topology. Add process supervision and graceful restart procedures.
- [ ] Define structured, redacted logs, request correlation IDs, alert thresholds, uptime checks, and log retention/access policies.
- [ ] Add dependency update cadence, vulnerability triage SLAs, release notes, and rollback procedure.
- [ ] Pin frontend dependencies with lock files and build/deploy browser bundles separately. Do not expose server configuration in frontend builds.

**Exit criteria:** Staging deployment has TLS, safe error responses, monitored health, restricted filesystem/network access, and a rehearsed rollback.

### Phase 6 — Optional integrations and launch sign-off

**Priority:** P1/P2  
**Goal:** Enable only verified integrations and approve launch based on evidence.

- [ ] Email: test delivery, TLS, sender-domain authorization, bounce/error behavior, timeouts, retries, and secret redaction using a controlled mailbox/API.
- [ ] Scheduler: validate timezone/DST expectations, task idempotency, lock behavior on the deployment filesystem, timeouts, retries, monitoring, and duplicate execution across hosts.
- [ ] WebSocket: decide whether the current server is suitable; require authentication, origin policy, message size/rate limits, frame validation, TLS termination, graceful disconnects, and supervision before any external exposure. Prefer a maintained server implementation if these controls cannot be met.
- [ ] Frontend: test CORS, CSRF/token storage, authentication expiry, error handling, and server-side authorization. Keep API secrets out of bundles.
- [ ] Run the complete manual and automated checklist in `docs/testing-and-requirements.md` on a staging-like environment.
- [ ] Complete a final code/dependency/security review and record accepted risks, owners, incident response contact, backup restore evidence, and launch approval.

**Exit criteria:** Every enabled feature has passing evidence; disabled features are not reachable; unresolved Critical/High risks block release or have formal owner-approved mitigation.

## Suggested order of execution

1. **First:** secret/history review, WebSocket test isolation, supported PHP target, and production error configuration.
2. **Next:** CI and automated tests, then route allowlisting and centralized request/error handling.
3. **Then:** sessions/authentication/CSRF, followed by database migrations and least-privilege controls.
4. **Before staging:** TLS/web-server hardening, monitoring, backups, and restore drill.
5. **At staging:** enable optional integrations one at a time and complete test cases.
6. **Launch gate:** review evidence and explicitly accept only documented residual risks.

## Deliverables

- Supported runtime/deployment matrix and environment configuration reference.
- Automated test suite and CI pipeline with dependency and secret scanning.
- Versioned database migrations and rollback/restore procedures.
- Hardened route, session, authentication, error, and security-header behavior.
- Deployment runbook, monitoring/alerting rules, backup evidence, and incident-response contacts.
- Completed staging test report linked to the release.

## Decisions needed before implementation

The implementation details depend on choices that should be recorded before Phase 2:

- Target PHP version, web server, operating system/container platform, and database engine.
- Whether the framework must preserve existing user password hashes and sessions.
- Which optional systems are actually required for the first production release.
- Authentication model for browser sessions, API clients, OAuth clients, and Firebase users.
- Availability and recovery targets, log/data retention, and backup schedule.
