# Backend Foundation - UPTD Kota Kupang

Isolated Laravel 13 backend foundation for UPTD Kota Kupang (Phase 1).

## 1. Scope & Architecture Boundaries

- **Phase 1 Boundary**: Establishes an isolated Laravel 13 backend scaffold with a minimal, typed health check API (`GET /api/health`).
- **Frontend Separation**: Public pages, dashboard UI source, assets and generated bundles remain independent and unchanged. Phase 1.1 modifies only development serving configuration and setup commands to prevent backend exposure; see [the security and integration discovery report](docs/PHASE-1.1-SECURITY-AND-INTEGRATION-DISCOVERY.md).
- **No Node/Vite Prerequisite**: The backend is configured as an API service. Node.js, `npm install`, and Vite builds are **not** required to run, test, or serve the backend API.
- **Deferred Implementations**:
  - Authentication (Laravel Sanctum, sessions, tokens, RBAC) is deferred to future phases.
  - CORS uses an exact-origin allowlist from `CORS_ALLOWED_ORIGINS` (empty by default) instead of Laravel's `*` default. Credentialed (cookie) CORS for the dashboard is still deferred.
  - Domain models are added one slice at a time. Only the FAQ public read slice exists (section 6); other modules, all writes and data import remain deferred.
  - No claims of existing demo authentication or production deployment are made.

### Document Root & Web Server Security Warning

- **Serve Exclusively from `backend/public`**: Laravel must ONLY be served through `backend/public` (e.g., via `php artisan serve` or a properly configured web server with document root pointing strictly to `backend/public`).
- **NEVER Serve Repository Root or `backend/` with a Static Server**: Never serve or publish the workspace repository root or `backend/` directly with generic static file servers. The old `npx http-server .` instruction has been replaced in current setup documentation.
- **Frontend Serving Remediation (Phase 1.1, verified by `node --test scripts/tests/frontend-security.test.mjs`)**: `dashboard-app/vite.config.ts` (`portalStatic`) now serves only an allowlist (17 root HTML pages, `assets/`, `dashboard/`) via `scripts/frontend-public.mjs`; `backend/` requests (including case, encoded, traversal, and `/@fs` variants) return a generic 404. Vite internal requests under `/dashboard/` are guarded to `dashboard-app/` real paths. Residual: Vite dev server is still a development tool; do not expose it to shared or public hosts.
- **Exposure Restriction**: Use `node scripts/serve-frontend.mjs` (loopback by default) instead of `npx http-server .`, which serves the whole repository root including `backend/.env`. For static hosting use `node scripts/stage-frontend.mjs` (writes only public files to ignored `_site/`). Never point any generic static server at the repository root or `backend/`.
- **`.gitignore` is NOT HTTP Security**: `.gitignore` prevents files from being committed to Git; it provides zero protection against static web server file access.
- **Phase 1 Boundary**: Phase 1 backend code is isolated to `backend/`. Phase 1.1 added only the narrow frontend serving allowlist above; no UI, auth, CORS, or deployment changes.

---

## 2. System Requirements & Runtime

- **PHP**: `^8.3` (`>=8.3.0 <9.0.0`). Verified with **PHP 8.3.23 (cli)** (NTS Visual C++ 2019 x64).
- **Composer**: Composer 2 with `runtime-api ^2.2`, recommended 2.8+ (verified with **Composer 2.8.10**).
- **PHP Extensions**:
  - Required: `ext-dom`, `ext-fileinfo`, `ext-filter`, `ext-hash`, `ext-iconv`, `ext-json`, `ext-libxml`, `ext-mbstring`, `ext-openssl`, `ext-pcre`, `ext-phar`, `ext-session`, `ext-tokenizer`, `ext-xml`, `ext-xmlwriter`, `pdo_mysql`.
- **Node.js**: Not required for backend development, testing, or execution.

---

## 3. Installation, Environment Setup & Key Generation

### Ordered Setup Steps from Repository Root

From the repository root, execute the following commands in order:

```bash
# 1. Navigate to backend directory from repository root
cd backend

# 2. Install PHP dependencies (Node/npm is NOT needed)
composer install

# 3. Guarded environment file copy (never blindly overwrite existing .env)
php -r "file_exists('.env') || copy('.env.example', '.env');"

# 4. Generate application key ONLY if APP_KEY is blank
php artisan key:generate
```

### APP_KEY Generation Warning

`php artisan key:generate` must **only** be executed when `APP_KEY` in `.env` is blank. Running it unconditionally on repeat rotates the encryption key, which breaks existing encrypted sessions and tokens. The unsafe `composer setup` script was removed from `composer.json` to prevent blind key rotation.

### Default Environment Settings (`.env.example`)

