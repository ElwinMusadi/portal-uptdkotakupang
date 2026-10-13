# PINTU — Authoritative Content Sources, Visibility Mapping and Public Projection Specification

**Phase:** 2.11, Step 2. **Date:** 2026-10-12. **Status:** evidence-based documentation; no application implementation or subsequent phase authorization.

## A. Executive summary and evidence classification

This specification develops the canonical public-read slice established in Phase 2.10 using the completed **Phase 2.11 Step 1 read-only inspection report in the current conversation**. It records observed sources, missing production-authority decisions, module-specific visibility gaps, candidate public projections, pending transport definitions and consumer compatibility. It does not convert insufficient evidence into approved schemas or functioning APIs.

**Main finding:** four modules have browser-local dashboard representations and separate static public representations; **no single authoritative production content source has been established for any module**. Source visibility is incomplete: Berita/Layanan have prototype workflow fields but no verified public-intent predicate, FAQ has a display toggle only, and Halaman has no publication/status field. No inspected Laravel content endpoint exists. Candidate fields below are not final public schemas.

| Status | Meaning |
|---|---|
| `CONFIRMED` | Explicit project-owner decision/governance requirement, not implemented functionality |
| `VERIFIED IN SOURCE` | Step 1 directly inspected source/test definition supports the finding; runtime execution not implied |
| `INFERRED` | Interpretation/recommendation based on evidence, not a conclusive source fact or approved decision |
| `PENDING` | Source-of-truth, mapping, field or policy/contract decision needed; transport details explicitly `PENDING SPECIFICATION` |
| `BLOCKED` | Actual access/environment/authority/execution evidence unavailable; dependent work cannot safely proceed |

**CONFIRMED governance:** exactly four modules `berita` (Berita), `layanan` (Layanan), `faq` (FAQ), `halaman` (Halaman Statis). Pengumuman is a Berita category; Persyaratan is nested under Layanan. Neither has an independent assignment. Exactly two roles, `Administrator` and `Pegawai UPTD`; owner label is `PINTU Project Owner (Application Administrator)`, no legal identity inferred.

Management depends on active backend identity/current module assignment/action, including otherwise authorized peer content. Multiple assignments and prompt subsequent-request revocation without re-login remain required. Administrator-only deletion/User Management remains. Public reads are anonymous/read-only and only **currently published AND public-intended** content is eligible. Unknown/invalid eligibility fails closed. Successful published edits have immediate public effect without mandatory Administrator approval; unpublish hides but retains an editable record; eligible republish restores visibility. None of these controls is implemented here.

### A1. Baseline and publication boundary

Working directory/root `D:\Herd\portal-uptdkotakupang`; branch/upstream `laravel-development` / `origin/laravel-development`, verified origin and matching local/remote tip `5932c233bf668b6eaa33e5902c26654fde94796b`. Actual status at Step 2: **385 outstanding paths = 6 tracked unstaged modifications + 379 untracked files, 0 staged**. Deliverable absent, no conflict. Tracked binary diff fingerprint `cac31712e8536b629d9fec47bb290ddea1f9c29e`; existing-file inventory fingerprint `695d347acb6df1ba6265a2a60c2d6c4a0a6e42a2` in Git output order. Final preservation comparison excludes only this new document.

Phase 2.9 and 2.10 were reread for this draft, with relevant Phase 2.8 excerpts. The completed Step 1 report supplies the prior broader governance/source inspection, including source symbols and test inventory; the full inspection was not rerun. Earlier local/untracked architecture reports and Laravel foundation stay untracked and are not bundled into this commit. References identify local evidence, not a promise all predecessor sources exist in clean remote checkout. No earlier file rewritten, no secrets read/printed, and no implementation/tests/DB/package/config/UI changes.

### A2. Evidence index

