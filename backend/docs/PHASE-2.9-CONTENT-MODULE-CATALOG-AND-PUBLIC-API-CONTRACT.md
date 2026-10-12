# PINTU — Content Module Catalog and Public API Contract

**Phase:** 2.9. **Date:** 2026-10-12. **Status:** source-verified category catalog and owner-confirmed semantic requirements; documentation only, no application implementation or Phase 3 authorization.

## A. Purpose, scope and preservation

Verify six owner-selected content categories against actual public portal/dashboard/interface/product sources, specify multiple module assignments with immediate subsequent-request revocation, and define the minimum **semantic** public-read contract. Exactly two roles remain **`Administrator`** and **`Pegawai UPTD`**; owner remains **`PINTU Project Owner (Application Administrator)`**. No extra role, legal identity, endpoint, field, schema, table, infrastructure resource or security approval is invented.

Owner selection is confirmation to investigate these six categories, not proof of six independent implemented modules. Product concept existence, current prototype shape, assignable security-module definition and runtime public API readiness are separate conclusions. Existing news/category and service/nested-requirement coupling must not be hidden by a six-row catalog.

Preflight: branch `laravel-development`; local/remote tip `ee88bcc4c7262f88bdd1d68d1046761acbcb2867`, no divergence/staged changes; **385 outstanding paths = 6 tracked unstaged modifications + 379 untracked files**. Same Phase 2.8 baseline. New deliverable path did not exist. Tracked diff fingerprint `cac31712e8536b629d9fec47bb290ddea1f9c29e`; existing-content inventory fingerprint `c8fc6f7acf62ba7de687afa103601c79fd1193dc` in Git output order. Final comparison excludes only this new document. All pre-existing UI/copy/source/config/dependencies/backend/documents/tooling/handover remain unchanged; no cleanup.

## B. Source inventory and verification method

Read actual source contents, not only old summaries. Static HTML and React forms prove prototype/product representation; comments/seeds/design recommendations do not prove authoritative server persistence, approved security-module boundaries, official service rules or integration. No runtime browser/HTTP/DB test was performed.

### B1. Preceding decisions reviewed

| Evidence ID | Repository document | Relevant evidence / publication |
|---|---|---|
| EV-01 | `backend/docs/PHASE-1.1-SECURITY-AND-INTEGRATION-DISCOVERY.md` | Historical source/adapter/static-health audit; local/untracked |
| EV-02 | `backend/docs/PHASE-2-ARCHITECTURE-AND-API-DESIGN.md` | Proposed domain/API/pagination/concurrency/security, local-identity decision; local/untracked |
| EV-03 | `backend/docs/PHASE-3-IDENTITY-AND-PERSISTENCE-FOUNDATION-SCOPE.md` | Bounded packages/stop gates; local/untracked |
| EV-04 | `backend/docs/PHASE-3-READINESS-GATE.md` | Tracked/remote; readiness conditions and independent preparation |
| EV-05 | `backend/docs/PHASE-2.2-SECURITY-AND-IDENTITY-LIFECYCLE-SPECIFICATION.md` | Tracked/remote; proposed identity/security/audit and planned tests |
| EV-06 | `backend/docs/PHASE-2.3-WORKING-TREE-AND-PHASE-3-READINESS.md` | Tracked/remote; exact outstanding-work/publication audit |
| EV-07 | `backend/docs/PHASE-2.4-GOVERNANCE-DECISIONS-AND-PHASE-3-AUTHORIZATION.md` | Tracked/remote; GOV decisions, authority/dependency gaps |
| EV-08 | `backend/docs/PHASE-2.5-GOVERNANCE-OWNERSHIP-AND-READINESS-UPDATE.md` | Tracked/remote; owner/two-role boundaries |
| EV-09 | `backend/docs/PHASE-2.6-CONTENT-PERMISSION-AND-ACCOUNT-LIFECYCLE-SPECIFICATION.md` | Tracked/remote; CP restrictions and candidate lifecycle |
| EV-10 | `backend/docs/PHASE-2.7-CONTENT-AND-ACCOUNT-LIFECYCLE-DECISIONS.md` | Tracked/remote; D27 account activation/credential direction and then-open content questions |
| EV-11 | `backend/docs/PHASE-2.8-CONTENT-AUTHORITY-AND-PUBLICATION-SEMANTICS.md` | Tracked/remote; D28 module/peer/immediate/unpublish/republish decisions and open catalog/assignments |
| EV-12 | Current explicit Phase 2.9 owner instruction | Six candidates for verification, multiple assignments, Administrator assignment control, prompt subsequent-request revocation and public-only published/public-intended content |

