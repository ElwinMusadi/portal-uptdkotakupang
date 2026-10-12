# PINTU — Canonical Content Module Boundaries and Public-Read Specification

**Phase:** 2.10. **Date:** 2026-10-12. **Status:** confirmed module boundaries and specified semantic first slice; documentation only, implementation not authorized.

## 1. Purpose, scope and change-control baseline

Resolve Phase 2.9's category-to-module questions using the owner's authoritative mapping, and define the first future slice: **public-read contract and server-side filtering of published content**. Exactly two application roles remain `Administrator` and `Pegawai UPTD`; owner remains `PINTU Project Owner (Application Administrator)`. No new role, module, endpoint, payload field, database state or infrastructure approval is inferred.

Six prototype content concepts map to **four** canonical assignment modules. Pengumuman belongs to Berita and Persyaratan belongs to Layanan. The owner has resolved these boundaries; do not ask whether they should be separate modules again. Internal schema, transport, publication/public-intent mapping and source of truth still require specification. Earlier documents remain unchanged, with current decisions taking precedence only for the exact settled mapping.

Exclusions: application PHP/JS/TS/HTML/CSS/UI/copy changes, routes/controllers/models/services/middleware/policies/migrations/seeders/factories, tests, dependencies, environment/configuration, authentication/MFA/account or assignment APIs, publication writes/workflow, data import, database operations, cache/CDN/audit code, build/deployment and cleanup. This document does not authorize application implementation by itself.

### 1.1 Initial Git audit

Working directory/repository root: `D:\Herd\portal-uptdkotakupang`. Branch `laravel-development`, upstream `origin/laravel-development`, remote `origin` verified, preflight local/remote tip `ed70576a3b10328abb7710dd8578b04af6430357`. No divergence or staged changes. Raw NUL-separated porcelain counted **385 paths = 6 tracked unstaged modifications + 379 untracked files; 0 staged, 0 deleted, 0 renamed**. This matches Phase 2.9, rather than assuming historical counts. Normal status collapses untracked directories; duplicated combined terminal summaries are not exact file counts.

Pre-existing tracked edits: `.gitignore`, `README.md`, `dashboard-app/README.md`, `dashboard-app/vite.config.ts`, `docs/RANCANGAN-UIUX.md`, `index.html`. All untracked backend, earlier architecture documents, handover, skills/editor/tooling and scripts are preserved. New intended deliverable did not exist. Binary tracked-diff fingerprint `cac31712e8536b629d9fec47bb290ddea1f9c29e`; existing-file inventory fingerprint `b49dad62db0eb3cee3d2afd9cdbc57464c3e705e` in Git output order. Final comparison excludes only this new document. No branch switch/history rewrite or source cleanup.

### 1.2 Evidence reviewed and status vocabulary

| ID | Actual evidence | Verification boundary |
|---|---|---|
| EV-01 | `backend/docs/PHASE-2.9-CONTENT-MODULE-CATALOG-AND-PUBLIC-API-CONTRACT.md`, Sections B-J | Source-verified six concepts, shared news/service structure, multiple assignments/revocation/public semantic policy, unresolved concrete API |
| EV-02 | `backend/docs/PHASE-2.8-CONTENT-AUTHORITY-AND-PUBLICATION-SEMANTICS.md`, D28/MA and lifecycle | Module/peer authority, immediate edits, retained unpublish/direct republish; no delayed Administrator review |
| EV-03 | `backend/docs/PHASE-2.7-CONTENT-AND-ACCOUNT-LIFECYCLE-DECISIONS.md`, D27/R27 | Existing account/credential direction unchanged; security and infrastructure not closed by content mapping |
| EV-04 | `backend/docs/PHASE-2-ARCHITECTURE-AND-API-DESIGN.md`, Sections4/7/9/11 | Unapproved source-of-truth/model/revision/API recommendations, governance and persistence gaps |
| SRC-01 | `dashboard-app/src/lib/api/types.ts:20-57,76-93,127-154,219-225,287-300` | Existing `Entri`/`Beralur`, category/nested shapes and collection keys; no approved backend schema |
| SRC-02 | `dashboard-app/src/lib/api/local.ts:74-87,112-151,172-208` | IndexedDB or temporary memory, seeded initialization, local arrays/mutations and ordering; per-browser data is not server truth |
| SRC-03 | `dashboard-app/src/lib/api/http.ts:31-75,77-90`, `dashboard-app/src/lib/api/index.ts:9-12` | Administrative adapter selection/read/write signatures and JSON parsing; not existing public API contract |
| SRC-04 | `backend/routes/api.php:6-10` | Only application health route in API file; no content-read endpoints/filter/public projections |
| SRC-05 | `dashboard-app/src/app/nav.ts`, `dashboard-app/README.md`, `informasi.html`, `layanan.html`, `index.html`, `kebijakan-privasi.html`, `berita-detail.html` | Existing navigation/public prototype mappings from EV-01 and read-only source review; static pages do not establish central live content integration |