| ID | Repository paths / symbols from Step 1 | Scope of verified evidence |
|---|---|---|
| GOV-01 | `backend/docs/PHASE-2.9-CONTENT-MODULE-CATALOG-AND-PUBLIC-API-CONTRACT.md`; `backend/docs/PHASE-2.10-CANONICAL-CONTENT-MODULE-BOUNDARIES-AND-PUBLIC-READ-SPECIFICATION.md` | Canonical boundaries/current policy and pending source/visibility/projection/transport decisions |
| GOV-02 | `backend/docs/PHASE-2.8-CONTENT-AUTHORITY-AND-PUBLICATION-SEMANTICS.md`; `backend/docs/PHASE-2.7-CONTENT-AND-ACCOUNT-LIFECYCLE-DECISIONS.md`; `backend/docs/PHASE-2.5-GOVERNANCE-OWNERSHIP-AND-READINESS-UPDATE.md` | Immediate published edits, module/peer authority, retained unpublish/direct republish, two roles and owner |
| SRC-01 | `dashboard-app/src/lib/api/types.ts:20-57,76-93,127-154,219-225` | Entri/Beralur/StatusAlur, Berita/KategoriBerita, Layanan/Persyaratan, Faq and Halaman shapes |
| SRC-02 | `dashboard-app/src/lib/api/index.ts:9-12`; `dashboard-app/src/lib/api/local.ts:74-87,112-118,149-178,192-208,212-222,276-317` | Adapter selection, IndexedDB/memory, seed initialization, unfiltered management reads, patch merges, physical local array removal, local data-URL files and snapshot/reset behavior |
| SRC-03 | `dashboard-app/src/lib/api/seed.ts:1-7,74-115,550-573,1010-1034`; `dashboard-app/src/lib/alur.ts:7-29`; `dashboard-app/src/lib/meta.ts:150-162` | Example provenance, synthetic workflow/time, service/static-page examples, client transitions; not production records or authority |
| SRC-04 | `dashboard-app/src/lib/api/http.ts:31-75`; `dashboard-app/src/lib/api/client.ts:24-49` | Existing administrative request/JSON/error/array/entity expectations; not public transport contract |
| SRC-05 | `assets/js/main.js:154-161,511-536`; `informasi.html:201-292`; `berita-detail.html:189-251`; `layanan.html:207-334`; `index.html:806-817,922-1130,1767-1909` | Static news/services/FAQ, DOM category/search/checklist behavior, no fetch/XMLHttpRequest content retrieval found in inspected main.js |
| SRC-06 | `kebijakan-privasi.html:189-207`; `syarat-ketentuan.html:192-202`; `scripts/sync-partials.mjs` | Static prose and source-time header/footer copying, not runtime API content transformation |
| SRC-07 | `dashboard-app/src/pages/berita/editor.tsx`; `dashboard-app/src/pages/layanan/editor.tsx`; `dashboard-app/src/pages/faq.tsx`; `dashboard-app/src/pages/halaman/editor.tsx`; `dashboard-app/src/components/form/rich-text-editor.tsx` | Local forms/display toggles and editor constraints; not server validation/sanitization/public projection |
| SRC-08 | `backend/routes/api.php:6-10`; `backend/routes/web.php:5-7`; `backend/bootstrap/app.php:9-22`; `backend/app/Http/Controllers/Controller.php` | Health API, welcome and framework health wiring/JSON exception handling; no inspected content routes/controllers |
| SRC-09 | `backend/tests/Feature/HealthCheckTest.php`; `backend/tests/Feature/ExampleTest.php`; `backend/tests/Unit/ExampleTest.php`; `scripts/tests/frontend-security.test.mjs` | Existing health/error/scaffold/static-serving tests, not content-visibility acceptance suite; no tests executed in Steps 1/2 |

The inspected source set had no missing/read-error that prevents these findings. No matching test/spec files were found under `dashboard-app/src` in Step 1; backend test directory inspection found the listed foundation tests. This is a targeted source review, not an exhaustive audit of all historical branches, external infrastructure, browser IndexedDB contents or private environments.

## B. Authoritative content-source matrix

### B1. Shared verified storage/adapter behavior

- **VERIFIED IN SOURCE:** `index.ts:9-12` trims `VITE_API_URL`; configured value selects HttpApi, otherwise LocalApi. This is a build-time branch, not proof which adapter a particular deployed browser is using.
- **VERIFIED IN SOURCE:** `local.ts:74-87` uses IndexedDB database `pintu-dashboard`, store `data`, keys `koleksi:<key>`; failures select temporary memory. No central shared store or cross-user synchronization is proved.
- **VERIFIED IN SOURCE:** `local.ts:112-118` seeds when metadata is absent. `seed.ts:1-7` describes examples derived from existing portal pages; these records/dates/actors are not verified production authority.
- **VERIFIED IN SOURCE:** `list/get` at `149-178` sorts local arrays by `urutan` and finds records by ID **without publication filtering**. `update` at `192-208` merges patches and records client time/activity. This is management adapter behavior, not an unsafe implemented public API.
- **VERIFIED IN SOURCE:** HTTP adapter makes generic administrative collection requests; failure throws ApiError, not automatic fallback to LocalApi. Laravel route source does not implement those content requests.

### B2. Four independent production-source decisions