All eleven predecessor documents exist and current contents were reviewed directly or through read-only architectural review; none is missing/unreadable. Three earlier documents and backend foundation remain local/untracked, not included in this commit or promised present in clean remote checkout. Source references below exist locally; no earlier document rewritten.

### B2. Actual product/source evidence

| Evidence ID | Paths / symbols / observed area | What the evidence establishes |
|---|---|---|
| SRC-01 | `dashboard-app/src/lib/api/types.ts:76-93,127-154,219-225,287-300` | `Berita`/`KategoriBerita`, `Persyaratan`, `Layanan`, `Faq`, `Halaman` and collection map; notices are a news category, requirements nested under service |
| SRC-02 | `dashboard-app/src/app/nav.ts:55-89,113-118` | Actual dashboard labels/nav targets: Berita & Pengumuman, Tanya Jawab, Persyaratan Layanan, Halaman Statis |
| SRC-03 | `dashboard-app/README.md:77-118,125-134` | Current menu-to-data/public-page map and prototype workflow; documentation's old review roles do not override the two-role/direct-publication decisions |
| SRC-04 | `informasi.html:225-257`, `layanan.html:6-7,128-145,190-196,336`, `index.html:527-602,1767-1770`, `kebijakan-privasi.html:6,190` | Existing static public news/announcement filters/items, service/requirements content, FAQ area and static privacy page; prototype content, not live API binding or approved regulations |
| SRC-05 | `dashboard-app/src/pages/halaman/editor.tsx:22-79`, `dashboard-app/src/pages/layanan/editor.tsx:30-46`, `dashboard-app/src/pages/faq.tsx:15-35` | Working source-level local editor/mutations and schemas: static-page CRUD, nested service requirements and FAQ display flag. Source capability not server security readiness |
| SRC-06 | `docs/RANCANGAN-UIUX.md:14-18,38-42,75-101` | Product/prototype intent: public service requirements, news/announcements, FAQ/homepage, static-page dashboard; historic four-role design not current approved roles |
| SRC-07 | `dashboard-app/src/lib/api/index.ts:9-12`, `dashboard-app/src/lib/api/http.ts:31-75,77-129` | `VITE_API_URL` selects HTTP adapter; otherwise local adapter. Existing generic administrative client calls, bare JSON parsing, errors/404 mapping; **not a public contract** |
| SRC-08 | `backend/routes/api.php:6-10`, `backend/bootstrap/app.php:9-22` | Only `GET /api/health` in API source, application health JSON and JSON exceptions; no content public-read/assignment implementation |

File references were read/Grep-verified; actual category classifications below do not rely on a category merely being selected. No missing source prevents the current findings. A guessed standalone module/endpoint not found in reviewed files is explicitly not represented as implemented. This is a targeted review, not proof that every file/history/external product artifact has been exhaustively searched.

### B3. Classification rules

Use exactly these values: `VERIFIED_EXISTING` for source-present content concept/prototype capability; `VERIFIED_PLANNED` for documented planned scope without corresponding reviewed implementation; `OWNER_CONFIRMED_CATEGORY_PENDING_SPECIFICATION` for owner investigation/category intent lacking a concrete source-backed definition; `NOT_FOUND_IN_REVIEWED_SOURCES` for absence in reviewed artifacts; `CONFLICTING_EVIDENCE` for genuinely incompatible authoritative scope evidence. None alone means approved backend module, table or operational public API.