Evidence paths exist locally and relevant current contents were read directly or through read-only architectural review. EV-04 and Laravel foundation remain local/untracked; this commit does not publish earlier artifacts or promise they exist in a clean remote checkout. No missing source is claimed reviewed. Historical tests in preceding reports are not rerun or used as new feature success claims.

Use these classifications: **CONFIRMED** = explicit owner decision; **VERIFIED IN SOURCE** = inspected repository behavior/shape, not approval; **PENDING** = technical contract/policy not settled; **BLOCKED** = required actual access/verification/authorization absent. Recommendations are labeled as recommendations and remain PENDING for adoption. No documentation completion is proof of implemented filtering or permission enforcement.

## 2. Canonical module registry

Canonical **documentation identifiers** below reuse existing collection keys and are mapped to the owner's display names. They are not deployed assignment IDs, database values or public route paths. No application identifier is renamed. Candidate `halaman-statis` maps to current `halaman`; candidate `informasi-layanan` maps to current `layanan`.

| Documentation identifier / canonical display | Included concepts / verified prototype mapping | Assignment boundary (CONFIRMED) | Conceptual public eligibility / known gaps |
|---|---|---|---|
| `berita` / **Berita** | `Berita` collection, news and **Pengumuman** category. Source `KategoriBerita` has `berita`, `pengumuman`, `keliling`, `edukasi`; combined dashboard menu `Berita & Pengumuman`. | One Berita assignment governs authorized content/category actions, including Pengumuman. **No independent Pengumuman assignment/module**. Source category labels do not create extra modules. | Only current published AND public-intended records; source `Beralur.status`/`terbitPada` is prototype evidence, not approved persistence/public predicate. Extent/validation of other existing category values and public allowlist require verification, not another module. |
| `layanan` / **Layanan** | `Layanan` collection; candidate informasi-layanan and nested `persyaratan: Persyaratan[]`; existing nav `Persyaratan Layanan`. | One Layanan assignment governs service information and nested Persyaratan. **No independent Persyaratan assignment/module** or separately invented parent/child grant. | Parent's current published/public-intended eligibility controls included requirement data; exact state/field projection/public-intent and attachment/parent-child representation remain PENDING. |
| `faq` / **FAQ** | `Faq` collection and existing `Tanya Jawab` navigation/public section; source `tampil:boolean`, no `Beralur`. | Separate FAQ canonical assignment. No change to existing Indonesian navigation label. | Safe mapping of source display flag to published/public-intended and retained-unpublish lifecycle requires explicit definition; not automatically `tampil=true` means all requirements met. |
| `halaman` / **Halaman Statis** | `Halaman` collection and editor, candidate halaman-statis; static public pages. Source includes slug/body fields but **no publication/status field**. | Separate Halaman Statis assignment; collection key remains `halaman`, no key renamed to candidate slug. | Approve included page/slug source and visibility/public-intent representation before any public read; absence of field never means publish all. |

**Authoritative six-to-four mapping:** berita -> Berita; pengumuman -> Berita; informasi-layanan -> Layanan; persyaratan -> Layanan; faq -> FAQ; halaman-statis -> Halaman Statis. This resolves EV-01's independent-category/child-scope alternatives. No separate assignment record or authorization module for Pengumuman/Persyaratan; neither is a role. Module registry does not prove backend resources/tables/routes/filtering/assignments exist.