| Canonical module | Current observed source / read-write behavior | Duplicate/derived public representation | Intended authoritative production source / status | Missing mapping / implementation dependency |
|---|---|---|---|---|
| `berita` — Berita incl. Pengumuman | **VERIFIED IN SOURCE:** Berita collection in browser IndexedDB/memory; local list/get/create/update and workflow patch; optional HttpApi administrative calls with no content server implementation | Static `informasi.html`, single `berita-detail.html` and homepage announcement; seeds derive from examples but states/content layout differ | **PENDING:** no single production authority established; neither local records, static files, selected adapter nor Laravel designated truth | Owner/data decision on authoritative accepted records/storage/update source; category/public-intent and current state mapping; separate migration/data quality review, no automatic seed import |
| `layanan` — Layanan incl. Persyaratan | **VERIFIED IN SOURCE:** Layanan collection with ordered nested requirements; local service editor/patch; no independent requirement source/assignment | Static `layanan.html` tables and homepage checklist/service tabs; anchors, nested formatting/additional-service placement and some values differ from seed | **PENDING:** production aggregate/requirements truth unselected; no verified backend persistence/API resource | Reconcile approved actual service text/identity/slug/parent-nested representation/public eligibility; competent content owner verifies requirements, no legal service rules invented |
| `faq` — FAQ | **VERIFIED IN SOURCE:** Faq collection and display toggle; local ordering/updates, no workflow state in type | Static homepage seven FAQ answers with inline links/formatting not fully present in seed strings | **PENDING:** no authoritative production FAQ source; display flag cannot itself establish complete lifecycle/intent | Explicit FAQ source plus visibility/public-intent mapping and answer format; no automatic adoption of plain seed text over current markup |
| `halaman` — Halaman Statis | **VERIFIED IN SOURCE:** Halaman local editor/collection with slug/title/body, no publication state | Static privacy/terms pages coexist with seed/editor HTML, including draft/legal-review notes and structurally different prose | **PENDING:** no approved page/body/version or central truth; static existence is not API/public-intent approval for every Halaman | Approve included pages, authority/data review, slug-to-consumer mapping and lifecycle/public visibility capability; no publish-all from absent fields |

Laravel content API resource for **each row: not found in inspected route/controller source**. Current tables/data/database existence were not inspected via DB; no assertion that a domain table exists or that all databases are empty. Multiple coexisting sources are an evidence gap, not a license to choose one. MySQL is the target but planned hosting/persistence does not designate current content authority.

**INFERRED recommendation:** approve one centrally governed source per module with accepted-data provenance, then map public reads to it. Existing browser snapshots may contain examples, drafts or independent edits; static HTML may contain different content. No priority/merge/latest-timestamp rule is approved. Subsequent migration/integration needs review of source records and consumer identities, not silent imports.

## C. Publication and public-eligibility mapping

### C1. Source fields and module-specific missing capability

| Module | VERIFIED IN SOURCE visibility/state evidence | Missing/invalid/public-intent/timestamp/retention findings | Minimum PENDING decision or capability |
|---|---|---|---|
| Berita | Extends Entri and Beralur: `status` enum `draf`, `diperiksa`, `persetujuan`, `terbit`, `dikembalikan`, `arsip`; nullable `terbitPada`; `tanggal` separate date; `riwayat` history | No explicit public-intent field or verified server predicate. Client status can be arbitrary at runtime; imported data lightly validated. No verified scheduler or soft-delete flag in inspected type. Dates/timestamp not scheduling/public truth. | Approved current-state/public-intent mapping and validated source; exclude unknown/invalid states. Do not automatically equate category/flag/non-null date with eligibility. |
| Layanan | Same Beralur fields; Persyaratan only `id`, `dokumen`, `jumlah` strings | Nested requirements have no separate visibility. Parent/public-intent mapping unverified; source local removal filters array rather than approved soft deletion. No scheduling semantics proved. | Explicit parent eligibility and permitted nested projection; no requirement-ID lookup that bypasses ineligible parent; no invented child status/column. |
| FAQ | `Faq.tampil:boolean`, Entri fields; no Beralur/status/terbitPada | Visibility toggle expresses local display intent only; no distinct source draft/published/unpublished/history/public-intent model. No server filtering in public consumers. | Define how FAQ can distinguish current published/public-intended versus ineligible/retained-unpublished; approve whether toggle suffices under an explicit model or another capability is needed. No new field chosen. |
| Halaman Statis | Halaman has `slug`, `judul`, `judulLanjutan`, `pengantar`, `isi`, common Entri fields; no publication/status field | Source cannot reliably distinguish draft/published/retained-unpublished. Seed bodies explicitly contain draft-review text; existing public markup does not settle production eligibility. No timestamp/slug implies approval. | Approve page-source/current eligibility capability and unknown handling before exposure; absence of state must not become public-by-default. No enum/schema introduced. |

**VERIFIED IN SOURCE:** `patchAlur()` (`alur.ts:7-18`) sets `terbitPada` when target is `terbit`, but retains previous timestamp on every other transition. Non-null `terbitPada` therefore does **not** prove current publication eligibility. `alurBaru():22-29` sets local draft metadata. Source `AKSI_ALUR` (`meta.ts:150-162`) maps “Tarik dari portal” from `terbit` to `arsip`; this is a prototype action, not approval that current unpublish equals archival or deletion. The owner-confirmed retained-unpublish semantics prevail, with internal mapping PENDING.

