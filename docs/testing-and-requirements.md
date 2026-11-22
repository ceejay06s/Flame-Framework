# System requirements and test cases

This document records the runtime requirements and a manual verification plan for Flame Framework. The repository has a small dependency-free PHP smoke-test runner at `tests/run.php`, but no configured PHPUnit/Pest suite, comprehensive automated coverage, CI workflow, or npm test script. The cases below are test scenarios, not a claim that they have all been run. Run them in a development or staging environment, never against production data.

Run the current smoke tests from the repository root with:

```sh
php -n tests/run.php
```

The `-n` switch omits local PHP configuration and was used for the available smoke-test run. The runner covers basic JWT signing/validation, cron matching, scheduler duplicate-run suppression, session settings, and explicit route matching. It is not a substitute for the full integration and staging checks in this document.

## System requirements

### Core runtime

| Requirement | Minimum / notes |
|---|---|
| PHP | 7.4 or newer; PHP 8.x recommended |
| Composer | Composer 2 recommended; install from `app/composer.json` |
| HTTP runtime | Apache, Nginx, or PHP's built-in server for local development |
| Document root | The `public/` directory |
| Required PHP extensions | `json`, `session`, `hash`, and `PDO` |
| Filesystem | Write access to `app/tmp/cache/session/`; write access to `app/tmp/cron/` when running scheduled tasks |
| Secret configuration | Environment variables supplied by the shell, service manager, or deployment platform; Flame does not load `.env` files |

### Composer packages

Install the PHP dependencies from the repository root:

```sh
composer install --working-dir=app
```

Declared direct dependencies:

- `phpmailer/phpmailer` for SMTP email.
- `bshaffer/oauth2-server-php` for the OAuth 2.0 server adapter.
- `doctrine/inflector` and `icanboogie/inflector` for framework inflection helpers.

Use the committed `app/composer.lock` to reproduce locked versions. The Composer `vendor/` directory is generated and should not be committed.

### Database requirements

The configured database must exist before Flame connects. Install one PDO driver that matches the selected connection:

| `DB_CONNECTION` | PDO driver / PHP extension | Typical default port |
|---|---|---:|
| `mysql` | `pdo_mysql` | 3306 |
| `pgsql` | `pdo_pgsql` | 5432 |
| `sqlsrv` or `mssql` | `pdo_sqlsrv` | 1433 |

The database user needs only the privileges required by the application. OAuth schema creation needs table-creation privileges during the one-time schema installation step. Database server versions are deployment-specific; validate the PHP PDO driver and target server combination supported by your provider.

### Optional feature requirements

| Feature | Additional requirements |
|---|---|
| JWT | PHP `hash` extension; private `JWT_SECRET` |
| Password helpers | Private `APP_KEY`; preserve it when upgrading to keep existing hashes verifiable |
| PHPMailer SMTP | Composer install; network access to SMTP host; `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` |
| Native email | PHP `mail()` and a functioning local mail configuration |
| Sendmail | A configured Sendmail-compatible binary and writable/valid configuration |
| cURL email | PHP `curl` extension; reachable HTTPS `MAIL_CURL_ENDPOINT`; optional `MAIL_CURL_API_KEY` |
| Scheduler | PHP CLI; recurring operating-system invocation; writable scheduler lock directory |
| WebSockets | PHP CLI and `sockets` extension; an available bind address and port |
| React / Vue development | Node.js and npm; root `package.json` lists React, React DOM, and Vue, but contains no build/test scripts |
| Angular / Firebase development | Separate client project and its own framework/SDK dependencies |

### Environment variables

The `.env.example` file is a reference list; it is not loaded by the application.