Public representations may distinguish category or display nested values without creating security modules. Exact assignment persistence/API identifiers and module lifecycle/version/validation remain PENDING, with these four boundaries fixed. Canonical display names are owner decisions, existing keys are source facts, internal assignment storage is not inferred from either.

## 3. Role and permission boundaries

The matrix is owner policy, not implemented endpoints. Protected module management differs from unauthenticated public browsing of eligible content. Active account/auth/security controls still apply to administrators. No review/approval role or creator-only ownership gate added.

| Operation | Administrator | Pegawai UPTD |
|---|---|---|
| Protected read/manage content in authorized modules | Full approved application content access | Allowed only with current applicable canonical module assignment/action |
| Manage peer-created content in assigned module | Allowed within full approved scope | Allowed; creator alone neither grants nor denies authority |
| Publish/edit published/unpublish/republish authorized content | Allowed within supported scope | Allowed directly within assigned canonical module; no mandatory Administrator approval |
| Protected access outside assigned modules | Full approved application scope, not conditioned on employee assignments | Denied; public eligibility does not grant management |
| Delete content | Administrator only under supported deletion policy | Denied, including indirect destructive/bulk bypass |
| User Management, roles, account status and module assignment administration | Administrator only | Denied; no self-assignment or scope expansion |
| Ordinary public read | Same public representation as any public consumer | Same public representation regardless of protected assignment; no privilege leakage |

A Pegawai may hold multiple assignments; removal of one preserves the others. Subsequent protected requests after revocation must deny the removed module **without re-login**, evaluating current trusted assignment or an equally prompt guaranteed mechanism. Client state/hidden controls/stale caches cannot restore access. In-flight handling remains separately specified, not cancellation claimed. These controls are confirmed requirements carried forward, not part of implementing the public-read slice or current enforcement evidence.

## 4. First implementation slice definition — public read only

**Named first future slice:** public-read contract and server-side filtering of published content across `berita`, `layanan`, `faq`, `halaman`. First slice is **unauthenticated, read-only consumption**, not writable content administration. No account creation/authentication/MFA/assignment management, publication write endpoints, migrations, UI/adapter/HTML integration or deployment is included automatically. Future persistence preparation or UI consumption may require separate explicitly authorized work, not hidden scope expansion.

Semantic scope:

- Server applies current published **and** public-intended eligibility to list and single-resource reads, including any approved categories/nested values/filter/order/paging surfaces.
- Exclude draft, unpublished and other non-public content; no caller parameter/ID/slug/preview flag can disable the predicate.
- Only an approved public field allowlist is projected, never entire administrative DTOs/internal metadata/credentials/private review or audit data.
- Berita includes Pengumuman under the same canonical module; Layanan includes Persyaratan under parent eligibility. Neither creates independent assignment or assumes a standalone read endpoint.
- Empty eligible sets are valid no-content collection results; missing/ineligible resource yields no public representation or internal state disclosure. Exact transport/body/status details remain **PENDING SPECIFICATION**.
- Successful authorized edit/unpublish/republish of source content must be reflected in subsequent public reads consistently with owner semantics. Write operations are outside the read slice; their source-of-truth/update boundary must be established rather than mocked as production capability.
- Safe, non-sensitive error behavior and direct-request negative tests define disclosure protection. Reads do not mutate state or grant write/assignment privileges.

No routes, HTTP methods, status codes, schemas, database columns/enums, paging/order/filter parameters or success envelopes are approved from this specification. The slice is bounded but **not yet implementable against an authoritative source** until Section8's concrete mapping/contract/access prerequisites close and a new task authorizes it.

## 5. Public-read contract specification

### 5.1 Resource boundaries and source of truth