No reviewed source establishes a complete scheduling, publication-intent or deletion/archival governance model. Array removal in LocalApi is not an approved production deletion policy. `sorotan`, `pengumumanBeranda`, `unggulan` are presentation hints, not authorization or publication proof. Unknown values may survive untrusted snapshots/runtime casts; TypeScript declarations are not server validation.

### C2. Required target visibility matrix — not current enforcement

| Conceptual condition | CONFIRMED public-read expectation | Application to each module / representation status |
|---|---|---|
| Current published AND public-intended | Eligible public representation using approved allowlist | Berita/Layanan exact mapping PENDING; FAQ/Halaman require their own visibility/public-intent mapping, not borrowed Beralur assumptions |
| Draft | Excluded from collection/detail/related/filter/count reads | Source Beralur draft present; FAQ/Halaman capability still PENDING |
| Unpublished | Excluded | Logical unpublish state not assumed to equal source `arsip`/`draf`; mapping per module PENDING |
| Not public-intended, even if another flag suggests publication | Excluded | Public-intent decision/capability PENDING for all four; no mandate for a new boolean column |
| Missing/invalid/unknown eligibility | Fail closed, no public representation | No fallback to all-visible or unfiltered seeds; exact malformed/error handling PENDING SPECIFICATION |
| Retained after unpublishing | Excluded publicly, stored/editable for authorized management | Retention is confirmed lifecycle requirement, not proved central persistence; files/history not deleted automatically |
| Republished and otherwise eligible | Eligible again | Direct authorized employee action, same canonical assignment; concrete state mapping/write/read boundary pending |

Successful authorized published edits must be visible on subsequent public reads without mandatory Administrator approval. Any chosen future freshness contract must meet that immediate-success requirement; PENDING caching details cannot weaken it into an invented TTL delay. No claim of cancelling in-flight reads or global CDN propagation is made. Already downloaded/copied content cannot be erased remotely.

## D. Category, nested content and public projection specification

### D1. Category and nested mappings

- **Berita/Pengumuman — VERIFIED IN SOURCE:** `KategoriBerita` includes `pengumuman`; `Berita.kategori` distinguishes it alongside `berita`, `keliling`, `edukasi`. **CONFIRMED:** shared Berita module/assignment. Future public projection/filter may expose an approved category discriminator; exact field/query schema remains PENDING. Missing/malformed category needs explicit validation/representation policy and must not produce a guessed notice type or bypass eligibility. No independent Pengumuman route/module/table/assignment is specified.
- **Layanan/Persyaratan — VERIFIED IN SOURCE:** `Layanan.persyaratan` is `Persyaratan[]`; child has `id`, `dokumen`, `jumlah` strings, no separate publication field. **CONFIRMED:** shared parent module/assignment. Future approved service projection may include allowlisted requirements only from an eligible parent; ordering, child-ID exposure, legacy validation and attachment/text handling pending. `jumlah` remains textual; do not infer document-count number/FK or new child status.
- **FAQ/Halaman:** their visibility models are independent source gaps. Never borrow Berita/Layanan workflow timestamps/status or pretend all static-page/editor records are public. Canonical identifiers remain `faq` and `halaman`.

### D2. Candidate public fields — PENDING SPECIFICATION, not approved schemas

All field names below come from SRC-01. Listing them proposes no final JSON key, requiredness, response envelope, database column or new DTO. A field displayed in static HTML does not prove an existing transformation from these names.

| Module | Candidate content fields supported by type evidence | Not automatically serialized / excluded administrative material | Source sufficiency and remaining decisions |
|---|---|---|---|
| Berita | `judul`, `slug`, `kategori`, `ringkasan`, `isi`; candidate `sampul`, `keteranganSampul`, `kreditFoto`, `tanggal` and public date presentation subject to explicit approval | Exclude raw `penyedia`, `pemeriksa`, `catatanRevisi`, `riwayat`, account/role/assignment/audit data and secrets. `status`, `id`, `dibuat`, `diperbarui`, `urutan`, `terbitPada`, `sorotan`, `pengumumanBeranda` require explicit public-use decision, not DTO spill. | `sampul`/`terbitPada` nullable; dates/cover/caption/credit/feature presentation and sanitized body/schema/lookup PENDING. No existing complete safe public DTO. |
| Layanan | `nama`, `slug`, `ringkasan`; candidate `loket`, `jenis`, `cekFisik`, `nonTunai`, `diwakilkan`, `label`, nested `dokumen`/`jumlah`, public service `catatan` and `unggulan` presentation subject to approval | Exclude raw workflow provider/reviewer/revision/history and private account/grants. Common Entri fields, `status`, `terbitPada`, parent/child IDs and order metadata not automatically public. | `catatan` may be public service copy, unlike internal `catatanRevisi`; decide each context. Validate arrays/strings/nesting/parent eligibility and official content, no invented field split or safe serialization claim. |
| FAQ | `pertanyaan`, `jawaban` | Do not emit `tampil`, `id`, `dibuat`, `diperbarui`, `urutan` just because in type; eligibility/order may be applied internally. No account/secret/internal data. | Source string does not settle plaintext versus HTML/link projection; null/malformed/missing policy and visible ordering PENDING. |
| Halaman Statis | `slug`, `judul`, `judulLanjutan`, `pengantar`, safely rendered `isi` | Do not expose common IDs/timestamps/order or arbitrary object properties automatically. Exclude unapproved management/private/credential data, even if legacy import adds them. | Empty optional titles/preamble, placeholder/draft source text, HTML/relative links and slug-to-page identity/public eligibility PENDING. No source publication-safe serializer exists. |