A subtype/nested form is **not conflicting evidence** merely because it lacks an independent collection. Likewise implemented local static-page editing is not merely planned because server integration is absent. Where historical review/role/publication suggestions differ from newer owner decisions, record the superseding policy and remaining technical gap; do not invent conflict between content-category existence and storage design.

## C. Six-category evidence catalog

The candidate identifiers are the owner's investigation identifiers, **not implemented server authorization keys**. Display names are exact observed labels where possible, with mapping qualifications. All six have source-backed product/prototype representation; independent assignment/public schemas are not thereby approved.

| Candidate identifier / display evidence | Classification and exact source basis | Confirmed/verified scope versus unverified functionality | Outstanding definition/dependency |
|---|---|---|---|
| `berita` — observed `Berita` filter and `Berita & Pengumuman` dashboard menu | **VERIFIED_EXISTING**; SRC-01 `Berita`, `KategoriBerita`; SRC-02 line59; SRC-04 `informasi.html:227,248-252`; SRC-03 row85 | Existing static news and local collection/editor concept. Owner selected category for verification; no independent assignment store or Laravel public news endpoint exists. | Whether catalog key berita means news subtype only or entire current combined collection; resolve overlap with pengumuman and category-changing edits. Public projection pending. |
| `pengumuman` — observed `Pengumuman` | **VERIFIED_EXISTING** as category, **not standalone collection**; SRC-01 line76 `KategoriBerita`; SRC-04 `informasi.html:228,254-257`; SRC-02 combined menu59 | Existing public filter/item and dashboard-supported news category. Lack of separate collection is not absence of product scope or a conflict. | Decide whether independent assignable authorization module or category within berita scope; do not create a new table/endpoint because six categories were selected. Prevent news grants unintentionally widening into unassigned notices. |
| `informasi-layanan` — candidate alias; observed display labels `Layanan & Persyaratan` / `Persyaratan Layanan` | **VERIFIED_EXISTING** service-information concept; SRC-01 `Layanan:133-148`; SRC-02 line68; SRC-04 `layanan.html:6,190-196`; SRC-06 lines81-84 | Existing service descriptions/form/published static representation under current collection `layanan`. Exact candidate hyphenated name is not a verified implemented module ID or independent UI label. | Owner approves mapping/name/granularity versus layanan/persyaratan; catalog/API identifiers and public service visibility mapping pending. |
| `persyaratan` — observed `Persyaratan` / `Persyaratan Layanan` | **VERIFIED_EXISTING** nested service-requirement concept; SRC-01 `Persyaratan:127-131`, `Layanan.persyaratan:144`; SRC-05 editor41-43; SRC-04 `layanan.html:190,336`; SRC-06 line82 | Existing structured requirement array and public service/checklist content, not standalone top-level collection. No independent API/state/assignment inferred. | Decide separate action/field-level assignment versus same service aggregate, parent visibility and update boundary; existing service patch can affect requirement content, so no unauthorized parent/child bypass. |
| `faq` — observed `Tanya Jawab` nav / `Tanya jawab` public heading | **VERIFIED_EXISTING**; SRC-01 `Faq:150-154`, collection292; SRC-02 line62; SRC-05 FAQ editor; SRC-04 `index.html:1767-1770`; SRC-03 row88 | Existing local FAQ CRUD/display toggle and static public FAQ area. `tampil` is source evidence, not automatically a finalized publish/unpublish state model. | Decide authoritative published/public-intended mapping, assignment/catalog/public projection and precise mutation contract; do not expose hidden FAQ just because a field is present. |
| `halaman-statis` — observed `Halaman Statis` | **VERIFIED_EXISTING** static-page/editor concept; SRC-01 `Halaman:219-225`, collection297; SRC-02 line88; SRC-05 editor22-79; SRC-04 static privacy page; SRC-03 row99; SRC-06 line100 | Existing local create/edit page content plus static public pages. Central persistence/API binding is pending; that absence does not downgrade implemented prototype/category to nonexistent or merely planned. | Approve included pages/slug mapping/public visibility state, assignment and field projection. Current `Halaman` lacks a publication field; no auto-public mapping or new field invented. |