| Canonical module | Verified existing content source / consumer evidence | Authoritative source for future public API |
|---|---|---|
| `berita` / Berita | Prototype `Berita` data stored in browser IndexedDB/memory via LocalApi; public `informasi.html` / `berita-detail.html` markup; administrative HTTP adapter can be selected | **PENDING SPECIFICATION**: centrally approved persistence/read source and update/public-intent mapping. Neither browser snapshot/seed nor static copy is automatically authoritative. |
| `layanan` / Layanan | Prototype service plus requirements array; static `layanan.html` and homepage service information | **PENDING SPECIFICATION**: service aggregate/current visibility, nested requirement source and safe projection. No DB FK/table/file source assumed. |
| `faq` / FAQ | Local Faq array/display flag; homepage FAQ section | **PENDING SPECIFICATION**: published/public-intended mapping and current read source, not just client `tampil`. |
| `halaman` / Halaman Statis | Local Halaman editor data; static informational/privacy pages | **PENDING SPECIFICATION**: page/slug inclusion, current source and publication/public-intent representation absent from prototype shape. |

Browser adapter choice (`VITE_API_URL` or LocalApi) is frontend configuration, not proof that a server currently implements the selected collection endpoints. `backend/routes/api.php` implements only `GET /api/health`, not the four-module contract. Default scaffold models/migrations are not domain tables, and live database existence is unverified. Never publish all Halaman or seed records because source lacks visibility fields.

### 5.2 Eligibility and consistency requirements

| ID | Acceptance requirement | Source/policy versus unresolved mechanism |
|---|---|---|
| PR10-01 | Only **currently published AND public-intended** records can produce public representations; apply consistently before projection and any aggregate totals/facets | Owner **CONFIRMED**; eligibility signals per module **PENDING SPECIFICATION** |
| PR10-02 | Draft/unpublished/non-public content absent from lists/details/lookups; frontend filtering cannot substitute for server enforcement | Owner **CONFIRMED**; actual source query/filter implementation unimplemented |
| PR10-03 | Unpublish removes subsequent public visibility but retains stored/editable record for authorized management | Owner **CONFIRMED**; internal enum/column/transaction/visibility-delivery **PENDING**; not archive/delete/files erasure |
| PR10-04 | Republish eligible retained record restores subsequent public visibility under same authority boundary | Owner **CONFIRMED**; no mandatory Administrator sign-off or recreation |
| PR10-05 | Authorized successful edit of published content is reflected immediately in subsequent public reads | Owner **CONFIRMED**; actual public read/update/success/cache/replica/error consistency design must be verified, no chosen TTL/purge/invalidation code |
| PR10-06 | Unknown/ambiguous public eligibility does not become default-public; no secret/private/raw admin projection | **Recommended fail-closed read design** supporting confirmed exclusion; approved mapping/field lists and failure protocol pending |

Prior statuses `draf`/`terbit` and `tampil` are source facts, not approved backend predicates. `terbitPada` alone, a client boolean, known slug or category cannot prove both required eligibility conditions. Eligibility/public intent might be represented by approved fields or module/data classification; exact design is pending, not a mandated new boolean. Other prototype workflow values remain non-public where approved mapping defines them; do not introduce a new required review flow.

### 5.3 Safe public projections

Approve an explicit public field/type/nullability/media/HTML/identifier allowlist per module before implementation. Source shapes can inform candidate fields but are not the public payload. Internal provider/reviewer/revision notes/history, role/module assignments, account/audit/private data and credential/session/recovery material are not automatically exposed. Do not infer every source timestamp/order/ID is public or select a new envelope.

Verify rich-text safety and URL/attachment behavior where actual returned content needs them; no new sanitizer/provider chosen here. Retained attachments after unpublish need approved public/private access semantics—record retention does not imply private file enforcement or removal of independently accessible copies. No server fetch of arbitrary media URL or imported browser data assumed.

### 5.4 Empty, absence and errors

- **Valid empty collection:** represent that the requested supported public collection has no eligible entries, not a server failure or disclosure of hidden totals. Exact body/schema/status is **PENDING SPECIFICATION**; no automatic empty-array versus envelope decision.
- **Missing/unpublished/no-longer-eligible target:** no public content representation, publication-state/internal-history/existence details or alternate bypass. Recommend equivalent public absence treatment; exact error/body/status mapping **PENDING SPECIFICATION**.
- **Unsupported module/query/input:** safe approved error, no dynamic model discovery, private data or broadened filters. Parameter validation and exact statuses/messages are pending.
- **Source/store/consistency failure:** safe generic error, no stack/path/environment/credentials and no fallback to unfiltered seeds/private data. Do not misrepresent unavailable verified data as published content. Exact outage/empty-versus-error semantics pending.
- Filter/order/paging/link/total metadata, if later approved, must use the same eligibility predicate. Private records must not leak through counts/facets or direct lookup. No transport codes/keys guessed.