An `id` is not categorically forbidden nor automatically approved: decide resource lookup/public identifier exposure with rationale (stable public reference, enumeration/privacy, legacy mapping). Internal actor/account IDs and raw database keys must not leak unintentionally. Source slug may become a public reference if explicitly approved; current news detail links do not use it. Public timestamps/order/flags are also field-specific decisions, not blanket exclusion of legitimate presentation.

### D3. Transformations, absent/null/malformed data and separation

| Topic | VERIFIED IN SOURCE / INFERRED finding | PENDING public projection requirement |
|---|---|---|
| HTML | Berita `isi` is described as simple HTML; Halaman seed/editor body HTML exists. Tiptap constrains editor structure, not arbitrary API/imported content. | Approve maintained sanitization/rendering/tag/attribute/protocol policy and source validation. No server sanitizer proof, regex fix or automatic trust in editor output. |
| Dates/timezone | Date-only `tanggal` separate from ISO `terbitPada`/Entri timestamps. Public HTML dates/WITA strings are manually authored. | Approve calendar/instant precision/zone/format fields; do not schedule from date or silently UTC-shift dates, fabricate null dates or use timestamp as eligibility. |
| Links/media | Relative anchors/placeholder graphics occur publicly; LocalApi uploads return data URLs. | Approve source URLs/safe protocols/null cover behavior/storage/delivery/access and public intent; no arbitrary remote fetch, credential URLs, data-URL acceptance or public file revocation claim. |
| Empty/absent/null | Types provide some nullable fields, but UI/import casts do not guarantee valid runtime records. | Field-by-field reject/omit/normalize rules and source quality handling; no guessed default public state, coercion or new keys. Unknown eligibility excluded regardless of presentation fallback. |
| Nested requirements | `dokumen`/`jumlah` are strings; static HTML manually splits parenthesized notes and uses markup. | Explicit child allowlist/order/identifier rules; preserve meaning without inventing extra fields or exposing raw children/hidden parents. |
| Legacy/unexpected properties | Snapshot validation in LocalApi is lightweight; arbitrary fields can survive input. | Only explicitly approved fields projected, never spread/serialize arbitrary object/admin DTO. Deep validation/safe error policy required. |
| Public versus admin | Admin consumer expects full typed entities for editing/workflow. | Separate approved public representation even if same underlying source is selected later; do not strip/admin-mutate DTOs to implement public read or break existing consumers. |

## E. Public transport contract — concrete details still pending

**VERIFIED IN SOURCE:** inspected Laravel API file contains only `GET /api/health` with `{"status":"ok"}`. Web welcome route and framework `/up` are separate, not content APIs. Inspected controller directory contains only base Controller. No public content endpoint/DTO was found. This is route/controller source evidence, not a runtime route-list or DB inspection.

Administrative `HttpApi` uses collection GET calls, bare arrays/entities, JSON/error parsing, 204 handling and administrative 404-to-null. These existing source behaviors **do not define public routes/methods/codes/schemas** and must not be adopted without a contract decision. No new public path, version prefix or finalized empty/error body supplied here.

| Module | Resource/source identity evidence | Verified public endpoint/method/query/shape | Remaining concrete public contract |
|---|---|---|---|
| `berita` | Local ID/slug/category; static list/detail/announcement markup | **None found in inspected Laravel sources**; static HTML paths are not API endpoints | Collection/detail/category lookup layout, method/path/key, field allowlist/schema/nulls, filters/order/paging/limits, empties/missing/ineligible/errors/cache **PENDING SPECIFICATION** |
| `layanan` | Local ID/slug/service aggregate/nested requirements; static service anchors | **None found** | Parent/read/nested layout and approved fields/keys/filters/order/paging/status/error/freshness **PENDING SPECIFICATION**; no standalone Persyaratan endpoint inferred |
| `faq` | Local ID/questions/display order/toggle; static FAQ details | **None found** | List/detail need, public identity/display-text format and visibility mapping, transport/schema/empty/error/order/paging **PENDING SPECIFICATION** |
| `halaman` | Local ID/slug/title/body; static privacy/terms paths | **None found** | Approved included page identity/slug lookup/content schema/visibility/empty/absence/errors and freshness **PENDING SPECIFICATION** |

