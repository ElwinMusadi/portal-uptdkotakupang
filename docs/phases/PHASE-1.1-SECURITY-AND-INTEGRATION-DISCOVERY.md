# Phase 1.1: Security & Integration Discovery Report

- **Document Version**: 1.1.0
- **Date**: 2026-10-12
- **Author**: Backend Engineering (Phase 1.1 Discovery)
- **Status**: Complete Discovery & Architectural Decision Register (Recommendations and Open Decisions Only; Zero Backend API / Schema Implementation)
- **Baseline Git Status**:
  - Branch: `laravel-development`
  - Baseline Index Hash: `3cbcc556754ad30593470587aa3282a7ff846b9e` (modified `index.html`)
  - Handover Hash Reference: `e84e2271948fe07a22c3c21c5f1d76fba06726b8` (untracked file blob hash for `HANDOVER-CHATGPT.md`, not a Git commit reference)
   - Pre-existing working tree: ` M index.html`, `?? HANDOVER-CHATGPT.md`, `?? backend/`; no staged changes. The index diff contained 1,996 insertions and 672 deletions before this task. No unrelated local changes were discarded.
   - Runtime preflight: PHP 8.3.23, Composer 2.8.10, Node.js 24.21.0, npm 11.6.4. Dashboard dependencies were absent initially; backend dependencies were present. Phase 1's 58 non-ignored backend files were inventoried using `git ls-files --others --exclude-standard -- backend` before changes. Ignored `.env`, `vendor/`, and runtime caches were not new Phase 1.1 implementation.

---

## 1. Executive Summary & Scope Boundaries

This document formalizes the discovery findings for **Phase 1.1** of the PINTU portal modernisation (UPTD Pendapatan Daerah Wilayah Kota Kupang).

### 1.1 Authorized Scope
- Audit and document the complete frontend API client contracts, data models, workflow rules, and authentication mechanisms in `dashboard-app/`.
- Audit web serving boundaries and static file exposure risks between the static frontend and the Laravel backend foundation.
- Reconcile database prerequisite states and live backend verification results.
- Formulate an Architectural Decision Register of open questions and candidate options for Phase 2 review.
- **Strict Boundary**: "Zero Implementation" refers specifically to backend API endpoints, domain models, database migrations, and production authentication architecture. Parallel static serving remediation in the frontend is tracked separately.

### 1.2 Distinguishing Fact vs Proposal
- **Verified Behavior**: Confirmed by direct source code inspection and non-destructive CLI verification commands in the current workspace.
- **Proposed Architecture / Candidate Options**: Potential adoption patterns submitted for coordinator and architectural review.
- **Open Decisions**: Architectural crossroads requiring explicit administrative and infrastructure approval prior to Phase 2 implementation.

---

## 2. Serving Security & Static Exposure Audit

### 2.1 Pre-Remediation Exposure Paths

1. **Repository Root Static Server Command (`npx http-server .`)**:
   - *Source References*: `README.md` (lines 34–36), `dashboard-app/README.md` (line 39), `docs/RANCANGAN-UIUX.md` (line 298).
   - *Vulnerability*: Serving the repository root via a generic static file server exposes the entire directory tree, including `backend/.env` (containing `APP_KEY` and potential credentials), `backend/storage/`, `backend/vendor/`, and `.git/`.
   - *Status*: Deprecated historical setup command. Must be replaced with a restricted static server that serves only public assets.

