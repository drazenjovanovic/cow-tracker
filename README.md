# Cow Tracker

A small PHP 8.1 web app that records what users do and shows it to admins. No framework, no Composer dependencies: just PHP, PDO + MySQL and plain templates.

## Features

| Area | What it does |
|---|---|
| Auth | Registration, login, logout, password reset, email verification (all CSRF-protected). Passwords hashed with `password_hash`. |
| Page A | "Buy a cow" button. After a click the button disappears and the message `thankYou` is shown instead. |
| Page B | "Download" button. Starts a download of an `.exe` file. |
| Statistics (admin) | Table of all events, with filters: **date** (from/to), **user**, **actions** (`login`, `logout`, `registration`, `view-page`, `button-click`). Paginated. |
| Reports (admin) | Line graph (x: dates, y: counts) and a table with the columns: date, page A views, page B views, "Buy a cow" clicks, "Download" clicks. |

Roles: **user** (sign in, open pages A and B) and **admin** (everything a user can do, plus Statistics and Reports).

## Requirements

- PHP 8.1+ with `pdo_mysql` and `mbstring` extensions
- MySQL 5.7+ or 8.x

## Quick start (local PHP)

The app has no runtime Composer dependencies — `vendor/` is only needed to run tests.

```bash
cp .env.example .env        # fill in DB_HOST, DB_USER, DB_PASSWORD, DB_NAME
php bin/setup.php           # creates storage dirs, runs migrations, creates first admin
php -S localhost:8000 -t public public/index.php
```

Open <http://localhost:8000>. The admin credentials are printed by `setup.php` (generated password shown once).

`bin/setup.php` is idempotent — safe to re-run. It skips any step that is already done.

## Quick start (Docker)