Semantic contract **CONFIRMED:** anonymous read-only eligible public collection/detail access; same server predicate for list, detail, related/nested results and any filters/counts. Valid empty eligible sets represent no eligible results, not unfiltered fallback. Missing/ineligible resource has no public content or management/existence/history disclosure; equivalent public absence handling is an **INFERRED recommendation**, exact status/body pending. Source failure is not silently relabeled empty or served from arbitrary seeds; safe error contract pending.

Pagination/filtering/order are not established by static chips or a decorative pager. Local `urutan` sort is admin/local behavior, not public paging policy. Any eventual totals/facets/links apply after eligibility and must not leak excluded records. Unsupported requests cannot expand into arbitrary collections/DTOs. Exact request validation, status codes/messages/body/envelope/tie-breaker/lookup/schema remain pending, with no new source field/route invented.

Freshness target remains immediate public effect after successful authorized published edits, public exclusion after unpublish and renewed eligibility after republish. Actual source/write-success/public-read/caching boundaries and failures must be specified/verified before code. No TTL, provider, invalidation algorithm, transaction or in-flight/global propagation guarantee claimed. Cache behavior cannot intentionally continue serving unpublished content beyond the approved consistency boundary or weaken settled immediate-success semantics.

## F. Existing consumer compatibility inventory

**VERIFIED IN SOURCE (Step 1):** public HTML stores content in static markup; inspected `assets/js/main.js` has no content `fetch`/`XMLHttpRequest`. `initFilters():511-536` hides/shows current DOM items by `data-cat` and text search; `initChecklists():154-161` counts checked inputs. Those are UI behaviors, not server publication predicates/API transformations. Header/footer copying in `sync-partials.mjs` is source-time copying, not content fetching.

| Consumer/source | Current data/format expectation and fallback | Compatibility risk / what must be preserved |
|---|---|---|
| `informasi.html`, `berita-detail.html`, homepage announcement | Hardcoded news/cards/detail/meta/dates/links; all inspected detail links target one detail file without ID/slug; DOM category search. No centralized backend fallback/request. | Define approved identity/link/paging mapping; preserve labels/cards/date meaning/links without silently binding unrelated seed article or turning admin array into public schema. |
| `layanan.html`, homepage service tabs/checklists | Hardcoded service anchors/requirement tables/checkbox text/counts. Seed service slugs differ from anchors, e.g. `perpanjangan-5-tahun` versus `lima-tahun`, `mutasi-ganti-pemilik` versus `mutasi-bbn`. | Reconcile anchors/slug and content/boolean/additional-service placement with owner-approved truth; do not automatically rename files/anchors or derive official requirements from examples. |
| Homepage FAQ section | Static native details/answers with inline links and formatting; some seed strings omit those links. No public `tampil` filtering. | Specify answer format/link handling/display order and own publication mapping; plain-string insertion may lose links or create unsafe HTML if guessed. |
| `kebijakan-privasi.html`, `syarat-ketentuan.html` | Static prose; seed/editor uses different nested paragraph/list/blockquote markup and draft notes. No verified runtime slug-to-file conversion/fetch. | Select reviewed public source and safe body/link rendering while preserving UI/copy; no auto-publish draft legal/prototype text or claim legal approval from existing page. |
| Dashboard LocalApi/forms | Arrays/entities, local merge/write/order, seeded init; settings may fall back to seeds; memory mode temporary | Keep administrative editing contract distinct; no automatic browser-to-server migration or identity/role trust. Local read lacks publication filter deliberately for management, not public exposure proof. |
| Dashboard HttpApi | Bare JSON arrays/entities, errors `pesan` then `message`, administrative 404-to-null, JSON for non-204 success. HTTP failure throws, **no automatic LocalApi fallback**. | Do not change adapter/response envelopes/filter out admin drafts or treat these paths as public; future integration separately authorized. |

**VERIFIED IN SOURCE examples of representation divergence:** `seed.ts:573` service is `persetujuan` while matching service content is hardcoded publicly; seed Halaman body includes draft-review statements without a status. **INFERRED conclusion:** static visibility and local workflow/flags are not synchronized and cannot establish a single authoritative publication predicate. These differences are reasons to reconcile accepted content, not runtime evidence of a leaking Laravel endpoint.

No public DTO-to-HTML transform or server-sanctioned source fallback found in reviewed code. Consumer behavior was source-inspected, not browser-tested. Future integration must explicitly preserve existing public wording/design/links and separate admin contracts; no migration/adapter/UI change approved or performed in this phase.

## G. Security requirements, planned acceptance and handoff

### G1. Future read security/consistency requirements