**Catalog result:** six reviewed content categories classified `VERIFIED_EXISTING` **at concept/prototype level**; zero claim of six implemented independent security modules. No row requires `NOT_FOUND_IN_REVIEWED_SOURCES` or a genuinely conflicting existence classification on this evidence. Planned centralized integration is distinguished from existing local behavior for every row. Historical static copy/seeds are not authoritative production data/legal service requirements.

Owner must approve the assignable canonical catalog/mappings and shared-resource boundaries before assignments reference these categories. News/notices share one source type/collection; service information/requirements share one parent with nested values. A future six-key assignment design cannot safely assume six isolated resources. Category changes, parent patches, list filters and public projections need scoped checks after mapping is chosen; authorship remains metadata, not the authorization boundary.

## D. Multiple module assignments and authorization model

New owner-confirmed requirements, not implementation:

| ID | Requirement | Qualification / open design |
|---|---|---|
| AS29-01 | One Pegawai UPTD may hold **multiple active module assignments** | Actual cardinality limits/UI/IDs/storage unselected; role stays Pegawai, not a role per module |
| AS29-02 | Administrator manages assignment creation/change/removal; employee cannot self-assign or alter scope | No extra role, approval identity or current assignment-management API/UI claimed. Precise Administrator workflow/safeguards/audit pending. |
| AS29-03 | Every assignment references an existing or explicitly approved module | Source verification above is not canonical assignment-key approval; validate approved mapping, inactive/unknown/ambiguous module treatment fail closed |
| AS29-04 | Every protected operation checks backend identity/active status/role/current effective assignments/action against the actual target | Trusted server source required; client label/body/token/cached frontend claim cannot create grants. Full Administrator approved scope remains. |
| AS29-05 | Within assigned modules, otherwise authorized peer and own content can be managed | Creator ID alone cannot prohibit; no permission outside assigned modules or to delete/User Management |
| AS29-06 | Missing/invalid/revoked assignments fail closed; assignment changes affect server authorization immediately for subsequent requests | No default-all-modules, no re-login/token-refresh delay; complete revocation property Section G. No current guarantee implemented. |

Exact UI, trusted storage representation, module identifiers, overlapping-category/child-field enforcement, workflow/version/consistency/audit and assignment revocation mechanism remain specification prerequisites. No pivot table, schema enum, ACL package, middleware, route or CRUD field is selected. Server resolves record's real scope rather than trusting alleged request module; updates cannot move category/content into a restricted scope unchecked.

Authenticated module authorization and public visibility are different boundaries: unassigned employee cannot manage private/admin content, but may read genuinely public published/public-intended representations as any public consumer. Removal of assignment does not itself unpublish content or deactivate the account unless a separate approved lifecycle action does so; no new effect invented.

## E. Minimum technology-neutral public-read contract

### E1. Purpose, consumers and existing-contract boundary

Purpose: future public portal and approved public consumers read the current public representation of supported content. **Only currently published AND intended-for-public content qualifies.** Public API existence, concrete routes, methods, response fields/status codes, paging or schema are **PENDING**. No authentication/session/assignment required to browse content already intended for anonymous public viewing; protected management and private representations remain separate.

Verified source facts are confined to current foundation `GET /api/health` and dashboard's generic administrative `HttpApi.list/get` calls to collection paths (SRC-07/08). Those client calls and their existing JSON/error/not-found behavior are **not adopted as public routes or public response contracts**. Current admin DTOs include workflow history/provider/reviewer metadata and other internal data; never return them wholesale publicly. No new field name or success envelope is mandated here; approved compatibility and safe public projection need explicit concrete contract design.

### E2. Semantic contract requirements (confirmed policy, qualified recommendations)

