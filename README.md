<div align="center">
  <img src="/public/images/OIG4-removebg.png" alt="Flame Framework" width="180" />
  <h1>Flame Framework</h1>
</div>

Flame is a small PHP MVC framework for building web applications with controllers, views, models, PDO database access, sessions, email, and optional integrations. This repository includes a sample application and framework libraries; it is not a full project generator.

> **Project status:** Flame is an evolving framework. Review the system requirements and test checklist before deploying. Not every optional integration is installed or configured by default.

## Contents

- [Features](#features)
- [System requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Run the sample application](#run-the-sample-application)
- [Application structure](#application-structure)
- [Core usage](#core-usage)
- [Optional integrations](#optional-integrations)
- [Testing and deployment checklist](#testing-and-deployment-checklist)
- [Documentation](#documentation)
- [License and attribution](#license-and-attribution)

## Features

- MVC request dispatch with convention-based and explicitly registered routes.
- Controller/model/view structure and PHP templates.
- PDO model with MySQL, PostgreSQL, and SQL Server (`sqlsrv`) connection support.
- Prepared-statement query helpers and guarded insert, update, and delete methods.
- Password hashing and session-based authentication helpers.
- OAuth 2.0 server helpers through `bshaffer/oauth2-server-php`.
- HS256 JWT encode/decode helper.
- Native PHP mail, Sendmail, PHPMailer SMTP, and a configurable JSON-over-HTTPS cURL transport.
- Five-field cron scheduler with file locks and a CLI entry point.
- JSON response helper, HTMX and jQuery assets.
- Integration examples and guidance for React, Vue, Angular, and Firebase clients.
- WebSocket server library.

Optional services and extensions require their own configuration. See [docs/integrations.md](./docs/integrations.md).

## System requirements

### Required for the PHP application

- PHP **7.4 or newer** (PHP 8.x is recommended).
- Composer 2 to install the dependencies in `app/composer.json`.
- A web server or PHP's built-in development server with URL requests routed to `public/index.php`.
- PHP extensions:
  - `json`
  - `session`
  - `hash`
  - `PDO`
- A supported database server and the matching PDO driver, if database-backed models are used:
  - MySQL / MariaDB: `pdo_mysql`
  - PostgreSQL: `pdo_pgsql`
  - Microsoft SQL Server: `pdo_sqlsrv`

### Optional components

| Component | Additional requirement |
|---|---|
| OAuth 2.0 | Composer dependencies installed; configured PDO database; OAuth schema installed once |
| JWT | PHP `hash` support and a private `JWT_SECRET` |
| SMTP email | PHPMailer Composer dependency and reachable SMTP credentials |
| Sendmail email | A working Sendmail-compatible executable and configuration |
| cURL email | PHP `curl` extension and an HTTPS email API endpoint |
| Cron scheduler | PHP CLI and a scheduled task/cron entry invoking `console/schedule.php` |
| WebSocket server | PHP CLI with the `sockets` extension |
| React and Vue clients | Node.js/npm for client development; packages are declared in the root `package.json` |
| Angular or Firebase clients | A separately created client project and its own dependencies |

This repository does not pin a database-server version or provide the external email API contract implementation. Confirm compatibility with the versions and services selected for your deployment.

For detailed requirements and verification scenarios, see [docs/testing-and-requirements.md](./docs/testing-and-requirements.md).

## Installation

Clone or download the repository, then install PHP dependencies from the `app` directory:

```sh
composer install --working-dir=app
```

Use the committed lock file to install the locked dependency versions. The Composer manifest is `app/composer.json`; the root `package.json` is for optional JavaScript clients.

Create your database and application credentials using your local secret manager, shell profile, container environment, or deployment platform. `.env.example` is a **variable reference only**: Flame does not parse `.env` files automatically. Do not put real passwords or keys in a committed file.

For PowerShell, environment variables can be set for the current process before starting the server:

```powershell
$env:APP_KEY = "<private-random-application-key>"
$env:JWT_SECRET = "<different-private-random-jwt-secret>"
$env:DB_CONNECTION = "mysql"
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "3306"
$env:DB_DATABASE = "flame"
$env:DB_USERNAME = "<database-user>"
$env:DB_PASSWORD = "<database-password>"
```

Use independently generated, high-entropy values for `APP_KEY` and `JWT_SECRET`. Keep them stable across application instances; changing `APP_KEY` can invalidate existing password hashes.

## Configuration

### Database

The default connection reads:

| Variable | Purpose | Default |
|---|---|---|
| `DB_CONNECTION` | `mysql`, `pgsql`, `sqlsrv`, or `mssql` (`mssql` maps to `sqlsrv`) | `mysql` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | Driver-dependent: 3306, 5432, or 1433 |
| `DB_DATABASE` | Existing database name | Required |
| `DB_USERNAME` | Database username | Empty |
| `DB_PASSWORD` | Database password | Empty |
| `DB_CHARSET` | MySQL connection character set | `utf8mb4` |

Flame connects to a database that must already exist. Create it using your database administration or migration process; the framework does not create databases or application tables automatically.

### Application security

| Variable | Purpose |
|---|---|
| `APP_KEY` | Key used by the legacy authentication helper's password HMAC |
| `JWT_SECRET` | HS256 signing key used by `Flame\Jwt` |

Keep both values secret, use separate keys, and do not send them to browser applications.

### Email

| Variable | Purpose |
|---|---|
| `MAIL_TRANSPORT` | `native`, `sendmail`, `phpmailer`, or `curl` |
| `MAIL_HOST` | SMTP host |
| `MAIL_PORT` | SMTP port |
| `MAIL_ENCRYPTION` | SMTP security mode |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | SMTP credentials |
| `MAIL_CURL_ENDPOINT` | HTTPS endpoint for the custom JSON mail transport |
| `MAIL_CURL_API_KEY` | Optional bearer token for that endpoint |

The cURL transport posts JSON to the configured endpoint; it does not target a specific vendor. The service must accept the request fields documented in [docs/integrations.md](./docs/integrations.md).

## Run the sample application

From the repository root, install Composer dependencies first, configure the environment, then start PHP's built-in server:

```sh
php -S 127.0.0.1:8000 -t public
```

Open `http://127.0.0.1:8000/`. The sample `HomeController` loads the `User` model and queries the configured `users` table. Consequently, the home page requires a reachable database with that table. If you have not configured the sample database yet, the application may show a database connection error; this does not mean the PHP development server failed to start.

Use only a local development server for development. For production, configure a supported web server, HTTPS, safe error handling, environment secrets, and appropriate routing to `public/index.php`.

## Application structure

```text
app/
  config/       Application and database configuration
  controllers/  Request controllers
  models/       Application model classes
  views/        PHP templates and layouts
  vendor/       Composer dependencies (generated)
console/        CLI entry points
docs/           Integration, requirements, and test documentation
public/         Web document root and static assets
systems/        Framework core and libraries
```

The web server's document root should be `public/`; do not expose application configuration, Composer files, or framework internals as public static files.

## Core usage

### Routing and controllers

By convention, a URL such as `/user/login` dispatches to `UserController::login()`. Routes can also be explicitly registered in `app/config/router.php`:

```php
$router->addRoute('/user/login', UserController::class, 'login');
```

Controllers extend `Flame\Controller\AppController`. Render a template with `$this->render('template')`, pass data with `$this->set('name', $value)`, or return JSON:

```php
public function status()
{
    $this->json(['status' => 'ok']);
}
```

Use `$this->json($data, $status)` with an HTTP status from 100 through 599. The helper emits a JSON content type and throws if encoding fails or output has already started.

### Models and database queries

Models extend `Flame\Model\Model` through the existing `Flame\Model` namespace convention. The base model selects a table name from the model class; set `$table` explicitly when the convention does not match your schema.

Use parameters for all values:

```php
$user = $this->User->first(
    'SELECT id, username FROM users WHERE username = :username',
    [':username' => $username]
);
```

The base model also provides `all()`, `execute()`, `find()`, `insert()`, `create()`/`save()`, `update()`, and `delete()`. Updates and deletes require explicit conditions. `find()` supports simple equality conditions; for joins, ordering, or complex predicates, use a prepared SQL query with `execute()`, `first()`, or `all()`.

### Authentication

The `Flame\Authorization` trait provides password hashing/verification and a database-backed login helper. Configure `APP_KEY` before using password helpers. The sample authentication flow depends on an application user table and corresponding model; define your own registration, account recovery, authorization, and session-expiration policies. Do not assume that login alone authorizes every action.

### Frontend clients

Flame can serve JSON for independently deployed web clients. React and Vue dependencies are listed in the root `package.json`; the repository does not include a generated React/Vue application, build scripts, or Angular/Firebase projects. Follow the examples and server-side validation/security notes in [docs/integrations.md](./docs/integrations.md).

## Optional integrations

### OAuth 2.0 and JWT

OAuth is provided through the existing `bshaffer/oauth2-server-php` dependency. Install its schema as a deployment/migration operation, then configure clients, redirect URIs, scopes, HTTPS endpoints, and user authorization. Do not create OAuth tables on each web request.

`Flame\Jwt` supports HS256 and validates signatures, expiration, not-before, and optional issuer/audience claims. JWT validation does not replace permission checks. See [docs/integrations.md](./docs/integrations.md) for examples.

### Scheduled tasks

Register callbacks in `app/config/schedule.php`, then configure an operating-system scheduler to invoke the runner once per minute:

```sh
php console/schedule.php
```

On Windows, configure the PHP executable and `console/schedule.php` in Task Scheduler. Ensure the task uses the expected working directory and environment variables. The runner is not a persistent daemon.

### Email and WebSockets

Select an email transport with `MAIL_TRANSPORT` and supply only the credentials required for that transport. The cURL transport requires an HTTPS endpoint and the PHP cURL extension. The WebSocket library is a separate CLI feature and requires the PHP sockets extension; it is not the same server as the HTTP application.

## Testing and deployment checklist

This repository does not currently define a PHPUnit, Pest, or npm test command. The companion [test cases and system requirements](./docs/testing-and-requirements.md) document provides a manual verification plan. Run it in a development environment with the required PHP runtime, extensions, dependencies, and test database before release.

Before deploying:

1. Install dependencies and verify the PHP runtime and required extensions.
2. Configure secrets and database connection outside source control.
3. Create the database and application schema using your migration process.
4. Verify routes, templates, validation, error handling, authentication, and authorization.
5. Test each enabled optional integration against a non-production service.
6. Configure HTTPS, least-privilege database access, backups, logs, and scheduled tasks.
7. Point the web server document root at `public/`; disable debug output in production.

## Documentation

- [Integration examples and configuration](./docs/integrations.md)
- [System requirements and test cases](./docs/testing-and-requirements.md)

## License and attribution

See the individual dependency licenses and repository history for project attribution and licensing details.