| Variable | Required when | Description |
|---|---|---|
| `APP_KEY` | Password hashing/authentication is used | Private HMAC key; configure a stable, high-entropy secret |
| `JWT_SECRET` | JWTs are used | Private HS256 signing key; use a different key from `APP_KEY` |
| `SESSION_PATH` | Sessions are used | Existing writable session directory; defaults to `app/tmp/cache/session/` |
| `SESSION_COOKIE_SECURE` | Sessions are used | `true`/`false`; set to `true` when HTTPS terminates at a trusted proxy |
| `DB_CONNECTION` | Database models are used | `mysql`, `pgsql`, `sqlsrv`, or `mssql` |
| `DB_HOST`, `DB_PORT` | Database models are used | Database endpoint |
| `DB_DATABASE` | Database models are used | Existing database name |
| `DB_USERNAME`, `DB_PASSWORD` | Database models are used | Database credentials |
| `DB_CHARSET` | MySQL connection is used | MySQL connection charset; defaults to `utf8mb4` |
| `MAIL_TRANSPORT` | Email is used | `native`, `sendmail`, `phpmailer`, or `curl` |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION` | SMTP is used | SMTP connection settings |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | Authenticated SMTP is used | SMTP credentials |
| `MAIL_CURL_ENDPOINT` | cURL mail is used | HTTPS JSON API endpoint |
| `MAIL_CURL_API_KEY` | cURL API requires auth | Optional bearer token |

Supply secrets using a secret manager or environment configuration. Never use example values as production secrets or commit real credentials.

## Preparing a test environment

1. Install PHP, Composer dependencies, and the extensions for the feature set being tested.
2. Set environment variables in the process that will run PHP. Do not expect `.env.example` to be loaded automatically.
3. Create an isolated, disposable test database and any application tables required by the scenario.
4. Ensure the PHP process can write to the session and scheduler directories.
5. Start the development server from the repository root:

   ```sh
   php -S 127.0.0.1:8000 -t public
   ```

6. If testing OAuth, install the OAuth tables once in the test database and configure a test client. If testing email, use a test mailbox or a controlled mock HTTP endpoint.
7. Record the PHP version, enabled extensions, database driver/server, environment, case result, and any logs with the test run.

**Do not run schema creation, insert/update/delete cases, or scheduler callbacks against production.**

## Test cases

Each case has a setup, action, and expected result. Mark cases not enabled in the target environment as **Not applicable**, and record why.

### Installation and application startup

| ID | Scenario | Steps | Expected result |
|---|---|---|---|
| SYS-01 | PHP runtime and required extensions | Run `php -v` and `php -m`; confirm PHP 7.4+ and `json`, `session`, `hash`, `PDO`. | Runtime version is supported and required extensions are enabled. |
| SYS-02 | Composer installation | Run `composer install --working-dir=app`. | Composer exits successfully and `app/vendor/autoload.php` is generated. |
| SYS-03 | Local HTTP startup | Start the development server with the command above and request `/`. | PHP server starts and dispatches the request through the public entry point. The sample home action also requires its configured DB and `users` table. |
| SYS-04 | Missing database configuration | Start a model with `DB_DATABASE` unset. | Model fails clearly with a configuration error; it does not attempt to create a database or return fabricated results. |
| SYS-05 | Public document root | Request public CSS/JS and verify application files are not web-accessible as static paths. | Static assets under `public/` are served; app/system config and source files are not exposed by the web server. |

### Routing, controllers, views, and JSON

| ID | Scenario | Steps | Expected result |
|---|---|---|---|
| WEB-01 | Convention-based controller dispatch | Request a known route such as `/user/login` with valid local dependencies. | The expected controller action executes. |
| WEB-02 | Explicit route dispatch | Add a test route in `app/config/router.php`; request its path. | The configured controller/action is selected and its configured parameters are passed. |
| WEB-03 | Unknown route/controller | Request an unmapped URL in the test environment. | The framework follows its not-found behavior; no unrelated controller or data is returned. |
| WEB-04 | View rendering | Render a test view with values set through the controller. | The expected template/layout renders with the provided values. |
| WEB-05 | JSON response | Call `$this->json(['status' => 'ok'], 201)`. | Response has HTTP 201 and `Content-Type: application/json; charset=utf-8`; body decodes to the supplied object. |
| WEB-06 | Invalid JSON payload | Pass a value that `json_encode()` cannot encode, such as a recursive structure. | A runtime error is raised rather than returning a success-shaped response. |
| WEB-07 | Invalid HTTP status | Call the JSON helper with a value outside the integer range 100–599 or a non-integer. | An `InvalidArgumentException` is raised. |
| WEB-08 | Route allowlist and method checks | Register an explicit route and request an unregistered path or disallowed method. | Unregistered path returns 404; disallowed method returns 405 and an `Allow` header. |
| WEB-09 | Unsafe path rejection | Request paths containing traversal, encoded separators, backslashes, or control characters. | The router rejects the path and no controller/action is invoked. |
| WEB-10 | Redirect validation | Attempt an external URL, protocol-relative URL, or CR/LF-containing redirect target. | The router rejects the target without sending a redirect response. |

### Database models

Run database cases for every PDO driver the deployment intends to support, using separate disposable databases and driver-appropriate schemas.

| ID | Scenario | Steps | Expected result |
|---|---|---|---|
| DB-01 | Connect to configured database | Configure a valid driver, existing database, and least-privilege test account; instantiate a model. | PDO connects successfully and the model table convention or explicit `$table` is used. |
| DB-02 | Unsupported driver | Configure an unsupported `DB_CONNECTION` and instantiate a model. | An explicit unsupported-driver exception is raised. |
| DB-03 | Parameterized read | Call `first()` with a SQL placeholder and a value containing a quote or SQL-looking text. | The matching literal row is returned; input is not interpreted as SQL. |
| DB-04 | Fetch all rows | Call `all()` for a known test query. | All matching rows are returned as associative arrays. |
| DB-05 | Execute statement | Call `execute()` with a prepared insert/update in the test database. | A PDO statement is returned; no result is printed as a side effect. |
| DB-06 | Find equality condition | Call `find('first', ['conditions' => ['User.username' => $value]])`. | One matching record is returned in the model-name wrapper; returned absence is `null`. |
| DB-07 | Find all and NULL | Query multiple rows and a condition whose value is `null`. | Results are wrapped per model; null condition generates an `IS NULL` lookup. |
| DB-08 | Reject invalid identifier | Pass a malformed table/condition identifier to the model API. | The identifier is rejected before SQL execution. |
| DB-09 | Insert record | Insert a test-only record using `insert()` or `create()` then `save()`. | One row is inserted with bound values; stored values match the test input. |
| DB-10 | Update with conditions | Update a known test row using a non-empty values array and explicit condition. | Only matching test rows are changed. |
| DB-11 | Update without conditions | Call `update()` with an empty condition array. | An exception is raised and no rows are modified. |
| DB-12 | Delete with conditions | Delete a test row with an explicit condition. | Only matching test rows are deleted. |
| DB-13 | Delete without conditions | Call `delete([])`. | An exception is raised and no table-wide deletion occurs. |
| DB-14 | MySQL first-row query | Call `find('first', ...)` through MySQL PDO. | Query executes with a one-row limit. |
| DB-15 | SQL Server first-row query | Call `find('first', ...)` through SQL Server PDO. | Query executes with SQL Server's one-row selection syntax. |

### Authentication, sessions, OAuth, and JWT

| ID | Scenario | Steps | Expected result |
|---|---|---|---|
| AUTH-01 | Password hash/verify | Configure `APP_KEY`; hash a test password and verify correct and incorrect values. | Correct value verifies; incorrect value does not; the stored hash is not plaintext. |
| AUTH-02 | Missing application key | Unset `APP_KEY` and call the password helper. | A clear configuration exception is raised. |
| AUTH-03 | Successful login | Use a disposable user record with a password created by the configured helper; submit valid credentials. | Authentication succeeds, session ID is regenerated when a session is active, and password is omitted from session user data. |
| AUTH-04 | Failed or incomplete login | Try an incorrect password and missing credential fields. | Login returns false; no authenticated session is created. |
| AUTH-05 | Session values | Store a falsey value (for example `0`) and read it back. | `get()` returns the stored value rather than treating it as missing. |
| AUTH-06 | OAuth schema installation | In an isolated test database, call `installSchema()` twice with the configured supported PDO driver. | Expected OAuth tables exist and repeated installation does not fail. |
| AUTH-07 | OAuth client credentials | Register a test client using the OAuth library's storage APIs; request a token using valid and invalid credentials. | Valid credentials produce a token response; invalid credentials produce an OAuth error response. |
| AUTH-08 | OAuth authorization code | Exercise authorization request after test-user authentication and consent; exchange the returned code. | An approved valid code can be exchanged; denied, reused, expired, or mismatched codes are rejected by the OAuth server. |
| AUTH-09 | Protected OAuth resource | Call `verifyResourceRequest()` with a valid token and then an invalid/expired token. | Valid token is accepted; invalid token yields `valid === false` and an OAuth error response. |
| JWT-01 | JWT round trip | Encode claims with a test secret; decode with the same secret. | Claims are returned and contain `iat` and `exp`. |
| JWT-02 | JWT signature tampering | Modify a token segment or decode with a different secret. | Verification throws an invalid-signature error. |
| JWT-03 | JWT expiration and required expiration | Decode an expired token and a signed token without `exp`. | Both are rejected. |
| JWT-04 | JWT time and audience checks | Test future `nbf`/`iat`, incorrect issuer/audience, and a valid token with matching options. | Invalid claims are rejected; the valid matching token is accepted. |
| JWT-05 | Unsupported JWT algorithm | Provide a token with an algorithm other than HS256. | Token is rejected; no algorithm downgrade occurs. |

### Scheduler, mail, frontend, and WebSockets

| ID | Scenario | Steps | Expected result |
|---|---|---|---|
| CRON-01 | Cron expression validation | Register valid wildcard, list, range, and step expressions, then try invalid field counts and out-of-range values. | Supported expressions are accepted; invalid expressions raise `InvalidArgumentException`. |
| CRON-02 | Due task execution | Register a due task and run scheduler with a fixed test time. | Callback executes once. |
| CRON-03 | Duplicate run suppression | Run the same due task twice within the same minute using the same lock directory. | Callback executes once for that minute. |
| CRON-04 | Concurrent lock | Hold a task lock from a second process while invoking the runner. | The competing process does not execute the locked task. |
| CRON-05 | Task failure | Make a callback throw, then invoke the scheduler again in the same minute. | The failure is visible; the run is not recorded as successful and the callback can be retried. |
| MAIL-01 | Missing cURL configuration | Select `curl` without a valid HTTPS endpoint. | Configuration error is raised before a request is sent. |
| MAIL-02 | Successful cURL mail | Use a controlled HTTPS mock endpoint returning 2xx; send a test message. | A POST request contains the documented JSON fields and optional bearer authorization; method returns true. |
| MAIL-03 | cURL API failure | Have the mock endpoint return non-2xx or make the request time out. | A runtime error with transport/status context is raised. |
| MAIL-04 | SMTP transport | Use a test SMTP service and send to a controlled mailbox. | Message is accepted; credentials are not exposed in logs or source. |
| UI-01 | React/Vue API examples | Configure a client to request a test JSON endpoint; simulate a non-2xx response. | Successful response renders data; HTTP failure is surfaced to the client. |
| UI-02 | Angular/Firebase guidance | Build the separate client and test API calls using test credentials only. | Client dependencies remain separate from server secrets; API auth is not trusted until server-side validation is configured. |
| WS-01 | WebSocket dependency and port | Confirm `sockets` extension, start the WebSocket server on a free local port, and connect a test client. | Server binds and accepts the connection; stop it after the case. |

## Recording results

Use a copy of this table in the release record or issue tracker:

| ID | Result (Pass / Fail / N/A) | Environment / versions | Notes or issue |
|---|---|---|---|
| Example: SYS-01 |  |  |  |

For failures, record the exact command/request, exception or HTTP response, relevant sanitized logs, and the PHP/database/extension versions. Never paste passwords, tokens, private keys, or personal data into test reports.