| ID | Required public-read behavior | Concrete definition still pending |
|---|---|---|
| PUB29-01 | Server enforces current publication visibility and public intent on **every** public read and permitted filter/lookup, not frontend filtering | Authoritative state mapping/projection per source category; no internal enum/table chosen |
| PUB29-02 | Draft, unpublished-retained and any supported other non-public content cannot be returned through public reads | Do not add a client flag/query/ID/preview parameter to bypass visibility; public previews are not authorized by this task |
| PUB29-03 | Successful authorized edit of published/public-intended content immediately affects subsequent public reads | Actual public/static integration/freshness/success-boundary verification; no invented TTL/CDN/cache purge or delayed approval/revision gate |
| PUB29-04 | Successful unpublish excludes content from public lists/details/representations while keeping editable record | No delete/archive/file erasure; attachment visibility/copies/history access separately reviewed, no claimed universal file revocation |
| PUB29-05 | Successful authorized republish restores public visibility under current publication/public-intent rules | Direct employee action in current assigned module, no Administrator approval/recreation requirement |
| PUB29-06 | Missing or non-public target yields no public representation and no internal existence/state/history disclosure | **Recommended** indistinguishable public absence behavior; exact protocol status/error/body contract pending. No invented 404/403/field names here. |
| PUB29-07 | Return only approved public fields; errors exclude credentials/tokens/session material/paths/stacks/internal actors, assignments and private review/audit data | Per-category field projection/error convention reviewed before code; raw source interface not default public payload |
| PUB29-08 | Filtering/pagination cannot leak hidden records via totals, facets, ordering or links; caller cannot relax server visibility | Existing static category chips and decorative pager are not a server pagination contract. Actual parameters/cursor/limits/shape are proposed pending approval; do not truncate/wrap current client arrays silently. |

No actual HTTP method/route/status/response key proposed for the new public endpoints; the semantic contract is intentionally minimal. Exact lookup identifiers, representation/sanitization/file links, absence/error behavior, paging/filter/sort and compatibility are decisions, not existing capabilities. Existing Laravel JSON exception behavior does not establish an approved public content error payload. Unauthorized protected actions fail securely/no mutation; public absence does not reveal whether a target is a private draft or nonexistent.

### E3. Per-candidate public applicability and schema gaps

| Candidate | Public read applicability | Verified source shape, **not an approved public schema** / unresolved mapping |
|---|---|---|
| `berita` | Applicable to source-present public news after publish/public-intent enforcement | `Berita` shape exists; category versus combined news scope, approved display/body/media/date fields and exclusion of workflow/private metadata pending |
| `pengumuman` | Applicable to public notices under same visibility rules | `Berita` category exists; category discriminator/identity and combined versus separate public projection pending, no standalone endpoint/table invented |
| `informasi-layanan` | Applicable to approved public service descriptions | `Layanan` shape exists; display/service/requirement coupling, safe public fields and canonical module mapping pending |
| `persyaratan` | Applicable to approved public service requirements, possibly as parent representation | Nested `Persyaratan[]` shape exists; independent read versus included value objects and parent/current public eligibility need approval; do not expose unpublished parent through child lookup |
| `faq` | Applicable to public Q&A | `Faq` and source display flag exist; publication/public-intent mapping and public fields pending, not automatic equivalent of source toggle to complete lifecycle |
| `halaman-statis` | Applicable to approved public informational/static pages | `Halaman` shape and static HTML exist; source lacks publication field, visibility/content mapping/slug scope/safe body fields and public integration pending |

For **all six**, exact public API schema is unresolved and no server endpoint/persistence implementation is claimed. Applicability is verified product representation plus confirmed public visibility policy, not a claim that every stored record is public or every source field is approved.

## F. Publication and visibility table

Logical labels below are semantic categories, not new database enums or selected flags. Source `Beralur` labels and FAQ `tampil` must be mapped deliberately; `Halaman` has no source publication field. Client workflow is evidence of prototype state, not a mandatory approval process. No delayed Administrator publication step reintroduced.