### 5.5 Concrete contract status and compatibility

| Item | Current specification status / necessary decision |
|---|---|
| Public paths, HTTP methods, lookup keys and resource/category/nested read layout | **PENDING SPECIFICATION**; canonical identifiers are not URL paths. Existing `/api/health` is the only cited current API. |
| Response schemas/allowlists/nullability/HTML/media and error/absence/empty codes/bodies | **PENDING SPECIFICATION**; no raw `Koleksi` interface or generic admin endpoint adopted automatically |
| Pagination, limits, cursor/offset, ordering/tie-breaker, filtering/category selection | **PENDING SPECIFICATION**; LocalApi `urutan` sort and static chips/pager are source behavior, not approved public transport |
| Publication/public-intent representation and current consistent data source | **PENDING SPECIFICATION**; module-specific evidence gaps in Section 5.1, no current centralized source verified |
| Public consumers/integration/freshness/cache compatibility | **PENDING SPECIFICATION**; static prototype pages verified, central live binding unimplemented |

Preserve HTML/public wording/design and existing admin adapter behavior. `HttpApi.list` expects bare arrays and `get` maps its existing administrative not-found error to null; these are source facts, **not** approval for a public array/404 contract. Do not silently wrap/truncate/rename existing responses or replace admin transport with public projection. New public consumer integration is a separately authorized task, not included in this documentation or automatically the read slice.

Public reads are unauthenticated and confer no write/User Management/delete/module-assignment authority. Public visibility is checked on server regardless of browser role; revoking an employee assignment denies protected management but does not remove access the general public has to eligible representations. Private/protected reads cannot be relabeled public to bypass authorization. Authentication/MFA policy remains separate for protected actions, not an invented prerequisite to anonymous public browsing.

## 6. Category and nested-content semantics

### 6.1 Pengumuman within Berita

**VERIFIED IN SOURCE:** `KategoriBerita` includes `pengumuman`; `Berita.kategori` carries it. **CONFIRMED:** Pengumuman is a category/type in `berita` / Berita and uses the same assignment/permissions; no separate `pengumuman` module or assignment.

Public consumers may distinguish a verified category through an approved future public projection/filter; exact response/query representation remains **PENDING SPECIFICATION**, not a new independent endpoint. Category filtering must not bypass Berita publication/public-intent eligibility. Editing the source category does not grant a different assignment or create a new role/module. Other observed category values do not create canonical modules; their public validation/scope extent must be checked without reopening the Pengumuman boundary.

### 6.2 Persyaratan within Layanan

**VERIFIED IN SOURCE:** `Persyaratan` has source `id`, `dokumen`, `jumlah` string fields and `Layanan.persyaratan` is a nested array. These are prototype names/types, not an approved public field list or physical child table. **CONFIRMED:** Persyaratan belongs under `layanan` / Layanan, same assignment/permissions, no separate `persyaratan` module/assignment.

Public requirement representation follows **eligible current Layanan parent**; no nested lookup may reveal requirements belonging to unpublished/non-public service content. Consumers may identify ordered/nested requirements inside an approved service representation; independent read shape/ordering/IDs/projection remains pending. Do not split the aggregate into independent authorization scopes, bypass Layanan via a child ID, or infer document count is numeric (`jumlah` is currently text). Eligibility of included fields/attachments still requires public classification.

## 7. Objective acceptance criteria and planned scenarios

**All application scenarios: PLANNED — NOT IMPLEMENTED OR EXECUTED.** Source-document verification is completed in this phase; it is not a passing runtime filter/permission/API test. Use approved isolated synthetic source data and concrete contracts once Section8 closes. Symbolic visibility/fixture terms are not new database enums.