- Anonymous reads grant no write, deletion, User Management or module-assignment rights. Protected management checks remain separately required for active identity/current assignments/actions; public read does not bypass private module management boundaries.
- Server applies current publication/public-intent eligibility before allowlisted projection, all collection/detail/related/nested queries, filters/order/pagination/counts. No frontend flag/known ID/status filter/preview bypass.
- Draft/unpublished/private/unknown/invalid eligibility excluded; missing publication representation is not default-public. Ineligible Layanan parent excludes nested requirements; Pengumuman shares Berita mapping/assignment, not an independent scope.
- No arbitrary admin DTO serialization, actor/review/history/assignments/private-account/audit/credential leakage; sanitize approved rich text and media under future explicit policy. Logs/errors exclude secret/environment/path/stack data.
- Unpublish removes subsequent public representation while retaining approved underlying record; republish restores eligible content; successful published edits visible immediately. Actual caching/failure/in-flight/read-consistency contract must be verified, without fake global invalidation or weakened timing guarantee. File/copy history and recovery policy separate.

These are target requirements, not existing filters/authorization/audit. They do not require anonymous readers to authenticate or settle MFA/secure administrative session configuration.

### G2. Planned acceptance test matrix

**All rows: PLANNED — NOT IMPLEMENTED OR EXECUTED.** No test files/fixtures/runners, API probes, DB connections/SQL or migrations added/run. Use approved isolated synthetic source data and future concrete contract; absent policy/transport/source means BLOCKED execution, not a passing guessed default.

| ID | Setup/action | Objective expected result / dependency |
|---|---|---|
| AC11-01 | Eligible current Berita read | Public representation returned only under approved source/state/intent/field contract |
| AC11-02 | Pengumuman category content/assignment mapping | Same Berita module boundary/eligibility; no independent module/assignment |
| AC11-03 | Eligible current Layanan read | Safe public service representation under own approved mapping |
| AC11-04 | Eligible/ineligible parent with nested Persyaratan | Nested values only from eligible parent, same Layanan boundary and approved child allowlist |
| AC11-05 | FAQ visible/hidden/ambiguous fixtures | Uses FAQ-specific approved publication/public-intent mapping, not borrowed Beralur or unverified toggle assumption |
| AC11-06 | Halaman published/draft/unknown fixtures | Uses its own explicit approved mapping; absent source field never publishes all records |
| AC11-07 | Draft target via collection/detail/related/known lookup | Excluded, no private metadata or existence/history leak |
| AC11-08 | Unpublished-retained target | Excluded from all public read surfaces, management retention separate |
| AC11-09 | Published-flagged but non-public-intended record | Excluded despite status/time/presentation hint |
| AC11-10 | Missing/invalid/unknown eligibility and malformed legacy input | Fail closed; no public fallback, field coercion or invented category |
| AC11-11 | Authorized source unpublish followed by public read | Publicly excluded, underlying record retained under approved persistence lifecycle; write fixture/integration separately authorized |
| AC11-12 | Authorized source republish followed by public read | Eligible again without mandatory Administrator approval/record recreation |
| AC11-13 | Authorized published edit completes, then read through actual source/cache path | Immediate updated representation under approved success/freshness contract; no delayed review gate |
| AC11-14 | Source contains extra internal fields/private synthetic markers | Only approved allowlist emitted; no raw workflow/review/account/grant/history or secret material |
| AC11-15 | Nested/related lookup attempts ineligible records | No parent/child/category/related-content bypass or arbitrary nested-object serialization |
| AC11-16 | Same fixtures requested through collection and detail | Equivalent current eligibility and safe projections, no hidden detail bypass |
| AC11-17 | Filters/page/order/facets/counts applied to mixed eligibility | Excluded records and counts not leaked, caller cannot disable predicate; concrete paging/filter contract needed |
| AC11-18 | Empty eligible collection; nonexistent and ineligible targets; source outage | Approved empty/absence/error contract obeyed without guessed codes/shapes, private disclosure or unfiltered seed fallback |
| AC11-19 | Existing public/admin consumer contract checked against approved future read API | Links/anchors/text/HTML/date behavior and admin arrays/error handling not silently broken; integration scope authorized |
| AC11-20 | Anonymous consumer attempts mutation/private management/deletion | Public read confers no administrative/write rights; no extra application role or scope granted |
| AC11-21 | Non-null old `terbitPada`, invalid status, FAQ `tampil`, Halaman missing state | None alone bypasses approved current published AND public-intended rule |
| AC11-22 | Null/missing/malformed cover/body/date/child strings and synthetic secret errors | Approved omit/reject/normalize/error handling, no invented field/default-public coercion or unsafe HTML/media leak |

Existing tests inspected in Step 1 cover Laravel health/JSON errors/scaffold and local static-serving boundaries; they are not publication/projection suites. No matching frontend content test/spec files found under inspected `dashboard-app/src`. Historical runtime passes are not newly executed tests. Acceptance of this document is formatting/source/governance review, not application behavior passing.