| Logical/source-supported condition | Public representation | Record and protected authority / change behavior |
|---|---|---|
| Currently published and public-intended | Visible through eligible public reads | Stored/editable; assigned employee can edit peers, edits immediately public after success, unpublish/republish direct within scope |
| Published but not intended for public visibility | Excluded | Publication indicator alone insufficient; approve which data/fields are public, not a new invented state enum |
| Draft/not-yet-published | Excluded | Protected assigned-module editing allowed; publication required for public eligibility |
| Unpublished-retained | Excluded | Record remains stored/editable; direct authorized republish restores eligibility; no deletion/archive implicit |
| Prototype `diperiksa`, `persetujuan`, `dikembalikan` or `arsip` where present (EV-01/02) | Excluded **when non-public** under approved mapping | Verified old client workflow values, not finalized state graph or mandatory reviews; no assumption unpublish must equal `arsip` |
| Hidden FAQ/display item per prototype flag | Public eligibility must be explicitly mapped and hidden content excluded | Source flag exists; not a separate automatic database state or finalized field contract |

Public reads after unpublish must not expose content merely because the caller knows an ID/slug or a stale frontend still displays it. Previously downloaded/copied data cannot be remotely erased; that limitation does not permit public server/cache paths to continue presenting non-public records as current. File access after unpublish remains a separate design question; record retention does not authorize public exposure of private media.

## G. Immediate assignment revocation property

**Confirmed requirement:** after Administrator successfully removes an employee's assignment to a module, **every subsequent protected request must evaluate current effective assignment or an equally prompt guaranteed revocation mechanism**, and deny the revoked module without re-login. Browser may remain authenticated; authentication alone is insufficient. No operational grant/cache/session architecture exists by this specification.

| ID | Testable requirement / analysis |
|---|---|
| REV29-01 | Define a verified removal-completion boundary: after removal is effective/successfully completed, initiate a new protected request to that module. Deny even using the same authenticated browser/session and stale frontend state. Do not return revocation success while knowingly leaving subsequent access effective. |
| REV29-02 | Preserve still-valid assignments to other modules; no requirement to log out, deactivate or lose all scopes merely to enforce one removal. Administrator access follows its full application role, not employee assignment absence. Other account-security rules still apply. |
| REV29-03 | Never authorize from unverified stale token claims/session snapshots/client storage. If authorization caches/replicas exist, choose and verify invalidation/version/current-grant evaluation that meets the immediate property; waiting for cache TTL, token refresh or re-login is not an accepted loophole. No cache/store/strategy selected here. |
| REV29-04 | Server validates target scope/current permissions, including lists/details, nested/shared resources, edits/publication and batch/alternate requests. Any mechanism must cover all applicable paths and failure modes, not just a hidden button or one route. |
| REV29-05 | Requests started **before** revocation completes are distinct from subsequent requests. No cancellation guarantee is asserted. **Recommended:** design authority recheck/coordination for sensitive commits and explicitly define concurrent-revoke/in-flight success/error/audit semantics before implementation. Current DB/transaction design cannot be assumed to cancel an already executing action. |
| REV29-06 | Long-lived business connections/subscriptions are not established by reviewed architecture. If later approved, require their own revoked-access/read/write/refresh/termination rules meeting appropriate security property; do not invent SSE/WebSocket infrastructure now. Developer tooling connections do not prove business subscriptions. |
| REV29-07 | Public reads of published/public-intended content remain public; assignment removal denies protected module work, not ordinary access everyone has to public content. Non-public views cannot be relabeled public to evade revocation. |

Verification experiment, **planned only**: give a synthetic Pegawai valid assignments to two approved test modules, authenticate under approved policy, verify authorized scope, remove one using Administrator, then send new protected requests with the unchanged session/stale client claims. Removed-module requests must fail with no mutation/protected disclosure; other assignment remains usable; approved Administrator operations remain usable. Probe any actual cache/replica/path surfaces once identified. Exact denial status/body/route names are not invented.