| ID | Future scenario / setup | Verifiable expected result / prerequisites |
|---|---|---|
| AC10-01 | Approved read source has published/public-intended and ineligible records across four modules | Only eligible current records represented; test per approved source mapping/allowlist |
| AC10-02 | Draft target in list/detail/filter/known lookup | Excluded everywhere, no private field/hidden-state leak |
| AC10-03 | Retained unpublished target in same surfaces | Excluded publicly; retained protected management source is not exposed |
| AC10-04 | Record whose source status suggests publication but public intent is not approved | Excluded; neither source flag nor category overrides eligibility |
| AC10-05 | Approved source undergoes authorized unpublish outside the read slice, then public read | Subsequent public result excludes it, underlying record retained/editable; source operation/integration fixture authorized separately |
| AC10-06 | Eligible retained record republished by authorized existing/future writer, then read | Subsequent public representation restored without record recreation/Admin approval; no writes implemented by read tests unless separately scoped |
| AC10-07 | Authorized published edit successfully completes, then read through actual public paths | Immediate updated representation; no review/revision approval gate or intentional stale-success; actual consistency/freshness mechanism validated |
| AC10-08 | Berita category is Pengumuman; assignment/read scope examined | Same Berita boundary, no independent assignment or sixth security module; category distinction only approved public representation |
| AC10-09 | Layanan with nested Persyaratan, eligible/ineligible parents | Same Layanan assignment; only eligible parent exposes approved requirements; no independent assignment/child lookup visibility bypass |
| AC10-10 | Internal/history/review/assignment/private/secret marker fields in source | Public allowlist excludes unapproved/internal/sensitive fields, safe errors no secrets/paths/stacks |
| AC10-11 | Valid collection with no eligible rows, missing/non-public target and source outage | Each follows concrete approved empty/absence/error contract, no invented fallback or hidden totals; currently blocked until protocol definition |
| AC10-12 | Existing public-page/admin consumer contracts compared with planned API adoption | No silent schema/UI/copy/lookup/paging break; exact integration scope and compatibility tests authorized before consumers change |
| AC10-13 | Client requests hidden content via raw ID/filter/status flag or no frontend filtering | Server still enforces current public predicate; client cannot weaken it |
| AC10-14 | Anonymous public consumer attempts mutation/protected management | No write/assignment/delete/User Management capability granted; protected implementation/tests remain separately scoped |

Where exact protocol/public field/source choices are pending, scenario is **BLOCKED for execution**, not failed scope or an invented default to claim success. Category/nesting tests can validate source mappings without implementing assignment administration. State-change consistency may use separately approved fixtures; read-only slice must not smuggle in write endpoints.

## 8. Dependencies, blockers and implementation readiness