**Requirements:** [Docker Desktop](https://www.docker.com/products/docker-desktop/) running.

```bash
cp .env.example .env                            # copy and adjust passwords if you like
docker compose up -d                            # builds the PHP image, starts app + MySQL
docker compose exec app php bin/setup.php       # creates tables and first admin
```

Open <http://localhost:8000>.

The MySQL container is exposed on **port 3307** on your machine (to avoid conflicts with a local MySQL on 3306). To connect with a GUI client (TablePlus, DBeaver):

```
Host:     127.0.0.1
Port:     3307
Database: cow_tracker
User:     root
Password: (your DB_PASSWORD from .env)
```

**Useful commands:**

```bash
docker compose stop                 # stop containers, keep data
docker compose up -d                # start again
docker compose down                 # stop and remove containers
docker compose down -v              # stop, remove containers and database volume
docker compose logs app             # PHP app logs
docker compose logs worker          # mail worker logs
docker compose logs mysql           # MySQL logs
docker compose exec app php bin/setup.php       # re-run setup (idempotent)
docker compose exec app php bin/migrate.php     # run pending migrations
docker compose exec app php bin/migrate.php --status    # migration status
docker compose exec app php bin/migrate.php --rollback  # rollback last batch
```

Regular users sign up through `/register`. Any account can be promoted with:

```sql
UPDATE users SET role='admin' WHERE email='you@example.com';
```

### The downloadable file

`storage/downloads/installer.exe` is what Page B serves. Setup creates a tiny harmless DOS stub that only prints a message. Replace it with any file you like (and change `download.name` in `config/config.php` if you rename it).

## Configuration

Copy `.env.example` to `.env` and set your values there. Everything else lives in `config/config.php`. Supported variables:

| Variable | Default | Purpose |
|---|---|---|
| `DB_HOST` | `mysql` | MySQL host — `mysql` inside Docker, `127.0.0.1` for local PHP |
| `DB_PORT` | `3306` | MySQL port — `3306` inside Docker, `3307` when connecting from the Mac host to Docker MySQL |
| `DB_NAME` | `cow_tracker` | MySQL database name |
| `DB_USER` | `root` | MySQL user |
| `DB_PASSWORD` | `secret` | MySQL password (also sets `MYSQL_ROOT_PASSWORD` in the Docker container) |
| `STORAGE_PATH` | `./storage` | Where sessions and the download file live |
| `APP_TIMEZONE` | `UTC` | Timezone for stored timestamps and the per-day report grouping |
| `APP_URL` | `http://localhost:8000` | Base URL used in password reset emails |
| `APP_DEBUG` | `false` | Show exception messages on the 500 page |
| `LOG_LEVEL` | `info` | Minimum log level: `debug`, `info`, `warning`, `error` |
| `MAIL_DRIVER` | `log` | `log` writes to `storage/mail.log`; `smtp` sends via SMTP; `mail` uses PHP `mail()` |
| `MAIL_FROM` | `noreply@example.com` | From address for outgoing emails |
| `MAIL_FROM_NAME` | `Cow Tracker` | From name for outgoing emails |
| `MAIL_HOST` | _(empty)_ | SMTP hostname (required when `MAIL_DRIVER=smtp`) |
| `MAIL_PORT` | `587` | SMTP port — `587`/`2525` use STARTTLS, `465` uses implicit SSL |
| `MAIL_USERNAME` | _(empty)_ | SMTP username |
| `MAIL_PASSWORD` | _(empty)_ | SMTP password |
| `MAIL_ASYNC` | `false` | Queue emails in the DB instead of sending during the request. Requires the worker to be running. |

Rate limiting is configured directly in `config/config.php` (not via env vars):

| Key | Default | Purpose |
|---|---|---|
| `rate_limit.max_attempts` | `5` | Failed logins before lockout |
| `rate_limit.decay_seconds` | `900` | Lockout window in seconds (15 min) |

The text shown after buying a cow is `messages.thank_you` (`thankYou`).

## Project layout

```
public/            Web root: index.php (front controller), assets/ (CSS, JS), .htaccess
src/
  Application.php  Request lifecycle: session start, dispatch, error handling, security headers
  AppFactory.php   Composition root: builds the object graph and wires all dependencies
  Core/            Router, Request, Response, View, Session, Csrf, Database, Config, RateLimiter, Mailer, QueueMailer, Validator, Logger
  Core/Config/     Typed config objects: AppConfig, DatabaseConfig, LogConfig, MailConfig, RateLimitConfig
  Middleware/      Csrf (all POSTs), Auth, Verified, Admin, Guest, Logging
  Controller/      AuthController, PageController, AdminController
  Domain/          User, Role, EventAction, EventTarget (enums), EventFilter
  Repository/      UserRepository, EventRepository, PasswordResetRepository, EmailVerificationRepository
  Service/         Auth, ActivityLogger, ReportService, PasswordResetService, EmailVerificationService
  Support/         LineChart (server-side SVG), DateInput
config/
  config.php       App configuration (reads from .env)
  routes.php       All route definitions in one place
views/             Plain PHP templates
database/
  migrations/      Versioned migration files (0001_*.php, 0002_*.php, …)
bin/setup.php      Idempotent installer
bin/migrate.php    Migration runner
bin/mail-worker.php Mail queue processor (one-shot or --loop)
bin/log-cleanup.php Deletes daily log files older than N days
storage/           Sessions, download file, daily log files (git-ignored)
.env.example       Template for environment variables (.env is git-ignored)
Dockerfile         PHP 8.1-cli image for the app container
docker-compose.yml App + MySQL 8.4 services for local development
```

## Migrations

### Commands

```bash
docker compose exec app php bin/migrate.php              # run all pending migrations
docker compose exec app php bin/migrate.php --status     # list all migrations and their status
docker compose exec app php bin/migrate.php --rollback   # rollback the last batch
```

`bin/setup.php` calls the migrator automatically, so you only need `bin/migrate.php` directly when adding new migrations after the initial setup.

### How it works

Every migration runs exactly once. After it runs, it is recorded in a `migrations` table with a **batch number**. Running `--rollback` undoes all migrations from the last batch together, so changes deployed at the same time are rolled back together.

```
Migration                                          Status     Batch
------------------------------------------------------------------
0001_create_users_table                            Ran        1
0002_create_events_table                           Ran        1
0003_add_last_login_to_users                       Pending    -
```

### Adding a migration

Create a new file in `database/migrations/` following the numbering sequence. The filename becomes the migration's identifier:

```php
<?php
// database/migrations/0003_add_last_login_to_users.php

declare(strict_types=1);

return [
    'up' => [
        'ALTER TABLE users ADD COLUMN last_login DATETIME NULL',
    ],
    'down' => [
        'ALTER TABLE users DROP COLUMN last_login',
    ],
];
```

Then run:

```bash
docker compose exec app php bin/migrate.php
```

Rules:
- `up` is required — one SQL statement per array entry
- `down` is optional but recommended — used by `--rollback`
- Never edit a migration that has already run — add a new one instead
- Keep migrations small and focused on one change

## Logging

All application logs are written as **JSON lines to STDOUT**, which Docker captures automatically. When attached to a terminal (local dev) the output switches to a human-readable, color-coded format.

```json
{"time":"2024-01-15 10:23:01","level":"INFO","message":"GET /page-a 200 12ms","context":{"ip":"1.2.3.4","user_id":7}}
{"time":"2024-01-15 10:23:05","level":"WARNING","message":"POST /login 422 45ms","context":{"ip":"1.2.3.4"}}
{"time":"2024-01-15 10:23:10","level":"ERROR","message":"Unhandled exception","context":{"exception":"RuntimeException","message":"...","file":"...","line":42}}
```

Every HTTP request produces one log line with method, path, status, duration, IP, user ID, and a unique **request ID**. The level reflects the outcome: `INFO` for 2xx/3xx, `WARNING` for 4xx, `ERROR` for 5xx.

```json
{"time":"2026-10-29 14:22:01","level":"INFO","message":"GET /page-a 200 12ms","context":{"request_id":"a1b2c3d4e5f6a7b8","ip":"1.2.3.4","user_id":7}}
```

The request ID is also returned in the `X-Request-ID` response header. When a user reports a problem, grab the header value from their browser's network tab and find all related log lines instantly:

```bash
grep "a1b2c3d4e5f6a7b8" storage/logs/app-2026-10-29.log | jq .
```

| Level | What produces it |
|---|---|
| `DEBUG` | (reserved for development; silent by default) |
| `INFO` | Successful requests, mail sent by worker |
| `WARNING` | 4xx responses, slow queries (>100ms), mail job retries |
| `ERROR` | 5xx responses, unhandled exceptions, permanent mail failures, failed event writes |

**View logs:**

```bash
docker compose logs -f app       # web process (HTTP request logs + errors)
docker compose logs -f worker    # mail worker (sent/retry/failed)
docker compose logs -f           # everything
```

Set `LOG_LEVEL=debug` in `.env` to enable verbose output during development.

### Log files

Logs are also written to **daily files** in `storage/logs/`:

```
storage/logs/
├── app-2026-10-29.log
├── app-2026-10-30.log
└── ...
```

Each file contains JSON lines for that day only. The file for the current day is created automatically on first write.

```bash
tail -f storage/logs/app-$(date +%Y-%m-%d).log       # follow today's log
tail -f storage/logs/app-$(date +%Y-%m-%d).log | jq . # pretty-print with jq
```

### Log cleanup

Old log files can be deleted with the included cleanup script:

```bash
php bin/log-cleanup.php              # delete files older than 30 days (default)
php bin/log-cleanup.php --days=7     # delete files older than 7 days
php bin/log-cleanup.php --dry-run    # preview what would be deleted
```

To run automatically, add a cron job on your server:

```
0 2 * * * php /app/bin/log-cleanup.php
```


## Monitoring & Observability

### Health check

`GET /health` returns the app and database status. Use it for Docker healthchecks, load balancers, and uptime monitors.

```bash
curl http://localhost:8000/health
# {"ok":true,"db":"up","time":"2026-10-29 14:22:01"}
```

Returns `200` when healthy, `503` when the database is unreachable. The Docker `app` service healthcheck is wired up automatically.

### Slow query detection

Any database query taking longer than **100ms** is automatically logged as a `WARNING`:

```json
{"level":"WARNING","message":"Slow query detected","context":{"ms":143,"sql":"SELECT ...","request_id":"a1b2c3d4e5f6a7b8"}}
```

### Request ID

Every request gets a unique ID (16 hex chars) generated at the start of the middleware pipeline. It appears in every log line for that request and is returned in the `X-Request-ID` response header. Use it to correlate all log entries for a single request:

```bash
grep "a1b2c3d4e5f6a7b8" storage/logs/app-2026-10-29.log | jq .
```

## Mail

The app sends emails for password resets and email verification. The driver is set via `MAIL_DRIVER` in `.env`.

| Driver | When to use |
|---|---|
| `log` | Default. Writes emails to `storage/mail.log` — no mail server needed |
| `smtp` | Any SMTP server, including Mailtrap for development |
| `mail` | PHP built-in `mail()` — requires a mail agent on the server |

### Async delivery

By default emails are sent **synchronously** inside the HTTP request. A slow or unreachable SMTP server blocks the response. Set `MAIL_ASYNC=true` to queue emails in the `mail_queue` table and send them in a background worker instead:

```ini
MAIL_ASYNC=true
```

The worker is included in `docker-compose.yml` and starts automatically with `docker compose up`. It polls every 5 seconds, processes jobs in batches of 10, and retries failed jobs up to **3 times** with exponential backoff (5 min → 10 min → 20 min). After the third failure the job is marked permanently failed and kept in the table for inspection.

**Worker commands:**

```bash
# Run via Docker (already in docker-compose.yml as the `worker` service)
docker compose logs worker          # view worker output

# Run directly (cron or local dev without Docker)
docker compose exec app php bin/mail-worker.php              # one batch, then exit
docker compose exec app php bin/mail-worker.php --loop       # keep running
docker compose exec app php bin/mail-worker.php --batch=25   # custom batch size
```

**Inspect the queue:**

```sql
SELECT * FROM mail_queue WHERE sent_at IS NULL ORDER BY created_at;   -- pending
SELECT * FROM mail_queue WHERE failed_at IS NOT NULL;                  -- permanently failed
```

### Development — log driver (default)

No configuration needed. Emails are appended to `storage/mail.log`. Read them with:

```bash
docker compose exec app cat storage/mail.log
```

### Development — Mailtrap

[Mailtrap](https://mailtrap.io) catches outgoing emails in a test inbox without delivering them to real addresses. Get your credentials from **Inboxes → your inbox → SMTP Settings**:

```ini
MAIL_DRIVER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM=noreply@example.com
MAIL_FROM_NAME=Cow Tracker
```

### Production — any SMTP server

```ini
MAIL_DRIVER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=you@yourdomain.com
MAIL_PASSWORD=your_password
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME=Cow Tracker
```

Port `587` and `2525` auto-negotiate STARTTLS. Port `465` uses implicit SSL.

## Password Reset

Users can request a reset link at `/forgot-password`. The flow:

1. User enters their email — the response is identical whether the email exists or not (no enumeration)
2. If the email is registered, a reset link is emailed with a **60-minute expiry**
3. The link is single-use — the token is deleted after the password is changed

Tokens are stored as SHA-256 hashes in the `password_resets` table.

## Email Verification

New users must verify their email address before they can access the app.

1. After registration, a verification link is emailed with a **24-hour expiry**
2. Until verified, the user is redirected to `/verify-email` on every request
3. The link is single-use — the token is deleted after the address is confirmed
4. A "Resend" button on `/verify-email` sends a fresh link

Tokens are stored as SHA-256 hashes in the `email_verifications` table. Existing users are auto-verified (their `email_verified_at` is set to `created_at`).

## Routes

| Method | Path | Access |
|---|---|---|
| GET / POST | `/login`, `/register` | guests |
| GET / POST | `/forgot-password` | guests |
| GET / POST | `/reset-password` | guests |
| POST | `/logout` | signed in |
| GET | `/verify-email` | signed in (unverified) |
| POST | `/verify-email/resend` | signed in (unverified) |
| GET | `/verify-email/confirm` | anyone (token in query string) |
| GET | `/` | signed in + verified |
| GET | `/page-a`, `/page-b` | signed in + verified |
| POST | `/page-a/buy`, `/page-b/download` | signed in + verified |
| GET | `/admin/stats`, `/admin/reports` | admin |

## How tracking works

Every event is one row in `events`: `user_id`, `action`, `target`, `ip`, `user_agent`, `created_at`.

| User does | `action` | `target` |
|---|---|---|
| registers | `registration` | |
| signs in / out | `login` / `logout` | |
| opens Page A / B | `view-page` | `page_a` / `page_b` |
| clicks "Buy a cow" | `button-click` | `buy_cow` |
| clicks "Download" | `button-click` | `download` |

All writes go through `ActivityLogger`. A failure to write an event is logged and never breaks the page.

## Decisions worth knowing

- **Only Pages A and B produce `view-page` events.** Home, Statistics and Reports views are not tracked, so admins browsing stats don't pollute the stats.
- **Reports and Statistics are admin-only.** Users can view only the two tracked pages.
- **The "Buy a cow" state is not persisted.** After a click the button is replaced by the message (via `fetch`, no reload). On the next visit the button is back, so repeat purchases can be counted. Without JavaScript the form still works: the server redirects back to Page A and shows the message once. That return trip is not counted as a second page view.
- **Download click is counted only if the file exists**, so a missing file never inflates the numbers.
- **Registration does not sign you in.** You are sent to the login page, so `registration` and `login` stay distinct events.
- **Report days use `APP_TIMEZONE`.** Report ranges are capped at 366 days and can't extend into the future.
- **Typed config objects** (`Core/Config/`) centralise all env-var reading and casting. Services (`Database`, `Mailer`, `Logger`, `RateLimiter`) receive a single typed config object instead of scattered primitives — mistyped keys fail in one place, and types are guaranteed at the boundary.
- **`Database::execute()` auto-detects PHP types.** PHP `int` values are bound as `PARAM_INT`, `null` as `PARAM_NULL`, everything else as `PARAM_STR`. This means LIMIT/OFFSET and integer filter params get the correct binding without manual `bindValue` calls, and slow query detection covers every query that goes through `execute()`.

## Security

- CSRF token on every POST (form field or `X-CSRF-Token` header), checked in one global middleware
- Prepared statements everywhere; output escaped with `htmlspecialchars`
- Session: `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS, strict mode, id regenerated on login and logout
- Login does not reveal whether an email exists (same message, comparable timing)
- Strict CSP (`script-src 'self'`, no inline script or style), `X-Frame-Options: DENY`, `nosniff`, `no-store` on pages
- `Strict-Transport-Security` header sent on HTTPS responses (1 year, includeSubDomains)
- Passwords: 8 to 72 characters (bcrypt ignores everything after 72 bytes)
- `X-Forwarded-For` is ignored for the recorded IP because it is spoofable. If you run behind a trusted proxy, adapt `Request::ip()`.
- Rate limiting on all sensitive endpoints: login (5/15 min), registration (10/hour), forgot-password (5/15 min), email resend (3/hour) — all per IP
- Password reset tokens are stored as SHA-256 hashes, expire after 60 minutes, and are single-use. The forgot-password response is identical whether the email exists or not (no enumeration).
- Email verification tokens are stored as SHA-256 hashes and expire after 24 hours. Unverified users are redirected to `/verify-email` until they confirm their address. Existing users at the time the feature was added are auto-verified.

## Validation

Form input is validated through `App\Core\Validator`. Pass a flat array of data and a rules map; it returns `field => first error message` (empty array means valid).

```php
$errors = Validator::make($request->post, [
    'name'     => ['required', 'min:2', 'max:60'],
    'email'    => ['required', 'email', 'max:190'],
    'password' => ['required', 'min_bytes:8', 'max_bytes:72', 'confirmed'],
]);
```

Validation stops at the first failing rule per field so error messages are never duplicated.

| Rule | Checks |
|---|---|
| `required` | Not empty after trimming |
| `email` | Valid email format |
| `min:N` | At least N characters (mb_strlen) |
| `max:N` | At most N characters (mb_strlen) |
| `min_bytes:N` | At least N bytes (strlen) — use for bcrypt passwords |
| `max_bytes:N` | At most N bytes (strlen) |
| `confirmed` | Matches the `field_confirmation` key in the same data |

## Tests

```bash
composer install          # first time only
vendor/bin/phpunit                          # all suites
vendor/bin/phpunit --testsuite Unit         # unit tests only (no DB needed)
vendor/bin/phpunit --testsuite Integration  # integration tests only (requires MySQL)
```

Or via the Composer script (runs all suites):

```bash
composer test
```

### Unit tests (105)

No database or running server needed — tests use an in-memory `ArraySession` stub, a hand-rolled `UserRepositoryInterface` fake, and a `NullLogger`.

| Suite | What is covered |
|---|---|
| `Domain` | `User::fromRow`, role and verification flags; `EventFilter` parsing and round-trip |
| `Core` | `Request` helpers; `Response` factory methods; `Csrf` token generation and validation; `Router` dispatch, 404/405, middleware ordering; `Validator` rules, multi-field errors, UTF-8 character counting |
| `Service` | `Auth` — attempt, login, logout, session resolution, deleted-user cleanup |
| `Middleware` | `Csrf` (POST gating), `Auth` (redirect + JSON 401), `Guest` (bounce signed-in users), `Verified` (bounce unverified), `Admin` (403 for non-admins), `Logging` (level selection, context, re-throw) |

### Integration tests (22)

Require a running MySQL instance. Tests auto-create a `cow_tracker_test` database, run all migrations once, and wrap each test in a transaction that is rolled back on teardown — no manual cleanup needed. Tests skip automatically if MySQL is unreachable.

| Suite | What is covered |
|---|---|
| `UserRepository` | create, findById, findByEmail, emailExists, duplicate-email exception, admin role, updatePasswordHash, markEmailVerified, listForSelect, countAdmins |
| `EventRepository` | insert round-trip, newest-first ordering, filter by user/action/date range, pagination, count vs search consistency, dailyCounts aggregation, null-user events |

**Running locally:**

```bash
docker compose up -d                        # start MySQL
vendor/bin/phpunit --testsuite Integration
```

The test DB (`cow_tracker_test`) is created automatically. To point tests at a different DB name set `TEST_DB_NAME` in your environment.

## CI

GitHub Actions runs automatically on every push and pull request.

| Job | What it does |
|---|---|
| **Unit Tests** | Validates `composer.json`, installs dependencies (cached by `composer.lock`), runs the 105 unit tests — no database needed |
| **Integration Tests** | Starts a MySQL 8.4 service, runs the 22 repository integration tests against `cow_tracker_test` — only runs if unit tests pass |

**To enable CI on your repository:**

1. Push the code to GitHub — the workflow file (`.github/workflows/ci.yml`) is already included
2. Go to **Actions** tab on your repository — both jobs run automatically on the first push

No secrets or repository configuration needed for the current setup. If you later add jobs that use real credentials (SMTP, staging DB, deployment keys), add them under **Settings → Secrets and variables → Actions** and reference them as `${{ secrets.YOUR_SECRET_NAME }}` in the workflow.

## Deployment

Point the web server's document root at `public/` (never at the project root) and make `storage/` writable by the PHP user.

**Apache** works out of the box with `public/.htaccess` (`mod_rewrite`).

**nginx**:

```nginx
server {
    root /var/www/cow-tracker/public;
    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }
    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    }
}
```

The app assumes it is served from the root of its host (`/`), not from a sub-directory.