Documented architecture has no implemented trusted assignment source, cache/revocation design or test evidence capable of establishing this guarantee. This is a **blocked implementation dependency**, not a passing runtime claim. Approve/verify the mechanism under a separate task before enabling protected module operations; no new MFA policy or global session logout requirement inferred.

## H. Planned acceptance criteria

**All scenarios: `PLANNED — NOT IMPLEMENTED OR EXECUTED`.** The source-review assertions in AC29-01/02 have documentary evidence in this phase, but the future integrated acceptance suite below is not implemented/run. Symbolic test-module labels do not invent the actual catalog.

| ID | Setup / planned action | Expected result / prerequisite |
|---|---|---|
| AC29-01 | Review each of six owner candidates against documented code/public/product evidence | All six classified with qualifications; subtype/nested concept not mistaken for independent implemented module; Section C paths/evidence |
| AC29-02 | Compare source implementation, planned centralized integration and approved product scope | Missing Laravel functionality not mistaken for absent product scope; local static-page CRUD exists, notices/category and requirements/nesting documented |
| AC29-03 | Approved synthetic Pegawai receives multiple valid assignments from Administrator | Multiple active scopes usable without changing role or employee self-grants; canonical mapping and workflow approved first |
| AC29-04 | No/invalid/ambiguous effective assignment; protected module request | Fail closed/no mutation/protected data; target's real server scope used, not client claim |
| AC29-05 | Authenticate employee, remove one assignment, make new protected requests without logout | Immediate denial for removed scope using same session; no re-login/token-refresh/cache-expiry delay or stale client restoration |
| AC29-06 | Same setup, other assignment unchanged and eligible Administrator | Other valid employee scope and Administrator full approved capabilities remain; no unnecessary global logout imposed |
| AC29-07 | Mixed published/public-intended versus non-public records in approved test source | Public reads include only eligible content; no private fields/total/facet leakage or frontend-only filtering |
| AC29-08 | Known draft ID/slug and list/filter attempt | No public representation/existence/internal history disclosure; exact concrete contract approved before execution |
| AC29-09 | Unpublished-retained ID/list/alternate lookup request | No public content leak; caller-controlled filters/preview cannot bypass server rule |
| AC29-10 | Authorized unpublish then inspect public/protected approved paths | Public hidden; stored record retained/editable, no file/history deletion inferred; public freshness behavior verified |
| AC29-11 | Authorized assigned Pegawai edits then directly republishes retained content | Eligible public visibility restored without Administrator approval/recreation; proper current scope checked |
| AC29-12 | Edit authorized published content, after successful completion perform public reads | Immediate updated representation, no review/draft approval delay or intentional stale-success; actual public/cache integration inspected |
| AC29-13 | Forge browser visibility/module flag or omit frontend filter; call public read | Server still excludes drafts/unpublished/non-public data; no raw admin DTO/dynamic disclosure |
| AC29-14 | Pegawai tries delete/User Management/bulk or parent-child/category scope bypass | Denied with no unauthorized mutation; Administrator-only operations remain as approved, shared-resource mapping enforced |
| AC29-15 | Synthetic secret markers/internal audit/assignments in source/error payloads | Approved public fields only, safe errors, no credentials/session/reset material/paths/stacks/private metadata |
| AC29-16 | Revocation concurrent with already-started request, and later new requests on actual replica/cache surfaces | Subsequent property guaranteed; in-flight handling matches approved separately specified policy, not assumed cancellation |

Runtime acceptance additionally needs concrete approved schemas/contracts, trusted module mapping/assignments/revocation, current-public projection/freshness and negative tests under authorized isolated environments. Missing evidence means BLOCKED, not a passing unexecuted test. No tests/DB/API implementation by this phase.

## I. Readiness and unresolved decisions