| ID / item | Status | Evidence / closure required | Impact on first slice |
|---|---|---|---|
| DEP10-01 Four canonical modules and nested/category boundaries | **CONFIRMED** | Current owner mapping; Section2 plus source-compatible keys | Boundary question closed; do not reopen separate Pengumuman/Persyaratan grants |
| DEP10-02 Existing collection/category/child/types/prototype consumers | **VERIFIED IN SOURCE** | SRC-01-05 | Informs representation design, not authoritative server source or public payload |
| DEP10-03 Authoritative current content source per module | **PENDING** | Approved central read-source/ownership/inclusion and data validity, not untrusted browser seeds | No reliable public reads until selected and accessible; choose no guessed persistence |
| DEP10-04 Publication and public-intent mapping per module | **PENDING** | Explicit safe `Beralur`/FAQ/Halaman mapping and handling missing/ambiguous visibility; no all-public default | Required for correct server filtering, especially FAQ/Halaman and nested Layanan |
| DEP10-05 Concrete paths/methods/schemas/codes/empty/absence/errors/paging/order/filters | **PENDING** | Approved read contract with field/type/privacy/identifier semantics, compatibility and safe error decision | Prevent fabricated API; sufficiently bounded semantic contract is not exact transport readiness |
| DEP10-06 Current consumers and compatibility/freshness/integration design | **PENDING** | Verified public/static read paths and approved response/success consistency; cache/replicas if actually present | Maintain immediate edit/unpublish/republish; no UI/adapter/cache changes now |
| DEP10-07 Database/test access and persistence readiness if selected source uses MySQL | **BLOCKED** | Approved privately managed access/grants/network/TLS, confirmed test isolation and separately authorized safe connectivity/model/recovery | DB-dependent read implementation/tests blocked; no DB action from documentation. Alternative source not silently selected to bypass target. |
| DEP10-08 Public field/security/HTML/media classification and applicable audit/operations governance | **PENDING** | Owner/data/security reviewers approve allowlists/sanitization/media/error/success/recovery/observability requirements, actual authority specified | Anonymous reads do not waive privacy/secret protections; not a new auth/MFA requirement for public visitors |
| DEP10-09 Protected identity/admin/assignment/MFA/session configuration | **BLOCKED for dependent protected work** | Earlier lifecycle/security/authority/config gates unchanged, no new policy | Separate from unauthenticated reads; not required merely to browse public content, but no writer/management operational readiness claim |
| DEP10-10 Actual origins/Cloud/resources/deployment | **BLOCKED for production/integration** | Approved verified environments/domain/security/secret/operations/release ownership | Narrow future local slice may be scoped without every production resource; no deployment ready/canary approval |
| DEP10-11 Explicit bounded implementation instruction | **PENDING** | User authorizes exact source/contracts/files/test target/dependencies/exclusions after relevant closure | This document itself does not grant implementation |

Design registry **complete and confirmed**; first read slice **specified semantically, concrete contract/source/filtering readiness PENDING/BLOCKED**. Authentication/MFA and production gates are not newly approved; equally, they must not be used to invent an authentication requirement for anonymous reads. No global Phase3 authorization. No actual migration, domain table, endpoint/filter/test or permission claimed implemented merely because a concept exists.

## 9. Phase 2.11 handoff

Phase2.10 establishes four canonical module boundaries, fixed Pengumuman/Persyaratan membership, retained two-role/assignment/permission rules and the public-read/filtering-first slice's safe semantic acceptance contract. No scope decision remains about whether those two concepts get separate assignments: they do not.

**Phase2.11 should investigate/specify the smallest remaining read contract:** authoritative source and publication/public-intent mapping per module (particularly FAQ and Halaman Statis), precise public field projections/transport/empty/error/lookup/paging decisions, and actual public consumer/freshness compatibility. Use real source gaps rather than adding fields/routes by assumption. Decide necessary security/data classification and MySQL/test-readiness evidence before DB-dependent implementation; no credential values in documentation. The owner application role is known; separate data/security/infrastructure mandates still require evidence where applicable.

Then request a new explicitly bounded implementation task once relevant DEP items close. Do not start Phase2.11 or application work here; no further documentation phase solely to repeat settled canonical boundaries. Readiness depends on concrete choices/verified prerequisites, not the volume of specifications.

### Validation and isolated publication

Only `backend/docs/PHASE-2.10-CANONICAL-CONTENT-MODULE-BOUNDARIES-AND-PUBLIC-READ-SPECIFICATION.md` is created. Validate all four modules/two relationships, preserved owner permissions/public visibility, pending technical details, referenced paths, complete Markdown/secret-free content, `git diff --check` and exact pre-existing fingerprints/status. Stage literal path only, inspect full staged diff/`git diff --cached --check`, commit `docs(pintu): define canonical modules and public-read slice` and normal push to `origin laravel-development`; verify single-file commit/parent/remote synchronization and all 385 existing outstanding paths intact. Stop for branch/divergence/staging/isolation/hook/access failures; no bulk staging, cleanup/reset/restore/discard/amend/force or prior-artifact inclusion. Unexpected unrelated staging is a blocker unless only that path can be safely unstaged without altering its content.

No runtime/app tests, SQL/DB connection/migration, auth/MFA/authorization/assignment/content API/workflow/cache/audit implementation, dependencies/env/config/UI/source or deployment changes. Existing ignored local artifacts stay untouched; no secrets read/printed. Actual validation/commit/push outcomes reported after commands. Phase2.11 and Phase3 have not started.