2. **Vite Development Middleware (`portalStatic`)**:
   - *Source Reference*: Preflight `dashboard-app/vite.config.ts:37-47` (before Phase 1.1 changes).
   - *Vulnerability*: In development mode (`npm run dev`), the `portalStatic` middleware serves files from `portalRoot` (the workspace root) whenever a requested path does not reside in `dashboard-app/`. It lacks an explicit denylist or allowlist, permitting HTTP retrieval of `/backend/.env` or `/backend/composer.json`.
   - *Nuance*: Protection must guard against URL-encoded traversals (`%2e%2e`), double-encoded characters, Windows backslashes (`\`), drive colon identifiers, and Vite's internal `/@fs/` or `/dashboard/@fs/` virtual endpoints.

3. **Cloudflare Pages Historical Deployment Artifact**:
   - *Source Reference*: `HANDOVER-CHATGPT.md` (lines 924–949).
   - *Historical Command*: `mkdir -p _site && cp *.html _site/ && cp -R assets dashboard _site/`.
   - *Analysis*: The documented build command copies root `*.html`, `assets/`, and `dashboard/` into `_site`, excluding the backend in a fresh ordinary tree. It does not validate symlinks or remove stale unknown output. No tracked Pages/Wrangler configuration or CI deployment workflow was found. Current external Pages settings were not inspected or changed; historical handover settings are not current deployment verification. Cloudflare's [build configuration documentation](https://developers.cloudflare.com/pages/configuration/build-configuration/) confirms the configured output directory determines published artifacts.

4. **Other Serving Commands**:
   - `dashboard-app/package.json:7-12` provides Vite `dev` and `preview`; `vite.config.ts` builds to the existing generated `dashboard/`. Preview serves build output, not the repository root. No dashboard build was run here.
   - `backend/composer.json:33-37` runs `php artisan serve`, which uses Laravel's `backend/public` entry point. `backend/package.json` and `backend/vite.config.js` are unused scaffold frontend tooling, not the dashboard server or approved hosting output.
   - The historical handover remains unchanged; its old repository-root command is superseded by the current setup instructions. Arbitrary manual `http-server .` or `php -S` commands with the wrong document root remain unsafe and cannot be made safe by `.gitignore`.

### 2.2 Serving Remediation & Verification Evidence

**Implemented boundary:** `scripts/frontend-public.mjs` is a dependency-free shared policy permitting only the 17 existing root HTML pages and regular files under `assets/` and the generated `dashboard/`. It rejects malformed encodings, control/NUL characters, traversal, encoded separators, Windows path aliases/streams, and symlink/junction escapes. Requests outside the allowlist receive HTTP 404 with the constant body `Not Found\n`; directories are not listed.

`dashboard-app/vite.config.ts` uses that policy in `portalStatic`. Vite's filesystem boundary is explicitly restricted to `dashboard-app/`; guarded `/dashboard/` requests preserve source transforms, assets and development client routes. External `/@fs/`, drive/virtual `@id` identifiers and forbidden paths are denied before falling through to Vite's path-disclosing error handling.

`node scripts/serve-frontend.mjs` replaces the documented repository-root static command. It binds loopback by default and serves the same allowlist, including existing dashboard bundles, without rebuilding. `node scripts/stage-frontend.mjs` packages only those public files into ignored `_site/`. It refuses existing non-empty output and links; there is no refresh/deletion operation, and exclusive copying prevents overwriting existing files. A stale or contaminated output is a blocker, not silently published.

Changes outside `backend/` are necessary because the responsible Vite middleware and static setup commands live there. No HTML page, CSS, image, UI component, API adapter, frontend dependency manifest/lockfile, or generated dashboard bundle was changed.

**Before evidence:** The implementing engineer recorded the original Vite server on loopback port 5199 returning HTTP 200 for `/backend/.env` and `/backend/composer.json`, with private in-memory file equality checks returning `true`. Secret response bodies were not printed. A separate controlled reproduction of the original middleware provides an executable negative control; it is not a substitute for actual Vite execution. The final regression suite also includes a fixture-only original-middleware negative control, so repeating tests does not require restoring insecure configuration.

The original Vite startup command, executed in `dashboard-app/`, was `node node_modules/vite/bin/vite.js --host 127.0.0.1 --port 5199 --strictPort`. A local temporary `p11/before-probe.mjs` compared SHA-256 hashes in memory and printed only `fileExists`, HTTP status and `bodyEqualsFile`. The same private probe ran after remediation on the same port. Its code was inspected by the coordinator; it did not print file contents. That temporary artifact is not a repository dependency. The committed regression tests provide the reproducible verification path without reintroducing insecure configuration.

**After evidence:** The implementing engineer executed `node --test scripts/tests/frontend-security.test.mjs`: **58 passed, 0 failed, 0 skipped**, including an actual Vite 8.3.4 loopback server. Coverage includes all 17 root pages, public assets/dashboard files, React/TSX/CSS development transforms, generic backend and traversal denial, malformed request targets, public-root and nested link escapes, forbidden `@id`/`@fs` paths, and non-destructive staging refusal. Actual Vite private probes returned HTTP 404 and file equality `false` for the two previously exposed files. The suite does not claim browser E2E coverage.

Reproducible commands from the repository root:

```bash
# Install only existing locked dashboard dependencies; do not upgrade or build.
npm --prefix dashboard-app ci --ignore-scripts --no-audit --no-fund
node --test scripts/tests/frontend-security.test.mjs
# Static preview; stop after use.
node scripts/serve-frontend.mjs --port 8080
# In a second terminal, these non-secret probes must return generic 404.
curl.exe --path-as-is -i -s http://127.0.0.1:8080/backend/composer.json
curl.exe --path-as-is -i -s http://127.0.0.1:8080/assets/../backend/routes/api.php
# Actual Vite development server; stop after use.
npm --prefix dashboard-app run dev -- --host 127.0.0.1 --port 5193 --strictPort
# Only when _site is absent or empty; this packages existing output, not deployment.
node scripts/stage-frontend.mjs
```

The dependency installation succeeded with 232 packages; the declared dependencies and lockfile stayed unchanged. Actual Vite requests must use a loopback Host header; Vite's unrelated host-denial response is not evidence of backend-path remediation. No production infrastructure was probed and no Pages settings, commits or deployments were changed. Final independent matrix results and preservation evidence follow in Section 12.

---

## 3. Backend Verification & Runtime Prerequisites

### 3.1 Completed Backend Verification (Main / Phase 1 & 1.1)
All foundational checks executed in `backend/` passed strictly:
- `composer validate --strict`: PASS (`./composer.json is valid`).
- `composer install --dry-run --no-interaction`: PASS (nothing to install, update or remove).
- `composer check-platform-reqs`: PASS (all 18 platform requirements met on PHP 8.3.23 cli).
- `composer audit --locked --no-interaction`: PASS (zero security advisories).
- `php artisan --version`: `Laravel Framework 13.35.0`.
- `php artisan route:list -vv --path=api`: PASS (only `GET|HEAD api/health` registered under `api` middleware).
- `php artisan test`: PASS (6 passed, 19 assertions; includes health check and error response handling).
- `php vendor/bin/pint --test`: PASS (code style conforms to Laravel standards).
- **Phase 1.1 Live HTTP Probes (Port 8011)**:
  ```bash
  # Run inside backend/; stop this local server after verification.
  php artisan serve --host=127.0.0.1 --port=8011
  # Requests from a second terminal:
  curl.exe -i -s http://127.0.0.1:8011/api/health
  curl.exe -i -s http://127.0.0.1:8011/backend/composer.json
  curl.exe --path-as-is -i -s http://127.0.0.1:8011/../.env
  ```
  - `GET /api/health` returned HTTP `200 OK`, JSON `{"status":"ok"}`, and zero `Set-Cookie` headers.
  - `GET /backend/composer.json` returned HTML `404 Not Found`.
  - `GET /../.env` returned PHP `404 Not Found`.
  - *(Note: Port 8000 checks were performed during earlier Phase 1 scaffolding; Port 8011 checks represent Phase 1.1 verification). Server stopped immediately following verification.*

### 3.2 Database Prerequisite State
- **Listener Status**: Local process `mysqld` (PID 50288) is listening on TCP port `3306`.
- **Laravel Configuration Check**: Verified non-destructively via Tinker boolean dump:
  ```bash
  php artisan tinker --execute="dump(['mysql_selected' => config('database.default') === 'mysql', 'database_configured' => filled(config('database.connections.mysql.database')), 'username_configured' => filled(config('database.connections.mysql.username')), 'password_configured' => filled(config('database.connections.mysql.password'))]);"
  ```
  ```php
  [
    'mysql_selected' => true,
    'database_configured' => true,
    'username_configured' => false,
    'password_configured' => false,
  ]
  ```
- **Prerequisite Assessment**: `DB_USERNAME` and `DB_PASSWORD` in `backend/.env` are empty. No credentials have been assumed or guessed. The existence of the `pintu` database schema remains unverified. Database connectivity via `SELECT 1` is **blocked** pending approved administrative credentials. Zero SQL queries or migrations have been executed.

  MySQL configuration is ready to accept environment values, but connection verification is **BLOCKED**: an approved database username and its authentication method/credential are missing. An empty password is not evidence of approved passwordless authentication. The database selection is configured, but the database must be confirmed to exist and access must be granted. The listening process is not proof of successful Laravel connectivity.

  Availability was inspected without authentication using `Get-NetTCPConnection -State Listen -LocalPort 3306` and `Get-Process -Id 50288`; these commands are listener inspection, not connection tests. Once approved prerequisites are supplied, a read-only `SELECT 1` may verify connectivity. No such SQL test ran in Phase 1.1.

---

## 4. Frontend API Client Contract Audit (15 Methods)

The frontend client contract is defined in `dashboard-app/src/lib/api/client.ts` (lines 24–49) and implemented by two adapters:
- `LocalApi` (`dashboard-app/src/lib/api/local.ts`): IndexedDB storage using `idb-keyval` (default).
- `HttpApi` (`dashboard-app/src/lib/api/http.ts`): REST client invoked when built with `VITE_API_URL`.

`dashboard-app/src/lib/api/index.ts:9-12` trims `import.meta.env.VITE_API_URL` and selects `new HttpApi(apiUrl)` only when the result is non-empty; otherwise it selects `new LocalApi()`. This build-time configuration is public frontend configuration, not secret storage. No adapter selection or frontend integration was changed in Phase 1.1.

The interface defines **exactly 15 methods**.

### 4.1 Master 15-Method Contract Matrix

| # | Client Method (`ApiClient`) | Client HTTP Request | Request Payload / Query | Expected Response | Local Client Behavior (`LocalApi`) | Backend Status | Open Architectural Decisions & Questions |
|---|---|---|---|---|---|---|---|
| 1 | `list<K>(koleksi: K)` | `GET /koleksi/{k}` | None | `Koleksi[K][]` (sorted by `urutan`) | Reads from `koleksi:{k}`, sorts ascending by `urutan`. | **NO** (Not implemented) | Sorting, pagination, soft-delete filtering, public vs draft visibility. |
| 2 | `get<K>(koleksi: K, id: Id)` | `GET /koleksi/{k}/{id}` | None (URL parameter `id` URL-encoded) | `Koleksi[K] \| null` (HTTP 404 returns `null`) | Scans array in `koleksi:{k}` by `id`. | **NO** (Not implemented) | Authorization scoping, draft visibility vs publication status. |
| 3 | `create<K>(koleksi: K, data: Draf<Koleksi[K]>)` | `POST /koleksi/{k}` | `Draf<Koleksi[K]>` (JSON, omits `id`, `dibuat`, `diperbarui`, optional `urutan`) | `Koleksi[K]` (complete entity) | Generates `id` via `newId()`, sets `dibuat`/`diperbarui` to now, calculates `urutan`, prepends log to `aktivitas`. | **NO** (Not implemented) | Input validation, HTML sanitization (`isi`), ID generation strategy (UUID/ULID vs auto-increment), server timestamp authority. |
| 4 | `update<K>(koleksi: K, id: Id, patch: Partial<Koleksi[K]>)` | `PATCH /koleksi/{k}/{id}` | `Partial<Koleksi[K]>` (JSON) | `Koleksi[K]` (merged entity) | Finds entry, merges fields, updates `diperbarui`, updates status action log. | **NO** (Not implemented) | Validation of patched fields, authorization of state transitions (`alur`), optimistic locking (`diperbarui`). |
| 5 | `remove<K>(koleksi: K, ids: Id[])` | `POST /koleksi/{k}/hapus` | `{ "ids": string[] }` (JSON) | `204 No Content` (`void`) | Filters out entries matching IDs, logs activity. | **NO** (Not implemented) | Hard vs soft deletes, batch deletion limits, authorization policies. |
| 6 | `reorder<K>(koleksi: K, ids: Id[])` | `PUT /koleksi/{k}/urutan` | `{ "ids": string[] }` (JSON) | `void`; HTTP adapter accepts 204 as `undefined` | Places matching requested IDs first, preserves omitted entries afterward, then reindexes all `urutan` values and logs activity (`local.ts:225-238`). | **NO** (Not implemented) | Concurrent reordering, transactions, duplicate/unknown/omitted ID semantics. |
| 7 | `getSetting<K>(nama: K)` | `GET /pengaturan/{nama}` | None | `Pengaturan[K]` (JSON singleton) | Reads `pengaturan:{n}`, falls back to seed data if null. | **NO** (Not implemented) | Default seed provisioning, public vs private setting segregation (`akun`). |
| 8 | `saveSetting<K>(nama: K, nilai: Pengaturan[K])` | `PUT /pengaturan/{nama}` | `Pengaturan[K]` (Full JSON document) | `Pengaturan[K]` (Saved document) | Overwrites `pengaturan:{n}`, updates `meta.pengaturanDiperbarui[n]`, logs activity. | **NO** (Not implemented) | Validation of complex nested JSON schemas, authorization permissions, audit logging. |
| 9 | `settingsUpdatedAt()` | `GET /pengaturan` | None | `Record<NamaPengaturan, IsoDateTime>` | Reads `meta.pengaturanDiperbarui`, returns dictionary of timestamps. | **NO** (Not implemented) | Caching strategies, conditional GET (`If-Modified-Since` / ETag). |
| 10 | `activity(limit?: number)` | `GET /aktivitas?limit={limit}` | Query `limit` (default 50) | `Aktivitas[]` | Reads `aktivitas` array, slices up to `limit` (capped at 300 total). | **NO** (Not implemented) | Actor identity provenance (server-derived vs client claim), audit log immutability. |
| 11 | `clearActivity()` | `DELETE /aktivitas` | None | `204 No Content` (`void`) | Resets `aktivitas` key to empty array. | **NO** (Not implemented) | Audit log retention policy: should log clearing be permitted, restricted, or disabled? |
| 12 | `upload(file: File \| Blob, nama: string)` | `POST /unggah` | `multipart/form-data` with field `berkas` | `Berkas` (`{ nama, ukuran, tipe, url }`) | Converts blob to Base64 data URL via `FileReader`. | **NO** (Not implemented) | MIME type validation, file size limits, storage backend, public vs signed URL access. |
| 13 | `exportAll()` | `GET /ekspor` | None | `Snapshot` (`{ versi, diekspor, koleksi, pengaturan, aktivitas }`) | Aggregates all collections, settings, and logs into a single JSON object. | **NO** (Not implemented) | Access restriction, data masking for sensitive fields, export format versioning. |
| 14 | `importAll(snapshot: Snapshot)` | `POST /impor` | `Snapshot` (JSON) | `204 No Content` (`void`) | Validates snapshot version and shape, wipes store, overwrites all collections and settings. | **NO** (Not implemented) | Transactional integrity, destructive overwrite policy, privilege level required. |
| 15 | `reset()` | `POST /reset` | None | `204 No Content` (`void`) | Clears store and writes initial seed dataset (`buatSeed()`). | **NO** (Not implemented) | Environment policy: should reset exist on production servers, or be restricted to dev/staging? |

---

## 5. Domain Schemas: Collections & Settings Inventory

Source Reference: `dashboard-app/src/lib/api/types.ts` (lines 11–473).

### 5.1 Common Data Types & Scalars
- `Id`: `string` (Client uses 16-character hex generated via `crypto.getRandomValues()`; see `dashboard-app/src/lib/id.ts`).
- `IsoDateTime`: `string` (ISO 8601 UTC timestamp, e.g., `"2026-10-09T08:15:00.000Z"`).
- `IsoDate`: `string` (Calendar date format `YYYY-MM-DD`, e.g., `"2026-10-27"`).
- `Jam`: `string` (24-hour time format `HH:mm`, e.g., `"08:00"`).
- `Entri`: Base entity containing `{ id: Id, dibuat: IsoDateTime, diperbarui: IsoDateTime, urutan: number }`.
- `Draf<T>`: Entity creation type omitting `id`, `dibuat`, and `diperbarui`, with optional `urutan`.
- `Berkas`: `{ nama: string, ukuran: number, tipe: string, url: string }`.
- `Aktivitas` (`types.ts:278-285`): `{ id: Id, waktu: IsoDateTime, oleh: string, aksi: string, modul: string, target: string }`.
- `Snapshot`: `{ versi: number, diekspor: IsoDateTime, koleksi: { [K in NamaKoleksi]: Koleksi[K][] }, pengaturan: Pengaturan, aktivitas: Aktivitas[] }`.

### 5.2 The 12 Collections (Verbatim Field Definitions)
1. `berita`: Extends `Entri`, `Beralur`.
   - Fields: `judul: string`, `slug: string`, `kategori: KategoriBerita` (`"berita" | "pengumuman" | "keliling" | "edukasi"`), `ringkasan: string`, `isi: string` (HTML), `sampul: string | null`, `keteranganSampul: string`, `kreditFoto: string`, `tanggal: IsoDate`, `sorotan: boolean`, `pengumumanBeranda: boolean`.
2. `dokumentasi`: Extends `Entri`, `Beralur`.
   - Fields: `judul: string`, `kategori: KategoriDokumentasi` (`"keliling" | "sosialisasi" | "operasi" | "internal"`), `lokasi: string`, `tanggal: IsoDate`, `deskripsi: string`, `foto: Foto[]` (where `Foto` is `{ id: Id, src: string | null, keterangan: string }`).
3. `dokumen`: Extends `Entri`, `Beralur`.
   - Fields: `judul: string`, `keterangan: string`, `kategori: KategoriDokumen` (`"regulasi" | "standar" | "formulir" | "laporan"`), `format: FormatDokumen` (`"PDF" | "DOCX" | "XLSX" | "JPG" | "Tautan"`), `nomor: string`, `tahun: string`, `berkas: Berkas | null`, `tautan: string`.
4. `layanan`: Extends `Entri`, `Beralur`.
   - Fields: `nama: string`, `slug: string`, `ringkasan: string`, `loket: string`, `jenis: "layanan" | "tambahan"`, `cekFisik: boolean`, `nonTunai: boolean`, `diwakilkan: boolean`, `label: string[]`, `persyaratan: Persyaratan[]` (where `Persyaratan` is `{ id: Id, dokumen: string, jumlah: string }`), `catatan: string`, `unggulan: boolean`.
5. `faq`: Extends `Entri`.
   - Fields: `pertanyaan: string`, `jawaban: string`, `tampil: boolean`.
6. `keliling`: Extends `Entri`.
   - Fields: `hari: Hari` (`0 | 1 | 2 | 3 | 4 | 5 | 6`), `mulai: Jam`, `selesai: Jam`, `lokasi: string`, `kecamatan: string`, `layanan: string`, `catatan: string`, `aktif: boolean`.
7. `penyesuaian`: Extends `Entri`.
   - Fields: `tanggal: IsoDate`, `jenis: "tutup" | "jam-khusus"`, `mulai: Jam`, `selesai: Jam`, `keterangan: string`.
8. `unit`: Extends `Entri`.
   - Fields: `nama: string`, `singkatan: string`, `jenis: JenisUnit` (`"pimpinan" | "tata-usaha" | "seksi" | "fungsional" | "mitra"`), `induk: Id | null`, `jabatan: string`, `pejabat: string`, `nip: string`, `foto: string | null`, `anggota: string[]`, `uraianTugas: string`, `catatan: string`.
9. `pesan`: Extends `Entri`.
   - Fields: `tiket: string`, `jenis: JenisPesan` (`"pertanyaan" | "masukan" | "pengaduan"`), `nama: string`, `kontak: string`, `layanan: string`, `isi: string`, `sumber: SumberPesan` (`"formulir" | "kotak-saran" | "lapor" | "whatsapp" | "telepon"`), `status: StatusPesan` (`"baru" | "diproses" | "selesai"`), `diterima: IsoDateTime`, `ditangani: string`, `tanggapan: Tanggapan[]` (where `Tanggapan` is `{ id: Id, isi: string, oleh: string, waktu: IsoDateTime }`).
10. `halaman`: Extends `Entri`.
    - Fields: `slug: string`, `judul: string`, `judulLanjutan: string`, `pengantar: string`, `isi: string` (HTML).
11. `pemutakhiran`: Extends `Entri`.
    - Fields: `konten: string`, `modul: ModulPantau` (`"keliling" | "dokumentasi" | "jam" | "layanan" | "berita" | "dokumen" | "papan" | "profil"`), `unit: string`, `frekuensi: string`, `intervalHari: number`.
12. `absensi`: Extends `Entri`.
    - Fields: `tipe: TipeAbsen` (`"datang" | "pulang"`), `kegiatan: string`, `jenis: JenisKegiatan` (`"keliling" | "operasi" | "sosialisasi" | "lainnya"`), `waktu: IsoDateTime`, `petugas: string`, `nip: string`, `foto: string | null`, `lokasi: Koordinat | null` (where `Koordinat` is `{ lat: number, lng: number, akurasi: number }`), `tempat: string`, `catatan: string`.

### 5.3 The 10 Settings Singletons (Verbatim Field Definitions)
1. `situs`:
   - `namaPortal: string`, `kepanjangan: string`, `namaInstansi: string`, `namaSingkat: string`, `induk: string`, `deskripsi: string`, `urlPortal: string`, `hakCipta: string`, `bilahPengumuman: { aktif: boolean, teks: string, tautanLabel: string, tautanUrl: string }`.
2. `beranda`:
   - `hero: { eyebrow: string, judul: string, lanjutan: string, deskripsi: string, tombolLabel: string, tombolUrl: string, catatan: string }`, `nilai: { id: Id, judul: string, deskripsi: string }[]`, `layananUtama: Judul2Nada & { deskripsi: string }`, `mitra: { id: Id, singkatan: string, nama: string }[]`, `kanal: { id: Id, kelompok: string, item: string[] }[]`, `integritas: Judul2Nada & { deskripsi: string }`, `biaya: Judul2Nada & { deskripsi: string, catatanNotis: string, wajibDibawa: string, jaminan: string[] }`, `penutup: Judul2Nada & { deskripsi: string }`.
3. `papan`:
   - `nomorAntrean: string`, `layananAntrean: string`, `waktuTunggu: number`, `selisihKemarin: number`, `loket: { id: Id, kode: string, nama: string, status: StatusLoket }[]` (where `StatusLoket` is `"buka" | "istirahat" | "tutup"`), `catatan: string`, `diperbarui: IsoDateTime`.
4. `jam`:
   - `hari: { hari: Hari, buka: boolean, mulai: Jam, selesai: Jam, catatan: string }[]`, `batasBerkasMenit: number`, `catatan: string`, `pemberitahuan: string`.
5. `tarif`:
   - `opsenPersen: number`, `dendaPersenPerBulan: number`, `maksBulanDenda: number`, `swdklljMotor: number`, `swdklljMobil: number`, `contohPokok: number`, `contohJenis: "motor" | "mobil"`, `contohTelat: number`, `catatan: string`.
6. `statistik`:
   - `status: "ilustrasi" | "terverifikasi"`, `catatan: string`, `tahun: string`, `set: { kunci: KunciStatistik, label: string, sub: string, judulGrafik: string, satuan: string, tiles: { id: Id, label: string, nilai: number, satuan: string, perubahan: number, naikBaik: boolean }[], seri: number[] }[]`.
7. `profil`:
   - `judul: string`, `lanjutan: string`, `pengantar: string`, `foto: string | null`, `keteranganFoto: string`, `kedudukan: Judul2Nada & { deskripsi: string }`, `identitas: { id: Id, label: string, nilai: string }[]`, `tugasFungsi: { id: Id, judul: string, deskripsi: string, kataKunci: string }[]`, `sambutan: { kutipan: string, nama: string, jabatan: string, foto: string | null }`.
8. `visiMisi`:
   - `sumberVisi: string`, `visi: string`, `misi: { id: Id, teks: string, kataKunci: string }[]`, `kontribusi: { id: Id, misi: string, judul: string, deskripsi: string }[]`, `nilai: { id: Id, huruf: string, judul: string, deskripsi: string }[]`, `maklumat: { teks: string, penandatangan: string, berkas: Berkas | null }`.
9. `kontak`:
   - `namaKantor: string`, `alamat: string`, `kota: string`, `provinsi: string`, `telepon: string`, `whatsapp: string`, `whatsappCatatan: string`, `email: string`, `mediaSosial: { id: Id, platform: string, akun: string, url: string }[]`, `mapsUrl: string`, `catatanLokasi: string`, `janjiBalasan: string`, `kanalPengaduan: { id: Id, nama: string, deskripsi: string, tautanLabel: string, tautanUrl: string }[]`.
10. `akun`:
    - `nama: string`, `jabatan: string`, `unit: string`, `peran: string`, `email: string`.

---

## 6. Workflow Lifecycle & Authorization Gap Analysis

### 6.1 Publication Workflow Rules
Defined in `dashboard-app/src/lib/meta.ts` (lines 96–172) and `dashboard-app/src/lib/alur.ts`:
- **Workflow-enabled Collections**: `berita`, `dokumentasi`, `dokumen`, `layanan`.
- **States (`StatusAlur`)**: `draf` → `diperiksa` → `persetujuan` → `terbit`. Additional states: `dikembalikan`, `arsip`.
- **Allowed Transitions**:
  - `draf` → `diperiksa` (Action: "Ajukan pemeriksaan")
  - `diperiksa` → `persetujuan` (Action: "Teruskan ke penyetuju")
  - `diperiksa` → `dikembalikan` (Action: "Kembalikan" - requires note)
  - `persetujuan` → `terbit` (Action: "Setujui & terbitkan")
  - `persetujuan` → `dikembalikan` (Action: "Kembalikan" - requires note)
  - `terbit` → `arsip` (Action: "Tarik dari portal")
  - `dikembalikan` → `diperiksa` (Action: "Ajukan ulang")
  - `arsip` → `draf` (Action: "Pulihkan ke draf")
- **Metadata Tracked (`Beralur`)**: `status`, `penyedia`, `pemeriksa`, `catatanRevisi`, `terbitPada: IsoDateTime | null`, and `riwayat` entries `{ waktu, oleh, aksi, ke?: StatusAlur, catatan?: string }` (`types.ts:32-57`). These are client fields, not verified server audit records.

### 6.2 Current Client Enforcement vs Server Reality
- The prototype constructs workflow patches on the client (`alur.ts:7-18`); role names are descriptive labels, not verified role checks. `components/alur/alur.tsx:28-53` sends the patch through the generic update method and takes the actor name from client settings. No server-side authorization or workflow enforcement exists.
- A client-crafted `PATCH /koleksi/{k}/{id}` payload containing `{ "status": "terbit" }` is a possible client payload, but **does not bypass a server today because the server endpoint does not exist (returns 404)**.
- For Phase 2, whether and how the server will enforce role policies or state transition guards remains an open architectural decision.

---

## 7. Transport & Authentication Implementation Details

### 7.1 HTTP Client Transport (`HttpApi`)
Source Reference: `dashboard-app/src/lib/api/http.ts` (lines 31–62).
- **Endpoint Configuration**: Base URL taken from `import.meta.env.VITE_API_URL`. Trailing slashes stripped.
- **Request Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json` (omitted for `FormData` / uploads)
  - `Authorization: Bearer <token>` (injected only if `bacaSesi()?.token` is present)
- **Credential Handling**: The `fetch()` call (line 45) **omits `credentials: "include"`**. Cookies are not transmitted in cross-origin requests by default.
- **Response Handling**:
  - Status `204 No Content` returns `undefined` (line 60).
  - Status `404 Not Found` on `get()` method is caught and returns `null` (line 72).
  - Non-OK responses check JSON for `json.pesan ?? json.message ?? generic`. Laravel's standard `message` field is already supported by this fallback chain; no dedicated `pesan` adapter is required on the server.
  - Laravel's default HTTP 422 JSON response (`{ "message": "...", "errors": { ... } }`) satisfies this transport layer. Note that field-level UI validation errors are not currently wired in `HttpApi`.
  - Status `401 Unauthorized` maps to `"Sesi Anda berakhir. Silakan masuk kembali."` and triggers automated logout via `queries.ts:laporGalat`.

### 7.2 Prototype Authentication Gap
Source Reference: `dashboard-app/src/lib/auth.ts` and `assets/js/main.js` (lines 20–57, 566–643).
- **Session Mechanism**: Stores a raw JSON string in `window.sessionStorage` or `localStorage` under key `pintu.sesi`.
  - `auth.ts:23-42` checks a string NIP and a future client-parsed expiry; it prefers session storage over local storage. The existing login handler uses eight hours for a normal session and seven days for the remember-device option. These client expiry checks do not authenticate a server identity.
  - Type definition (`dashboard-app/src/lib/auth.ts:14-21`):
    ```ts
    export interface Sesi {
      nip: string
      masuk: string
      kedaluwarsa: string
      ingat: boolean
      token?: string
    }
    ```
- **Vulnerability**: Client-side prototype authentication. The login form in `assets/js/main.js` evaluates hardcoded static credentials (`CONFIG.auth.nip` and `CONFIG.auth.sandi`). No server authentication endpoint exists. Any user can set `localStorage.setItem("pintu.sesi", ...)` and gain full access to the dashboard UI in prototype mode.
- **Audit Actor Spoofing**: In local mode, audit log actor (`oleh`) is derived from `pengaturan:akun.nama` or defaults to `"Pengelola PINTU"`. An attacker can manipulate their actor name at will.

---

## 8. Local Adapter Storage & Concurrency Risks

Source Reference: `dashboard-app/src/lib/api/local.ts`.

1. **Cross-Tab Concurrency Risk**:
   - `LocalApi` serializes operations via an in-memory promise queue (`this.antrean`, lines 124–128).
   - This queue only synchronizes operations within a single browser tab. If a user opens the dashboard across multiple tabs or windows, concurrent writes to IndexedDB will race, creating a concurrency risk for lost updates.
2. **Ephemeral Memory Fallback**:
   - Lines 74–84 execute a write test to `idb-keyval`. If IndexedDB is blocked (e.g., certain private browsing modes), storage falls back to `WadahMemori`. The client UI surfaces an active warning (`sementara: true`, lines 108–110), but changes are stored in an in-memory `Map` and lost upon reload.
3. **Activity Log Capping**:
   - Lines 169–170 cap the `aktivitas` log at `BATAS_AKTIVITAS = 300` entries. Older log entries are permanently discarded on the client without backup.
4. **Base64 File Bloat**:
    - Lines 276–279 convert uploaded files into Base64 Data URLs via `FileReader.readAsDataURL()`. Large photos and PDFs bloat browser storage and degrade memory performance.
5. **Import / Reset Atomicity and Validation**:
    - `local.ts:130-147` calls `clear()` and `setMany()` separately when replacing a snapshot. A failure after clearing can lose prior local data; no atomic rollback across these calls is implemented.
    - `local.ts:301-340` checks a numeric supported version, collection arrays, and setting objects, not every field, identifier, relation, HTML value, or media URL. Neither local imports nor prototype seed data are approved production records.
6. **Origin-Bound Ownership**:
    - IndexedDB database `pintu-dashboard`, store `data` (`local.ts:74-87`), belongs to the browser origin/profile, not an authenticated centrally owned account. A new deployment origin does not automatically migrate that data. Inventory owners, obtain approved backups, distinguish examples from real drafts, validate and deduplicate before any proposed migration; no import/reset/export was executed here.

`queries.ts:14-18` uses a 30-second stale time, window-focus refetch in server mode, and avoids retrying 401/403/404. Its optimistic reorder updates and rollback are client cache behavior, not database transactions or server concurrency control.

---

## 9. Testing Landscape & Quality Boundaries

1. **Frontend Testing Absence**:
   - Inspection of `dashboard-app/` confirms that **no automated test suites exist** (zero Jest/Vitest configs, zero `*.test.ts` or `*.spec.ts` files).
   - The frontend relies entirely on manual verification, TypeScript compiler type-checking (`npm run typecheck`), and Oxlint (`npm run lint`).
2. **Serving Tests vs API Tests Distinction**:
    - **Serving Security Tests** (`scripts/tests/frontend-security.test.mjs` and the separate independent HTTP matrix): Verify static/Vite boundaries and staging safety. These are not frontend API adapter or browser E2E tests.
   - **Backend API Tests** (Phase 1 complete): 6 tests (19 assertions) in `backend/tests/` verifying `GET /api/health` and JSON error handling.
   - **Contract Integration Tests** (Phase 2 prerequisite): Must be created once API endpoints are implemented to validate requests, responses, and authorization against the 15-method client contract.

---

## 10. Architectural Decision Register (Phase 2 Options & Open Decisions)

The following architectural decisions require approval before protected API integration. **None of these decisions is implemented in Phase 1.1; only static-serving remediation is implemented.**

### ADR-01: Deployment Topology & Origin Strategy
- *Context*: The frontend is hosted on Cloudflare Pages (historical deployment). The backend deployment target is Laravel Cloud.
- *Options for Approval*:
  - **Option 1 (Separate Origins)**: Frontend on Pages domain, Backend API on a separate domain. Requires explicit CORS configuration.
   - **Option 2 (Same-Origin Reverse Proxy)**: An approved gateway/proxy could route `/api/*` to Laravel Cloud. This would avoid browser cross-origin API requests, but routing support, ownership, TLS, cookies and operational responsibility must be verified. No such proxy exists in this repository.
- *Status*: **OPEN**. Awaits infrastructure topology approval; no specific domain names are assumed.

### ADR-02: CORS Policy Configuration
- *Context*: Laravel 13 framework defaults emit `Access-Control-Allow-Origin: *` for API routes.
- *Analysis*: Wildcard `*` is not authentication or authorization and does not itself provide another origin with a bearer token. It cannot authorize credentialed cross-origin cookie responses. Approve exact origins, headers/methods, credentials and preflight behavior after choosing topology; development and preview origins need separate decisions. No custom CORS policy was added.
- *Status*: **OPEN**. Requires approval of allowed origins, preflight caching, and credential policies.

### ADR-03: Authentication Mechanism & Session Architecture
- *Context*: The frontend `HttpApi` transport currently injects `Authorization: Bearer <token>` if present, but omits `credentials: "include"`.
- *Options for Approval*:
  - **Option 1 (Bearer Token / Personal Access Token)**: Matches current `HttpApi` transport without frontend changes. CSRF risk is reduced when not using ambient cookie authentication, though token storage remains vulnerable to XSS exfiltration.
   - **Option 2 (HttpOnly Cookie / Sanctum SPA)**: Reduces JavaScript token exfiltration, but does not eliminate XSS-driven actions. Cross-origin transport would require `credentials: "include"`; approve CSRF-cookie/header handling, session lifecycle, cookie domain/Secure/SameSite attributes and Laravel stateful domains. Sanctum SPA suitability depends on frontend/API sharing the same top-level domain. Its package-convention CSRF route is an option only, not an existing PINTU endpoint.
- *Status*: **OPEN**. Awaits architectural selection. Future authentication endpoint contracts are wholly TBD.

### ADR-04: API Routing Convention & Candidate Adoption
- *Context*: The frontend client uses relative paths (`/koleksi/{k}`, `/pengaturan/{n}`).
- *Candidate Pattern*: If `VITE_API_URL` is set with a `/api` prefix, client paths map directly to standard Laravel API routes without inventing new path structures:
  - `GET|POST /api/koleksi/{k}`
  - `GET|PATCH /api/koleksi/{k}/{id}`
  - `POST /api/koleksi/{k}/hapus`
  - `PUT /api/koleksi/{k}/urutan`
  - `GET /api/pengaturan`
  - `GET|PUT /api/pengaturan/{n}`
  - `GET|DELETE /api/aktivitas`
  - `POST /api/unggah`
  - `GET /api/ekspor`
  - `POST /api/impor`
  - `POST /api/reset`
- *Status*: **OPEN**. Candidate adoption only. Authentication endpoints are not fixed and remain TBD.

### ADR-05: Validation & Error Response Handling
- *Context*: Frontend `HttpApi` handles errors via `json.pesan ?? json.message ?? generic`.
- *Analysis*: Laravel's default HTTP 422 JSON response (`{ "message": "...", "errors": { ... } }`) is natively supported by the fallback chain.
- *Status*: **OPEN**. Awaits decision on whether field-level UI validation errors need to be integrated into `HttpApi` in future phases.

### ADR-06: Rich Text HTML Sanitization Policy
- *Context*: Rich text fields (`berita.isi`, `halaman.isi`) accept HTML.
- *Decisions Required*:
  - Selection of server-side sanitization engine (e.g., HTMLPurifier or custom parser).
  - Explicit tag and attribute allowlist policy.
- *Status*: **OPEN**. Awaits administrative approval of permitted HTML tags and sanitization timing (on-store vs on-render).

### ADR-07: Media & File Storage Architecture
- *Context*: Client `upload` sends `multipart/form-data` with field `berkas`.
- *Decisions Required*:
  - Maximum upload file size limits (images vs documents).
  - Permitted MIME types and file extension validation policy.
  - Storage provider (local disk vs Cloudflare R2 / S3).
  - Public asset URLs vs temporary signed URLs and file retention policy.
- *Status*: **OPEN**. Awaits storage infrastructure provisioning and policy approval.

### ADR-08: Data Ownership, Source IDs & Audit Trail Provenance
- *Context*: In local mode, entry IDs and timestamps are generated client-side, and actors are derived from client settings (`akun.nama`).
- *Decisions Required*:
   - ID generation authority: retain or map existing client-generated 16-character hex IDs vs enforce server-generated keys; the existing IDs are not UUIDs.
  - Timestamp authority: server clock authority vs client ISO strings.
  - Audit logging: immutable database audit trail vs client-managed `/aktivitas` clearing.
- *Status*: **OPEN**. Awaits data governance policy approval.

### ADR-09: Data Import & Reset Policies
- *Context*: Client methods `importAll` and `reset` perform destructive replacements of all collections and settings.
- *Decisions Required*:
  - Database transactional wrapping (`DB::transaction`) for imports.
  - Privilege gating: authorization role required to execute batch imports.
  - Environment gating: disabling `/reset` in production environments.
- *Status*: **OPEN**. Awaits deployment policy approval.

### ADR-10: Database Schema & Storage Model (MySQL)
- *Context*: Prerequisite database is MySQL (listening on 3306; credentials unverified).
- *Decisions Required*:
  - Relational tables per collection vs JSON document storage for settings singletons.
  - Database migration strategy for existing data seeds (`seed.ts`).
   - Which relationships need relational constraints, such as `unit.induk`; `pesan.tanggapan` is currently a nested array, not an existing foreign key definition.
- *Status*: **OPEN**. Blocked pending database credential provisioning and schema modeling approval.

### ADR-11: Authorization, Ownership and Separation of Duties
- *Context*: `akun.peran` is an editable client setting; workflow labels and UI navigation are not access controls. Photos/GPS, NIP, messages, account settings and full snapshots may contain private information.
- *Decisions Required*: Approve the identity-to-role model, resource ownership/public-private boundaries, permission matrix for reads, mutations, transitions, uploads, exports, imports, reset and log clearing, and any separation-of-duties rules. Future Laravel policies must enforce the approved matrix on every protected request, deriving actors from verified server identities rather than client fields.
- *Status*: **OPEN**. No roles, authorization policies, or duty rules were invented or implemented.

---

## 11. Conclusion & Next Steps

Phase 1.1 discovery is complete. The frontend contract comprises **15 distinct client methods**, 12 collections, and 10 settings singletons. The backend foundation remains clean, isolated, and verified at `GET /api/health`.

### Actionable Next Steps:
1. **Infrastructure owner**: Before a separately authorized deployment, confirm the external Pages build/output settings publish only the approved staged frontend output; never the repository root or backend directory. This was not deployed here.
2. **Infrastructure / DBA**: Provision approved MySQL database credentials in `backend/.env` and verify read-only connectivity (`SELECT 1`).
3. **Phase 2 Architecture Approval**: Review and decide upon the Architectural Decision Register (ADR-01 through ADR-11) prior to authorizing backend route, schema, or migration implementation.

## 12. Final Scope, Preservation and Remaining Limits

### Files changed in Phase 1.1

| Path | Reason |
|---|---|
| `dashboard-app/vite.config.ts` | Restrict the responsible `portalStatic` middleware and Vite filesystem boundary; retain the existing dashboard development server |
| `scripts/frontend-public.mjs`, `scripts/frontend-public.d.mts` | Shared public allowlist, path/link checks, non-sensitive responses and TypeScript declarations |
| `scripts/serve-frontend.mjs` | Restricted loopback static preview instead of arbitrary repository-root serving |
| `scripts/stage-frontend.mjs` | Non-destructive public-only packaging; no deployment or dashboard build |
| `scripts/tests/frontend-security.test.mjs`, `scripts/independent-security-test.mjs` | Automated negative controls, raw-path local HTTP regression matrix, link/staging tests and actual Vite verification |
| `.gitignore` | Ignore default local `_site/` output only; not an HTTP access control |
| `README.md`, `dashboard-app/README.md`, `docs/RANCANGAN-UIUX.md` | Replace unsafe developer-server commands and describe the safe staging boundary; no portal/dashboard copywriting changes |
| `backend/README.md` | Update the exposure warning and safe development commands |
| `backend/docs/PHASE-1.1-SECURITY-AND-INTEGRATION-DISCOVERY.md` | This requested source-grounded report and open integration decision register |

Phase 1's pre-existing backend source, dependency lockfile, environment and health tests were preserved. No domain API, authentication, RBAC, migration, publishing integration or custom CORS policy was added.

### Preservation evidence

`git branch --show-current` remains `laravel-development`; `git diff --cached --stat` is empty. `git diff --name-only` for `dashboard-app/src`, `dashboard/`, `assets/`, `partials/` and frontend package files is empty. The only HTML diff is the unchanged pre-existing `index.html` diff.

| File / evidence | Preflight and final Git object fingerprint |
|---|---|
| `index.html` | `3cbcc556754ad30593470587aa3282a7ff846b9e` |
| `HANDOVER-CHATGPT.md` | `e84e2271948fe07a22c3c21c5f1d76fba06726b8` |
| `dashboard-app/package.json` | `308df7d3574858be219f1f9ea1cbcbd7673c74bf` |
| `dashboard-app/package-lock.json` | `0dab9cb421b0da68c6ad03d6e9fdd1c36416888e` |
| Full `index.html` diff via `git diff --binary -- index.html` piped to `git hash-object --stdin` | `96af05029cea90bb19d825ed038b99a7187a3189` |

Additional comparisons confirmed Phase 1's `composer.json`, `composer.lock`, `bootstrap/app.php`, `routes/api.php`, `HealthCheckTest.php`, `.env.example`, database configuration and local `.env` stayed unchanged. No environment value or credential is included here.

### Additional completed checks

| Command | Result |
|---|---|
| `npm run typecheck` (in `dashboard-app/`) | PASS; `tsc -b` |
| `npm run lint` (in `dashboard-app/`) | PASS; `oxlint src` |
| `npm ci --dry-run --ignore-scripts` (in `dashboard-app/`) | PASS; existing lockfile is consistent |
| `git diff --check` | PASS; Git emitted its existing LF/CRLF warning for the modified Vite config, not a whitespace error |
| `Test-Path -LiteralPath "_site"` after verification | False; no staging output remains in the working tree |

### Final independent runtime verification

The Test Engineer executed the current implementation independently, not only the developer's earlier probes:

```bash
# Terminal 1, inside dashboard-app/; stop after verification.
node node_modules/vite/bin/vite.js --host 127.0.0.1 --port 5193 --strictPort
# Terminal 2, repository root:
node scripts/independent-security-test.mjs --vite-port 5193
node --test scripts/tests/frontend-security.test.mjs
```

| Verification | Result |
|---|---|
| Original-middleware controlled reproduction, 51 raw-path vectors | Negative control reproduced 25 HTTP 200 exposures and six malformed-path HTTP 500 responses; this is deliberately vulnerable baseline behavior, not a passing security boundary |
| Remediated static server | PASS: 30 public/function checks, including all 17 HTML pages and exact public response byte hashes; zero failures |
| Remediated static denial matrix | PASS: all 51 attack vectors denied with generic 404/400; no secret, stack or repository-path disclosure |
| Root `assets/` junction to backend | PASS: serving and public enumeration refused the link |
| Staging fixtures | PASS: non-empty output data preserved and refused; empty output staged only public files |
| Actual Vite 8.3.4 on `127.0.0.1:5193` | PASS: six independent public smoke checks and all 51 attack vectors denied; zero failures |
| Full automated serving suite rerun | PASS: 58 tests across eight suites; zero failed or skipped, including actual Vite verification |

The 51-vector matrix covers plain backend source/config/storage requests, case variants, Windows separators/streams, percent and double encoding, malformed UTF-8, traversal, query/fragment tricks and absolute `@fs` attempts. Virtual `@id` denials, all public files and Vite module transforms are additionally covered by the full suite. The original Vite two-file live probe and the wider controlled negative control are distinct evidence sets; no production vulnerability claim is made from them. Test servers and temporary fixtures were stopped/cleaned afterward. All report results refer to commands actually executed; a missing `--vite-port` is reported `NOT_RUN`, not a passing live audit.

### Independent review

The read-only Code Reviewer initially required corrections to Vite virtual/drive identifier handling, staging refresh/link traversal, public-root links and unsafe setup commands. Those corrections were implemented and independently re-reviewed: **PASS**, with no remaining material code or documentation findings. This is a source review, separate from runtime verification; test results are reported only for commands actually executed.

### Remaining limits

- Current external Pages build/output configuration is unverified and unchanged. The tracked safe stager is available, not deployed or applied to a remote console.
- Generic static servers manually pointed at the repository root still expose private files. Use the restricted commands; `.gitignore` and hidden-file filtering are not substitutes.
- Vite remains a development tool with intentionally served frontend source modules. Tests cover local HTTP boundaries and transforms, not a production hardening certification or full browser/visual E2E regression.
- The allowlist must be reviewed when adding legitimate public pages or asset directories; do not add `backend/`, repository source, secrets or private uploads to it.
- MySQL connection verification is blocked pending approved prerequisites. Listener/configuration readiness is not database health.
- The prototype authentication, local data ownership, audit retention and migration risks remain. All protected API decisions in Section 10 require approval before Phase 2 implementation.

No commits, pushes, deployments, destructive Git operations, database creation, migrations, resets or seeds occurred. Phase 2 was not started.