### G3. Readiness and closure register

| Item | Evidence status | Required next decision/evidence / implementation effect |
|---|---|---|
| Four modules/category/nesting, two-role/peer/direct-publication restrictions | **CONFIRMED** | Settled; do not reopen Pengumuman/Persyaratan assignments or add review roles |
| Local/static/types/adapters/routes/tests observed in Step 1 | **VERIFIED IN SOURCE** | Inform design, not production state/endpoint/security proof |
| Central production truth and accepted records for every module | **PENDING** | Owner/data custodians select approved source/provenance/reconciliation; do not adopt browser/HTML/seed or Laravel by assumption |
| Current publication/public-intent mappings | **PENDING** | Approve Berita/Layanan predicate and separate FAQ/Halaman capability, unknown/malformed behavior and state-change consistency; no columns/enums invented |
| Public projections | **PENDING** | Approve candidate field allowlists/ID/date/order/null/HTML/media/nested rules and disclosure review; no final public DTO here |
| Public transport | **PENDING SPECIFICATION** | Exact routes/methods/lookup/schema/codes/filter/paging/empty/error/absence contract; existing admin calls do not settle it |
| Public consumer integration/freshness/recovery | **PENDING** | Reconcile actual link/anchor/content/body differences and select verified successful-change/public-read consistency/caching; no global guarantee invented |
| MySQL/test DB/schema/read source access and recovery | **BLOCKED** | Privately approved access/grants/TLS, verified isolated test target and separately authorized safe connectivity/model/recovery; no DB work from this doc |
| Production infrastructure/origins/deployment | **BLOCKED** | Actual Cloud/domain/resources/secret/environment/operations/release evidence; no platform documentation equals account readiness |
| Protected authentication/MFA/session/permissions | **PENDING** policy / **BLOCKED** dependent protected implementation | Separate security approver/policy/config/lifecycle gates remain; anonymous public read needs no login/MFA, protected writers remain gated |
| Public-data/security/audit governance and explicit implementation scope | **PENDING** | Actual authorities approve content classification/projection/sanitization/operational requirements; user separately authorizes exact read slice after relevant prerequisites |

No runtime implementation is ready merely because this specification is complete. Independent non-executable mapping/projection/contract review may proceed under its task. Source-dependent public endpoints/filtering/projection/DB tests remain blocked until their decisions/access/scope close. Missing unrelated production MFA/Cloud resources does not create a login requirement for public readers or prevent a later separately approved local slice, but cannot waive source/data security/test prerequisites. Administrator application identity is known; separate security/data/infrastructure mandates remain `NOT IDENTIFIED — OWNER CONFIRMATION REQUIRED` where not evidenced.

### G4. Recommended Phase 2.12 handoff

Obtain the smallest concrete decision packet rather than repeating settled module policy:

1. Owner/data custodians choose accepted authoritative production sources and reconcile static/local examples, then approve current publication/public-intent capabilities **independently for FAQ and Halaman** as well as Berita/Layanan parent/category mapping.
2. Approve per-module public allowlists and representation rules, including identifiers, HTML/media, null/malformed legacy records and consumer links/anchors; define concrete public transport/empty/error/filter/order/paging and freshness success boundary.
3. Verify relevant MySQL/test/access and data/security/operations evidence through separately authorized safe work, then request a bounded implementation slice with exact files/contracts/test target/exclusions. Do not treat this packet or documentation as authorization to create schemas/endpoints/UI changes.

Phase 2.12 is not started. No unnecessary phase simply to restate confirmed boundaries; actual decisions and technical prerequisites are the blockers.

## H. Validation, preservation and isolated publication

Only `backend/docs/PHASE-2.11-AUTHORITATIVE-CONTENT-SOURCES-VISIBILITY-MAPPING-AND-PUBLIC-PROJECTION-SPECIFICATION.md` is created. Validate complete Markdown/owner/evidence distinctions, four modules/category/nesting, accurate citations from Step 1, secret-free content, whitespace/newline, `git diff --check` and unchanged pre-existing diff/inventory excluding this file. Recheck branch/origin/status immediately before staging. Stage exact literal path only, inspect full staged diff and `git diff --cached --check`; commit `docs(pintu): specify authoritative content and public projections`, normal push to `origin laravel-development`, verify single-file commit/parent/remote/upstream equality and all 385 pre-existing paths unchanged.

Stop for unexpected repository edits, staging, conflict/divergence/isolation/hook/access failure; no add-all, cleanup/reset/discard/restore/history rewrite/amend/force or previous artifact inclusion. No application/tests/config/dependency/env/UI/DB changes, SQL/connection/migration, auth/MFA/role/module/public API/projection code, cache/audit/credential delivery or deployment. Git publication is not external deployment authorization. Actual validation/review/commit/push reported after commands; no unexecuted test claimed passing.