| Area | Current outcome | Remaining evidence/decision / dependent implementation |
|---|---|---|
| Category verification | **COMPLETE documentary evidence for six source-existing concepts** | Canonical assignable module catalog/granularity is **PARTIAL**, not six approved independent resources; approve berita/pengumuman and layanan/persyaratan boundaries |
| Multiple assignment model | **CONFIRMED policy; PARTIAL technical specification** | UI/IDs/storage/trusted grant lifecycle/Administrator workflow/audit and overlap mapping not implemented; scope-dependent code **NO-GO** |
| Immediate revocation | **CONFIRMED subsequent-request requirement; mechanism UNVERIFIED** | Choose/evidence equally prompt server strategy plus in-flight/any later long-lived policy; protected execution **NO-GO** |
| Public visibility contract | **CONFIRMED semantic rules** | Per-category state/public-intent mapping including FAQ/page/parent-child, safe projections/errors and concrete read definitions pending |
| Content API design | **PARTIAL; implementation NO-GO** | No public routes/methods/fields/status/paging/schema finalized; public/static integration/cache success-boundary/validation/files/audit must be specified/verified |
| Authentication/MFA/session security | **NO-GO unchanged** | Sanctum recommended, MFA undecided; account/credential/lifecycle/security authority and configuration questions remain from earlier phases |
| MySQL/test database/recovery | **BLOCKED unchanged** | Approved private access/grants/test isolation and separately authorized safe connection/model/migration/recovery evidence missing; no DB work now |
| Origins/CSRF/CORS/cookies/Cloud deployment | **BLOCKED unchanged** | Actual domains/resources/security/release mandate/environment parity unverified; no deployment approval |
| Application implementation authorization | **NOT GRANTED** | New bounded task with specific closed dependency evidence/files/tests/exclusions; documentary completeness cannot enable Phase3 globally |

Remaining minimum decisions: canonical category-to-security scope mapping; assignment workflow/trusted source/revocation success and in-flight semantics; public safe fields/visibility mapping/concrete transport/absence/filter design and public delivery integration; actual validation/media/audit/history/operations requirements. Administrator application authority known; separate security/infrastructure/data custodian mandates **NOT IDENTIFIED — OWNER CONFIRMATION REQUIRED** as applicable. No names/job titles/credential/retention/security values invented.

## J. Smallest next action and isolated publication

1. **Owner resolves canonical catalog and overlapping scope mappings:** approve whether notices are an independent assignable scope or news category, and requirements an independent field/child scope or service aggregate; define the other verified category mappings. This is not asking whether source-backed categories exist again, or rejecting an unimplemented category. No new module list assumed.
2. **Specify one bounded assignment/public-read slice:** Administrator workflow, trusted current grants and immediate subsequent revocation mechanism, scoped parent/category writes and in-flight semantics; per-category public projection/visibility/absence/concrete API and freshness integration. Do not invent schema/routes or another phase merely for document volume; obtain actual choices and close applicable technical evidence.
3. **Close relevant existing identity/security/DB/environment prerequisites**, then expressly authorize the slice's implementation/tests. Independent documentary/design work can proceed without all production Cloud resources, but no operational authorization/SQL/public integration is implied here. Secrets delivered outside docs through approved custody, not requested as values.

Only `backend/docs/PHASE-2.9-CONTENT-MODULE-CATALOG-AND-PUBLIC-API-CONTRACT.md` is added. Verify owner/source consistency, all six categories, references, secret-free complete document/whitespace/newline, `git diff --check`, exact existing fingerprints/status and independent read-only review. Stage only literal new path, inspect staged diff/`git diff --cached --check`, commit `docs(pintu): define module catalog and public API contract` and ordinary push to `origin/laravel-development`; verify single-file commit/parent/remote synchronization and all 385 pre-existing paths preserved. Stop for branch/divergence/staging/isolation/hook/access failures; no broad staging, cleanup/reset/restore/discard/amend/force or prior-document inclusion.

No source/UI/copy/dependency/config/environment/DB changes, endpoints/auth/MFA/module assignment/persistence/workflow/schema/cache/audit implementation, runtime tests/HTTP/SQL/connection or deployment. Actual documentary verification/commit/push results reported only after commands; Phase3 remains unstarted. Publishing this semantic contract is not publication of an implemented public API or external deployment authorization.