```ini
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pintu
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

- `APP_DEBUG=false` by default to avoid sensitive stack trace disclosure.
- Drivers for session, cache, and queues default to file/sync so the backend foundation does not require an active database connection to boot or serve health checks.
- Database credentials (`DB_USERNAME`, `DB_PASSWORD`) are blank placeholders. No credentials have been invented or assumed.

---

## 4. Database Provisioning & Schema Policy

### MySQL Provisioning Prerequisites

- **Target Database**: `pintu` on `127.0.0.1:3306`.
- **Connectivity Status**: A local `mysqld` process is listening on port 3306. However, database existence (whether database `pintu` exists) and access credentials remain unknown. Phase 1 did not create or provision the database.
- Developers/DBAs must verify or provision the MySQL database and configure valid credentials in `.env` before running database operations.

### Safe Connection Verification (`SELECT 1`)

Do **not** execute database commands until valid credentials are configured in `.env`. Once credentials are provided, verify connectivity non-destructively using this shell-portable, variable-free read-only command:

```bash
php artisan tinker --execute="DB::selectOne('SELECT 1 AS result');"
```

- On connection failure, the command exits non-zero.
- Detailed error messages and traces must be kept private rather than logged or printed in public documents to avoid exposing credential or host details.
- Do not execute this command without credentials configured in `.env`.

### Scaffold Schema Disclaimer

- The default migration files retained in `database/migrations/`:
  - `0001_01_01_000000_create_users_table.php`
  - `0001_01_01_000001_create_cache_table.php`
  - `0001_01_01_000002_create_jobs_table.php`
- Along with `app/Models/User.php` and `database/factories/UserFactory.php`, are standard Laravel framework skeleton files.
- **THEY ARE NOT APPROVED DOMAIN SCHEMAS OR AUTHENTICATION ARCHITECTURE FOR THIS PORTAL.**
- `create_faqs_table` belongs to the FAQ slice (section 6). It has only run against the in-memory SQLite test database, never against MySQL.
- Do **not** execute automated migrations, `migrate:fresh`, or database seeding (`db:seed`).
- Any future execution of `php artisan migrate` must only be done against an explicitly reviewed, dedicated empty database.

---

## 5. Development Server & Health Check API

### Starting the Server

```bash
php artisan serve --host=127.0.0.1 --port=8000
```
Or via composer dev helper:
```bash
composer dev
```

### Health Check Endpoint

- **Endpoint**: `GET /api/health`
- **Full URL**: `http://127.0.0.1:8000/api/health`
- **Response Code**: `200 OK`
- **Content-Type**: `application/json`
- **Payload**:
  ```json
  {"status":"ok"}
  ```
- **Characteristics**:
  - Stateless typed closure in `routes/api.php`.
  - Zero database queries.
  - Zero session or cookie dependencies.
  - Zero authentication or authorization requirements.
  - No environment, timestamps, or unverified service claims exposed.

### JSON Error Formatting

In `bootstrap/app.php`, exception rendering is configured with:
```php
$exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e): bool {
    return $request->is('api', 'api/*') || $request->expectsJson();
});
```
This ensures that requests to API paths (e.g. unhandled 404s) return structured JSON errors instead of HTML error pages, even when no `Accept: application/json` header is sent.

---

## 6. Public FAQ API (first content slice)

`GET /api/public/faq` implements the Product Owner decisions GC18-01–09 recorded in [Phase 2.19](docs/PHASE-2.19-GC18-DECISION-BINDING-AND-CLOSURE-EVIDENCE-COORDINATION.md). It is anonymous and read-only. Nothing consumes it yet: the homepage FAQ and the dashboard are unchanged.

| Aspect | Behaviour |
|---|---|
| Success | `200` with a bare JSON array of `{"pertanyaan": string, "jawaban": string}`; `[]` when nothing is eligible. No IDs or internal fields. |
| Eligibility | `status = published` AND `publik = true` AND `tampil = true`. Filtered in SQL, then re-checked exactly in PHP because MySQL's `utf8mb4_unicode_ci` treats `Published` and `published ` as equal to `published`. |
| Text validity | Valid UTF-8, at most 200 (question) / 800 (answer) characters, no control characters except tab, CR and LF, and at least one visible character (not only spaces, format or private-use characters, combining marks or blank fillers). Failing entries are withheld, never trimmed or truncated. Their IDs, not their text, are logged as a warning at most once every five minutes. |
| Plain text | Strings are returned exactly as stored. Markup and URLs are neither stripped, escaped twice, nor turned into links; consumers must render them as text. |
| Order | Question (case-insensitive, then case-sensitive), then the internal ULID. Sorted in PHP so MySQL and SQLite agree. `urutan` is not used. |
| Bound | Complete collection, no pagination. More than `PINTU_PUBLIC_FAQ_MAX_ITEMS` (default 100) eligible entries returns `503`, never a partial list. Entries withheld for invalid text still count toward the bound. |
| Errors | `422` for any query parameter, `405` for write methods, `429` after `PINTU_PUBLIC_API_REQUESTS_PER_MINUTE` (default 60) requests per IP, `503` `{"message": "…"}` when the table cannot be read or the bound is exceeded. Each `503` cause is logged at most once per minute. |
| Caching | Every response from a matched `api/*` route, errors included (`422`, `429`, `503`), sends `Cache-Control: no-store` (`App\Http\Middleware\PreventResponseCaching`). Routing-level `404`/`405`, CORS preflight and maintenance-mode responses keep Laravel's default `no-cache, private`. No `ETag` or `Last-Modified`, so never `304`. |
| CORS | Exact origins (`scheme://host[:port]`) from `CORS_ALLOWED_ORIGINS`, comma-separated. Wildcards, paths and trailing slashes are dropped rather than widened. Empty means no cross-origin browser access. |
| Configuration | `PINTU_*` limits must be whole numbers of at least 1; anything else, including an empty value, falls back to the default. |

Table `faqs`: `id` ULID, `pertanyaan` text, `jawaban` text, `status` string (`draft`, `published`, `unpublished`; default `draft`), `publik` boolean (explicit public intent; default false), `tampil` boolean (display toggle; default true), `urutan` unsigned int (dashboard order), timestamps.

Code: `app/Http/Controllers/PublicFaqController.php`, `app/Actions/ListPublicFaqs.php`, `app/Models/Faq.php`, `app/Enums/FaqStatus.php`, `app/Http/Middleware/PreventResponseCaching.php`, `config/pintu.php`, `config/cors.php`. Tests: `tests/Feature/PublicFaqControllerTest.php`, `tests/Unit/Models/FaqTest.php`, `tests/Unit/Config/EnvironmentConfigTest.php`.

### Technical choices awaiting owner confirmation

The product decisions left these open. Each is a deliberate default that is cheap to change while no database has been migrated:

- ULID primary keys (Phase 2 recommendation D08) rather than auto-increment.
- Text limits 200/800, matching the dashboard form; collection bound 100; 60 requests per minute per IP.
- `422` for query parameters and `503` for unavailable or over-bound collections (Phase 2.16 proposal).
- `no-store` on the whole `api/*` group, which also covers the future admin API.
- Question-first ordering (GC18-07) conflicts with the dashboard FAQ page, which promises manual drag-and-drop order on the homepage. One of the two must change before the homepage reads this API.

### Laravel Cloud notes (verified 2026-10-13)

- Laravel trusts all proxies automatically on Laravel Cloud (`TrustProxies`, `laravel_cloud()`). With every hop trusted, `$request->ip()` is the leftmost `X-Forwarded-For` entry, which a client can forge, so the per-IP throttle above is best-effort on Cloud. Enable Cloud's edge rate limiting (Growth or Business plan) before go-live.
- Cloud's Cloudflare edge respects explicit `Cache-Control`, so `no-store` keeps API responses out of the edge cache. The edge already adds `X-Content-Type-Options: nosniff`.
- The application lives in `backend/`; create the Cloud application with root directory `backend`.
- Set `CORS_ALLOWED_ORIGINS` to the real portal origin(s) in the Cloud environment variables.

### Tests

`php artisan test` runs 71 tests on in-memory SQLite. `tests/TestCase.php` refuses to boot against any other database, so a stray `DB_CONNECTION` or `DB_URL` can never let `RefreshDatabase` wipe MySQL. MySQL parity has not been tested yet; it needs a dedicated, disposable MySQL test database.

---

## 7. Verification Results (Phase 1 snapshot)

Completed in workspace environment (`backend/`):

| Check | Command | Result |
|---|---|---|
| Composer Config Validation | `composer validate --strict` | `./composer.json is valid` |
| Lockfile / Dependency Consistency | `composer install --dry-run --no-interaction` | Nothing to install, update or remove |
| Platform Requirements | `composer check-platform-reqs` | Success (all 18 requirements satisfied) |
| Composer Security Audit | `composer audit --locked --no-interaction` | No security vulnerability advisories found |
| Framework Version | `php artisan --version` | `Laravel Framework 13.35.0` |
| Route Registration | `php artisan route:list -vv --path=api` | `GET\|HEAD api/health routes/api.php:6` |
| Automated Tests | `php artisan test` | 6 passed (19 assertions), including JSON error disclosure regression |
| Code Formatting | `php vendor/bin/pint --test` | Passed |
| Live HTTP Health | `curl.exe -i -s http://127.0.0.1:8000/api/health` | HTTP 200; `application/json`; exact body `{"status":"ok"}`; no `Set-Cookie` header |
| Live HTTP API Error | `curl.exe -i -s http://127.0.0.1:8000/api/safe404` | HTTP 404; JSON message without debug fields, filesystem paths, or stack trace |
| MySQL Connectivity | N/A | Unverified (listening on 3306; credentials not configured) |

The HTTP checks used `php artisan serve --host=127.0.0.1 --port=8000`; the verification server was stopped afterward. The health check confirms application-level health only, not database health. No migrations, seeds, database provisioning, commits, pushes, or deployment were performed. Existing frontend source, generated dashboard output, package files, and prior working-tree changes were preserved; Git status and file hashes were checked against the preflight baseline.

---

## 8. Windows Environment Caveats

- **PowerShell Execution**: In Windows PowerShell 5.1, run multi-command sequences with `; if ($?) { ... }` rather than Unix `&&`.
- **PHP Path**: Ensure the CLI PHP path matches the runtime environment (e.g., PHP 8.3 NTS on Laragon/Herd).
