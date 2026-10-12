# PINTU — FAQ First-Slice Decision Record and Approval Closure Plan

**Phase:** 2.16. **Date:** 2026-10-12. **Scope:** documentation only. **Overall readiness: NOT READY — remaining governance, security, data/API, environment and operational evidence is unresolved; implementation NOT AUTHORIZED.**

## A. Product decision record and source evidence

This document records two exact decisions received after Phase 2.15. It updates their meaning additively; earlier phase files are not rewritten. Confirmation of a candidate and answer format does not accept an entire technical approval packet, its numerical limits, DTO, route, lifecycle, normalization, data records, security verification, rollout timing or code execution.

| Decision ID | Statement | Status / owner category | Scope and consequences | Explicit non-consequences / related pending items |
|---|---|---|---|---|
| **PO-2.16-01** | The product owner accepts **FAQ collection-only public read** as the candidate for the first implementation slice. | **CONFIRMED — PRODUCT OWNER DECISION**; current user instruction, owner `PINTU Project Owner (Application Administrator)` | Candidate selection settled; narrow collection-only FAQ preparation, no detail route by default. Other modules remain outside this first slice, not removed from the product. | No security/API/model/data/environment/custodian/cutover/implementation approval. Lifecycle G16-01–03, DTO/API A16-01–04, data D16-01/02, environment E16-01/02 and authorization I16-01 remain open. |
| **PO-2.16-02** | The product owner selects **plain-text FAQ answers, without rich-text or HTML-rendered links**. | **CONFIRMED — PRODUCT OWNER DECISION**; current user instruction, same product owner | Public answer content treated/presented as text, not parsed HTML/rich text; link-looking text not automatically clickable. | Does not approve stripping tags/deleting stored URLs, literal legacy-markup adoption, normalization, new schemas, whitespace/length/error rules or an executed security review. Legacy policy G16-04/data evidence and S16 security verification remain separate. |

These statements settle the selection part of Phase2.15 U15-01 and the answer-format direction of U15-08. U15-01's mandate/data/execution scope and U15-08's normalization/security/compatibility questions are **not** fully closed. **U15-07 two-field DTO is still pending**: plain-text selection does not approve exact field allowlists or lack of IDs/metadata/envelopes by itself. No remaining decision is silently resolved by an agent's recommendation.

### A1. Unchanged confirmed governance

- Four canonical modules: `berita` / Berita including Pengumuman category; `layanan` / Layanan including nested Persyaratan; `faq` / FAQ; `halaman` / Halaman Statis. No independent Pengumuman/Persyaratan assignments. Exactly `Administrator` and `Pegawai UPTD`, no new role/sub-role or alternative authorization identity.
- Laravel target canonical source for all four; static HTML consumer or controlled transitional fallback; incremental validated rollback-capable migration. Actual content models/read source/persistence/API not proved by this policy. FAQ requires explicit lifecycle; existing tampil may be additional display control only.
- Public anonymous/read-only; current published AND explicit public intent AND approved safe representation required. Draft/unpublished/private/missing/unknown/malformed/contradictory eligibility fails closed. Unpublish hides but retains stored/editable management record; eligible republish restores visibility; successful published edit has immediate effect without mandatory Administrator approval.
- Assigned employees manage eligible peer content within modules, including direct publish/edit/unpublish/republish. Administrator-only deletion/User Management/roles/status/assignment administration remains; assignment removal denies subsequent protected requests without re-login and preserves other valid scopes.
- Protected authentication/MFA and origins/CSRF/CORS/cookies/DB/deployment policy remains unresolved separately. An anonymous public reader does not need login or MFA; owner application role does not confer security-risk or infrastructure authority.

### A2. Safety audit and complete predecessor review

Root/current directory `D:\Herd\portal-uptdkotakupang`; branch/upstream `laravel-development` / `origin/laravel-development`, origin verified. Preflight local and remote tip `87c9314275191c75e41b46f43a316ce6249a6fa5`; no divergence. Fresh raw porcelain: **385 outstanding paths = 6 tracked unstaged modifications + 379 untracked files; 0 staged**. Target file absent; no conflict. Existing tracked-diff fingerprint `cac31712e8536b629d9fec47bb290ddea1f9c29e`; content inventory `43094bece1d2fdd60d89f76b90e6cbed98f9d0c9` in Git output order. Final comparison excludes only this new document. No cleanup, stage-all, reset, branch switch or pre-existing content changes.

The complete six requested predecessors were read directly or by a read-only architecture reviewer; no missing/unreadable document claimed reviewed:

| Evidence | Actual path | Governing finding / qualification |
|---|---|---|
| EV16-01 | `backend/docs/PHASE-2.10-CANONICAL-CONTENT-MODULE-BOUNDARIES-AND-PUBLIC-READ-SPECIFICATION.md` | Canonical boundaries/public-read-first, not implemented endpoints |
| EV16-02 | `backend/docs/PHASE-2.11-AUTHORITATIVE-CONTENT-SOURCES-VISIBILITY-MAPPING-AND-PUBLIC-PROJECTION-SPECIFICATION.md` | Prototype/static divergence, independent FAQ/Halaman visibility gaps and pending safe projections |
| EV16-03 | `backend/docs/PHASE-2.12-CONTENT-AUTHORITY-AND-PUBLICATION-GOVERNANCE-DECISIONS.md` | Source/rendering/lifecycle options, not implicitly approved recommendations |
| EV16-04 | `backend/docs/PHASE-2.13-GOVERNANCE-DECISION-FINALIZATION-AND-IMPLEMENTATION-READINESS.md` | Confirmed Laravel target/incremental/explicit FAQ-Halaman lifecycle; field/transport/data details pending |
| EV16-05 | `backend/docs/PHASE-2.14-PUBLIC-READ-CONTRACT-FINALIZATION-AND-LIFECYCLE-MAPPING.md` | Proposed routes/DTO/error/cursor/limit/no-store packet, per-module prerequisites rather than all-module approval |
| EV16-06 | `backend/docs/PHASE-2.15-GOVERNANCE-APPROVAL-PACKAGE-AND-FIRST-SLICE-READINESS-GATE.md` | FAQ collection recommendation, U15-01–24 and open G15 gates; only current PO decisions supersede exact unresolved product-direction portions |

All prior files stay untouched, including local/untracked foundation/older architecture artifacts. Citation is local repository evidence, not proof all application sources are published or implemented. Git commits are not organizational approvals.

### A3. Current read-only source findings

| Source / symbol | Verified source finding, not runtime assurance |
|---|---|
| `dashboard-app/src/lib/api/types.ts:20-26,150-154` | Faq extends Entri with pertanyaan:string, jawaban:string, tampil:boolean; no explicit lifecycle/public intent/status/terbitPada. Type declaration does not validate runtime imports/records. |
| `dashboard-app/src/lib/alur.ts`; `dashboard-app/src/lib/meta.ts` | Prototype Beralur collection/workflow does not provide FAQ's explicit lifecycle. Do not borrow terbit/arsip as approved FAQ values. |
| `dashboard-app/src/pages/faq.tsx:15-18,49,121` | Question/answer required client strings, current UI maxima 200/800, answer textarea and React text expression. These limits/presentation are not production schema/security approval. |
| `dashboard-app/src/lib/api/seed.ts:625-664` | Seven inspected example FAQ pairs are prose strings, no HTML/Markdown link syntax or explicit URL token observed in those literals; source fields returned with client IDs/times/order/tampil. This does not certify browser-edited/legacy/production records or official content. |
| `index.html:1767-1909`, especially 1825/1842 and later links | Static FAQ contains paragraph/detail/nbsp markup and clickable service/schedule/contact anchors. Those links are consumer markup, not verified markup stored in the inspected seed answer string. |
| `dashboard-app/src/lib/api/index.ts:9-12`; `dashboard-app/src/lib/api/local.ts`; `dashboard-app/src/lib/api/http.ts` | Configured HttpApi otherwise IndexedDB/memory/seeds; local management list/get no public filtering, patch merges, administrative bare entities/arrays and errors. HTTP failure does not automatically select LocalApi. No centralized public FAQ projection. |
| `backend/routes/api.php:6-10`; `backend/app/Models/`; `backend/app/Http/Controllers/`; `backend/database/migrations/` | Health API only; inspected content source structures remain scaffolding, no FAQ endpoint/model/content migration found. Actual DB/table/data state not queried. |
| `backend/phpunit.xml:20-34`; `backend/tests/Feature/HealthCheckTest.php` | SQLite :memory: test target and foundation tests, not MySQL access/parity or executed FAQ acceptance. No runtime tests/connectivity performed here. |

Current findings support the known gap; no new source behavior resolves pending lifecycle/DTO/security/database questions. Missing private/runtime data remains unknown. No .env/credential/log values read or printed, no DB/production system accessed, no normalization performed.

## B. Bounded first-slice scope

**Confirmed candidate:** FAQ collection-only public read. **Proposed implementation scope, pending approval:** anonymous collection retrieval from approved canonical Laravel FAQ data, server eligibility filtering, a minimal public allowlist, plain-text answer strings, safe bounded query/error/cache policy. No existing public route claimed.

Out of scope: public detail endpoint unless separately justified/approved; FAQ write/update/delete, lifecycle/admin/assignment/login/MFA implementation, schema/migration/source preparation/data import/reset, dashboard redesign or consumer changes, other modules, production deployment/cutover and operational DB provisioning. Any necessary source/model preparation requires a later explicitly scoped prerequisite task; a read endpoint cannot silently seed/backfill/publish content or mutate through query parameters.

Public read never returns a private administrative variant when cookies/tokens/roles are supplied. Public access grants no management capability; Administrator/assigned employee transition permissions are retained but not implemented here. Candidate selection is not API-path/method or full rollout-order approval. Existing static UI and admin consumers remain unchanged.

## C. FAQ lifecycle, public intent and fail-closed requirements

**Confirmed requirements:** explicit FAQ lifecycle and published AND public-intended eligibility; unknown/missing/malformed/contradictory eligibility excluded. Existing tampil is not publication/public-intent evidence. Canonical representation/valid transitions/provenance remain G16-01/02 decisions, with no final enum/field/table or Boolean formula chosen.

| Topic | Required semantics and current gap | Proposed control, explicitly unapproved |
|---|---|---|
| Lifecycle | Distinguish draft/current published/retained-unpublished and invalid/unknown logically; Faq has none of these source states | **RECOMMENDED — PENDING APPROVAL:** explicit validated canonical lifecycle capability, not automated backfill from tampil. Physical encoding/mutations/history pending. |
| Public intent | Deliberate current classification/attestation that content may be public, separate from arbitrary display flag | **RECOMMENDED — PENDING APPROVAL:** approved evidence-backed intent representation evaluated alongside lifecycle; no mandatory new is_public field. |
| Optional tampil | May narrow presentation after both eligibility conditions; adoption/default/per-surface meaning unresolved | **RECOMMENDED — PENDING APPROVAL:** if retained for first collection, false/missing/invalid suppresses display, true never grants eligibility. Not a confirmed requirement to retain it or apply this exact rule. |
| Null/empty/malformed text | Current string type/UI validation does not prove valid stored records; text defect not automatic public eligibility | **RECOMMENDED — PENDING APPROVAL:** reject/hold malformed required records, preserve source for remediation; exact empty/whitespace/null/oversize/collection-failure versus omission policy G16-05 pending. |
| Unpublish/republish | Hide public result, retain editable record; republish only when all current conditions pass | Confirmed semantics, **RECOMMENDED — PENDING APPROVAL** physical mapping/visibility-write/read/error design, no source arsip/deletion assumption. |
| Published edit/freshness | Approved authorized successful changes affect subsequent public reads immediately, no mandatory Administrator approval | **RECOMMENDED — PENDING APPROVAL:** current trusted read state/no intentional stale-success. Already-started requests separate; actual consistency/failure/cache mechanism pending, no global cancellation guarantee. |
| Management authority | Current active backend identity/role/assigned FAQ module governs employee transitions; deletion/admin exclusive to Administrator | Confirmed boundary, not implemented route/policy. Transition/version/revocation/audit/assurance requirements and protected MFA gates remain separate. |

FAQ question/answer validation, valid Unicode and safe rendering are needed even with a text-only contract. Missing/invalid lifecycle cannot be remedied by stripping markup or choosing a guessed text fallback. Republish/edit/retention do not approve HTML output or override current intent; no normalizer runs here.

## D. Plain-text answer contract and reviewable legacy policy

**CONFIRMED — PRODUCT OWNER DECISION:** public answers are text, not HTML/rich text or HTML-rendered links. Answers that look like URLs or Markdown links are not auto-linked or parsed; no embedded image/media/active markup. This is presentation direction, not authority to delete source URL strings or mechanically strip static markup.

**API serialization versus presentation:** JSON serialization can carry characters such as `<`, `>`, quotes or URL-looking text in a correctly encoded string. A future consumer must render decoded answer values as text (for example text-node/escaped template/React text rendering), never unsanitized HTML. The JSON encoder may use valid Unicode escapes; that does not require pre-HTML-encoding stored strings or double-encoding content. Existing HTML syntax in a source string must never become active tags/attributes through consumer rendering. Exact serialization/null/string schemas still A16 approval items.

### D1. Source policy options — no data transformation approved

| Option | Meaning and consequence | Current recommendation / required approval |
|---|---|---|
| P1 Preserve source string and render literally | Store/retrieve accepted raw string, present markup/Markdown/URLs as visible text with no click/HTML execution. Preserves content but may display tag syntax or intended formatted links awkwardly. | **RECOMMENDED — PENDING APPROVAL** conservative non-destructive baseline for review, not automatically adopted by PO-2.16-02. Owner/data approval of readability/meaning/source acceptance needed. |
| P2 Approved normalization to text | A separately reviewed, provenance-preserving conversion may extract meaningful text or include approved literal URL text, with source backup/diff/per-record acceptance. Can lose markup/links/spacing if naive. | **PENDING APPROVAL:** mapping/algorithm/per-record rules, records/authority/test/recovery plan; no regex blanket strip, URL deletion, in-place cleanup or migration in this phase. |
| P3 Manual content remediation | Owner/authorized custodian rewrites accepted answer to approved prose, with preserved original, reason and semantic review. Better control but possible content mistakes and operational work. | **PENDING APPROVAL:** explicit affected records/meaning/role/custody/edit approval and backup/reconciliation; no fictional legal/reference review or actual remediation now. |

These choices all produce the confirmed plain-text presentation; returning rich HTML is **not** an alternative within this confirmed scope. A future superseding product decision would be necessary to change the format. Combining per-record P1/P2/P3 requires explicit accepted mapping; keep original source immutable for review until transformation authorized.

### D2. Text policy decisions still open

**RECOMMENDED — PENDING APPROVAL (G16-05/A16-01/S16-01):** valid UTF-8 JSON strings, consistent Unicode handling, explicit whitespace/newline/control-character rules. Recommended non-destructive default is preserve accepted meaningful text/newlines; any CRLF normalization, trimming, collapsing spaces, Unicode normalization or replacement must be specified and reviewed, not applied silently. Consumer line-break display (pre-wrap/paragraph/plain text) is a separate later UI scope, not current CSS change. U+00A0/HTML entity decoding and legacy escaped strings need explicit per-source handling; do not blindly decode/remove them and double-escape.

Null, absent, empty, whitespace-only, oversized or malformed question/answer behavior and limits must be decided before code. Current UI maxima 200/800 are **source evidence only**, not approved server policy. Unknown eligibility always excluded; field-level rejection/omission/outage body policy requires acceptance. Error responses/logs must not echo entire answer content or authentication secrets. Safe JSON escaping alone does not make arbitrary HTML insertion safe; actual consumer behavior needs later adversarial tests.

## E. Proposed minimal public DTO — approval not implied by format

**RECOMMENDED — PENDING APPROVAL:** each collection item has exactly existing source keys `pertanyaan` and `jawaban` as approved plain strings. Candidate `pertanyaan` plain-text treatment and its requiredness/length are recommendations; owner explicitly selected answer format, not a two-field schema. No public record identifiers/detail URLs in this first candidate by default; exact collection item keys and exposure require A16-01 approval.

| Decision | Source/candidate | Required remaining approval |
|---|---|---|
| Stable identifiers | Source Entri.id exists; public candidate omits it | Determine internal stable identity/source mapping/tie-breaker and whether collection needs public reference. No ID, slug, UUID or schema selected; detail route out of scope absent new approval. |
| Ordering | LocalApi sorts urutan; static questions have manual order | Approve deterministic stable source order/tie-breaker/paging-change behavior, no raw internal key in public cursor/header. |
| Display | Source tampil exists | Govern optional narrowing **after** current lifecycle/intent; no confirmed false/missing suppression rule or public tampil output. |
| Null/empty/validation/max lengths | Source strings/client limits | Approve exact requiredness, Unicode/whitespace/record-hold/normalization/body/query/response limits; no arbitrary defaults. |
| Legacy text/extra data | Unknown browser-edited records may contain markup/extra fields | Explicit allowlist and reviewed legacy P1/P2/P3; no whole admin serialization. |
| Envelope/versioning/metadata | Phase2.15 bare-array/no-version/no totals suggestion | Still A16 pending; selecting collection/plain text doesn't approve body structure or public metadata. |

Proposed exclusions: raw `id,dibuat,diperbarui,urutan,tampil`, any future lifecycle/intent fields, internal account/grant/audit/provider/reviewer/approval/workflow/revision history, storage/debug paths, passwords/hashes/reset/access/session material and all unapproved object properties. This is proposed field packet plus established secret/private-data exclusion, not claimed current serializer. Cursor/Link/error/query metadata must not indirectly disclose excluded identifiers or hidden counts. Administrative DTOs remain distinct for authorized management.

## F. Recommended collection transport — pending API approval

No public FAQ endpoint exists in the inspected source. All details below are **RECOMMENDED — PENDING APPROVAL**, carried as review options from Phase2.15, not approved by PO selection:

| Topic | Proposed option / unresolved approval |
|---|---|
| Route/method/access | GET /api/public/faq, anonymous read-only/no login/credentials; no version prefix in proposal. No detail route/API IDs; framework HEAD/OPTIONS reviewed for same visibility/no mutation if supported, no anonymous writes. |
| Success/empty | UTF-8 `Content-Type: application/json`, bare array of approved DTOs; valid empty `[]`, proposed 200. Exact encoding/header/envelope/item schema/codes pending. Missing source not an empty-success fallback. |
| Errors | Proposed safe `{message:string}`; validation may include only allowlisted public parameter errors. Proposed 400 malformed, 422 invalid parameter, 404 absent route, 405 unsupported method, 429 throttled, 503 unavailable canonical source/eligibility, 500 unexpected. Wording/headers/statuses not chosen; no private record/state/paths/stack/secrets or reflected raw payload. |
| Ordering/paging | Proposed urutan ascending plus approved stable internal tie-breaker; opaque validated cursor with Link next header, limit default 20 / maximum 100 **review values only**, no hidden totals. Exact identity/cursor/version/change behavior/query validation and accepted limits pending, no snapshot guarantee or raw internal ID exposure. |
| Filters/response bounds | Initial proposed only limit/cursor; no status/intent/tampil/private include/owner override. Provisional 1 MiB response review cap and precise string/query/item/page bounds pending, no silent truncation or authority inferred from UI 200/800 values. |
| Cache/freshness | Proposed no-store/no 304/ETag/Last-Modified until verified current-eligibility strategy accepted. Confirmed successful-change/public-read requirement cannot become an invented stale TTL grace period; actual read-success/in-flight/cache/failure semantics pending. |
| CORS/origin | Exact reviewed non-credentialed public origins and expose-headers if needed; target local/staging/production policy unresolved. No domain guessed; CORS is not public eligibility or protected authorization. |
| Abuse | Approved read rate/query limits, network/proxy trust/429/Retry-After needed; no numeric rate/window selected. No visitor login requirement to avoid read-throttle design. |
| Separation/no-write | Same public projection with or without cookie/token, no privileged variant, no mutation/seeding/backfill on read, unsupported write methods rejected; protected User Management/publication endpoints not implemented or authorized. |

No route, DTO, schema, rate/cache/CORS config or API fixture installed. Source preparation/migration/read-source capability must be separately specified and authorized before endpoint implementation, not hidden in this read-only contract. Production DNS/Cloud and protected authentication/MFA need not block unrelated local documentation, but applicable DB/test/security/current-eligibility prerequisites remain.

## G. Compatibility and data assessment

**Verified current source:** inspected seed FAQ pairs are plain prose; static index FAQ uses paragraphs/details, nonbreaking-space formatting and clickable service/schedule/contact links. Dashboard textarea/source plain strings do not carry all that markup/link behavior. Actual edited IndexedDB/legacy/canonical production records were **not** inspected; unknown content cannot be certified plain/clean. No email/provider/service/security facts inferred from FAQ example wording.

PO-2.16-02 now settles **future plain-text presentation**, not exact replacement of static links, literal inclusion of markup or disposal of URLs. Potential losses: click navigation, emphasized/nonbreaking text and legacy Markdown intent. Required owner/data mapping identifies accepted prose/URL-as-text meaning per record, preserved source, projection and later approved consumer render. Current HTML remains unchanged; phase does not remove links or claim current rich static FAQ already conforms to future plain-text API.

**Closure plan:** inventory question/answer strings from accepted static/local/seed sources without executing a migration; classify markup/Markdown/URL-like/entity/newline anomalies; preserve originals/provenance; review per-record P1/P2/P3 policy and accepted canonical text/visibility/order; approve exact consumer/native-details/plain rendering and accessibility/line-break/semantic parity; separately authorize consumer changes and validate during cutover. Data inventory/completeness/runtime values need real later evidence, no automatic seed import.

Dashboard full Faq admin DTO/local behavior must not be overwritten by two-field public projection; HttpApi existing arrays/entities/errors/network-failure behavior remains unchanged. API-only local implementation, if later authorized, can exclude frontend cutover but needs safe accepted representation/test source. Current public-content meaning/data/privacy decisions still relevant; no false compatibility claim merely because static files are untouched.

**Rollback requirement:** preserve current canonical eligibility and post-cutover withdrawals/private classifications. Restore a prior consumer only with reconciled currently eligible text or a safe unavailable state; retain canonical records/audit/provenance according to approved recovery. **If static fallback cannot enforce the same current eligibility, restoring it as a public fallback is prohibited until resolved.** Old static FAQ snapshot not a valid escape from unavailable source. Operators/actual traffic switching/backups/monitoring/rollback tests/cutover order/date remain unapproved/unverified; no operation or guarantee here.

## H. Separate approval and evidence registers

Categories below keep product choice, governance, security, content, transport, infrastructure, operational and execution evidence distinct. Owner category for product/application decisions is `PINTU Project Owner (Application Administrator)`. Actual separate security-risk/data/DB/domain/operations mandates remain **NOT IDENTIFIED — OWNER CONFIRMATION REQUIRED** where not evidenced; no names/job titles/appointments, signatures or review outcomes fabricated. Every item uses non-secret closure evidence; operational secrets managed privately outside docs/Git/chat.

### H1. Product decisions — confirmed

| ID | Status / evidence | Closure effect |
|---|---|---|
| PO-2.16-01 | **CONFIRMED — PRODUCT OWNER DECISION**, current explicit instruction | Candidate selection closed only; no overall implementation scope/API/model/reviewer mandate acceptance |
| PO-2.16-02 | **CONFIRMED — PRODUCT OWNER DECISION**, current explicit instruction | Plain answer presentation closed only; normalization/schema/security/data compatibility pending |

### H2. Governance choices — pending

| ID | Required decision / current status | Approver category / dependencies | Closure evidence / blocks slice? |
|---|---|---|---|
| G16-01 | Explicit FAQ lifecycle representation/valid transitions/retention; **PENDING APPROVAL** | Owner + technical/security review; accepted source/model E16-01 | Approved logical and physical mapping/failure/withdraw/repub rules, no invented enums; **yes** for affected read source |
| G16-02 | Explicit current public intent/provenance; **PENDING APPROVAL** | Owner/designated data/security;G16-01/D16-01 | Accepted state/intent predicate and invalid/internal cases; no status/toggle-only adoption; **yes** |
| G16-03 | Whether to retain tampil and exact relation/default/surface behavior; **PENDING APPROVAL** | Owner;G16-01/02 | Explicit option/adoption/missing-invalid decision, can narrow never override eligibility; **yes if used**, not a mandatory display-control feature |
| G16-04 | Legacy/source text P1/P2/P3 policy; **PENDING APPROVAL** | Owner/data custodian/security;D16-01/S16-01 | Accepted record-specific preservation/normalization/remediation plan and no unauthorized transformation; **yes for adopting relevant legacy text**, independent synthetic design still scoped |
| G16-05 | Unicode/newline/whitespace/null/empty/malformed/size validation; **PENDING APPROVAL** | Owner/API/security;G16-04/A16-01/03 | Exact field/request/response/record-hold behavior and limits, not copied UI values; **yes** |
| G16-06 | Canonical source/model/order and update-read success/retention semantics; **PENDING APPROVAL** | Owner/data/technical;G16-01/02/E16-01/O16-01 | Accepted minimal capability/storage/consistency/identity without silent schema approval or browser authority; **yes** |

### H3. Security review — pending

| ID | Required review / status | Approver category / dependencies | Closure evidence / blocks slice? |
|---|---|---|---|
| S16-01 | JSON-to-plain-text threat/rendering/Unicode/HTML-like/URL safety; **PENDING APPROVAL**, verification **NOT VERIFIED** | Actual designated security reviewer;PO-2.16-02/G16-04/05/A16-01 | Reviewed policy and later adversarial render tests proving no HTML/autolink/no secret leaks; document code review is not this approval; **yes** |
| S16-02 | Query/cursor/ordering/rate/size/errors/observability controls; **PENDING APPROVAL** | Designated security/operations;A16-02/03/O16-01 | Approved exact abuse limits/proxy trust/no private cursor/count/error/payload disclosure and test expectations; **yes** |
| S16-03 | Protected admin authentication/MFA/session/assignment gates; **PENDING APPROVAL / NOT VERIFIED** | Separately designated security authority;prior governance | **No anonymous-reader dependency**; **blocks protected writer/admin/assignment** work excluded from candidate. No factor/login/policy chosen. |

### H4. Data/content and consumer review — pending

| ID | Required evidence / status | Approver category / dependencies | Closure evidence / blocks slice? |
|---|---|---|---|
| D16-01 | Accepted FAQ inventory/provenance/markup/URLs/conflicts/quality/current visibility; **NOT VERIFIED** | Owner/authorized data custodian;G16-01/02/04/05 | Per-record accepted/rejected mapping and source preservation, no seed/current-browser assumption; **yes for real content**, approved synthetic model/test source separately needed |
| D16-02 | Plain-text meaning/link-function change acceptance and consumer parity; **PENDING APPROVAL / NOT VERIFIED** | Owner/consumer reviewers;PO-2.16-02/G16-04/D16-01/S16-01 | Approved presentation/material mapping and later authorized cutover/accessibility/link-as-text/native-details evidence; **blocks cutover**, no HTML edit dependency for an explicitly isolated API-only task |

### H5. API contract review — pending

| ID | Required decision / status | Approver category / dependencies | Closure evidence / blocks slice? |
|---|---|---|---|
| A16-01 | Exact DTO keys/types/requiredness/ID/metadata/nulls/envelope/version; **PENDING APPROVAL** | Owner/API/security;G16-05/S16-01 | Approved two-field candidate or explicit scoped alternative still meeting plain-answer requirement; no approved schema inferred from format; **yes** |
| A16-02 | Route/GET/empty/errors/unsupported methods/missing routes/public wording/encoding; **PENDING APPROVAL** | Owner/API/security;A16-01/S16-02 | Precise approved transport and safe failure contract, distinct from admin; **yes** |
| A16-03 | Stable internal ordering/cursor or bounded collection policy/filter/page/response/rate limits; **PENDING APPROVAL** | Owner/API/security;G16-06/A16-01/E16-01/S16-02 | Approved stable key/order/cursor-change/bounded behavior/limits, no hidden ID/count leakage or fake immutable snapshot; **yes** |
| A16-04 | Cache/current-public freshness/CORS/header/target-origin policy; **PENDING APPROVAL**, environment **NOT VERIFIED** | Owner/API/designated infrastructure/security;G16-06/O16-01/E16-02 | Approved no-store proposal or equally valid current-public mechanism, local origin policy and future read-success/failure tests. No TTL exception or production domain invented; **yes for applicable target** |

### H6. Environment/testing evidence — unavailable or unverified

| ID | Required evidence / status | Approver/custodian / dependencies | Closure evidence / blocks slice? |
|---|---|---|---|
| E16-01 | Approved MySQL access/model/isolated test target/network/TLS/grants/recovery; **BLOCKED — ENVIRONMENT** | Appropriate DB/infrastructure custodian, mandate to confirm;G16-06/O16-01 | Privately supplied approved access/isolation/fail-closed target, separately authorized safe connection evidence. SQLite config not MySQL verification; **yes for DB-dependent read/test work** |
| E16-02 | Approved local test/consumer origin and production Cloud/domain/resource access; **NOT VERIFIED / BLOCKED — ENVIRONMENT** | Designated domain/infrastructure/security | Target-specific verified configuration/resources; applicable local requirements **block local integration**, production-only evidence safely deferred while production excluded |
| E16-03 | Approved acceptance design and actual executable/run evidence; **PENDING APPROVAL / NOT VERIFIED** | Authorized technical/security/API test reviewers;G/A/S decisions+E16-01/I16-01 | Reviewed expected-results/dependencies; actual tests only under later authorization. Plan review can close design scope, not runtime readiness; **yes for completed implementation acceptance** |

### H7. Operations and rollback — pending

| ID | Required decision/evidence / status | Responsible category / dependencies | Closure evidence / blocks slice? |
|---|---|---|---|
| O16-01 | Safe observability/audit/recovery/rollback/retention/review and incident responsibility; **PENDING APPROVAL / NOT VERIFIED** | Owner/designated data/security/operations;G16-06/S16-02/E16-01 | Accepted slice-level source/test safety/current eligibility recovery, no credentials/audit tampering; real runbook/tests absent. **Blocks applicable safety**, not a mandate to implement full lifecycle audit now. |
| O16-02 | Consumer migration/cutover/public fallback/rollback monitoring; **PENDING APPROVAL / NOT VERIFIED** | Owner/actual consumer/data/operations custodian;D16-02/A16-04/O16-01 | Approved per-module package/order/window/operators/rollback rehearsal and visibility-safe static behavior; **blocks consumer cutover**, explicitly outside local read-only candidate |

### H8. Implementation authorization — not granted

| ID | Required evidence / status | Authorizing category / dependencies | Closure evidence / blocks slice? |
|---|---|---|---|
| I16-01 | Explicit bounded code/test/environment scope; **NOT AUTHORIZED** | New explicit user task after applicable product/governance/security/API/data/environment evidence | Names accepted version/module/files/packages/model/test target/exclusions/review/stop conditions. Current instruction permits this document only; **yes for all implementation/execution**. |

Separate unresolved register count: **21 pending/evidence items**, grouped as 6 governance, 3 security, 2 data, 4 API, 3 environment/testing, 2 operational and 1 implementation item. Product confirmations are two additional records, not open blockers. Dependencies must be evaluated per target/activity; do not require unrelated Berita/Layanan/Halaman or full production Cloud/authentication decisions for a closed isolated FAQ read slice. Specification choices and security/data review can be reconciled in parallel before live access; design dependencies are not circular demands to execute code or connect to a database before approving a model. Actual connection/testing/implementation acceptance remains separately gated by verified access and authorization.

## I. Planned acceptance and readiness gates

### I1. Future acceptance scenarios

**Every scenario: PLANNED — NOT IMPLEMENTED OR EXECUTED.** Exact codes/fields/limits/policies adopted before executable expected results; future isolated fixtures/consumer render tests separately authorized. No test or DB/HTML manipulation in this phase.

| ID | Scenario | Required pass/fail evidence / dependency |
|---|---|---|
| AC16-01 | Current published + explicit public-intent valid FAQ | Returned only under approved lifecycle/source/DTO contract, anonymous read-only |
| AC16-02 | Draft/unpublished/private/missing/unknown/invalid/malformed/contradictory eligibility | Excluded everywhere, no frontend filtering or known-ID/query bypass |
| AC16-03 | tampil=true with unapproved/unknown lifecycle/intent; false/missing control cases | True never grants eligibility; adopted optional narrowing policy tested separately, no automatic chosen formula |
| AC16-04 | Plain answer serialization and text consumer presentation | Decoded answer is accepted plain text; no HTML interpretation, not pre-double-HTML-encoded accidentally |
| AC16-05 | Source-like `<a>`, script/event attribute, Markdown/entity and URL-looking synthetic strings | Rendered literally or handled by explicitly approved safe text policy, never executable markup or auto-clickable link. No source URL deleted/stripped without approved transformation. |
| AC16-06 | Newline/whitespace/Unicode/null/empty/oversize/malformed text | Exact approved preserve/normalize/hold/error/limit policy, no invented default or silent truncation |
| AC16-07 | Extra internal fields/credentials/account/history/tampil/order/public cursor input | Only approved public DTO/metadata, no secret/state/count/ID leak or arbitrary-object serialization |
| AC16-08 | Equal-order records, cursor or approved result bounds and concurrent changes | Stable bounded behavior follows selected policy, no unbounded query/hidden totals/private key; not falsely immutable snapshot |
| AC16-09 | Anonymous/credentialed public request plus prohibited write/detail/admin attempt | Same eligible public representation; no mutations/private variant/User Management; collection-only scope maintained |
| AC16-10 | Valid empty collection, malformed query, unavailable source, unexpected failure | Approved safe empty/error distinction, no seed/static fallback or internal record/path/stack disclosure |
| AC16-11 | Source unpublish/republish/eligible edit completes before a new read | Withdrawal excluded while record retained; eligible republish restored; successful published edit current immediately. Writes/fixtures separately scoped, no in-flight/global cancellation claim. |
| AC16-12 | Actual cache/origin/abuse/header behavior on approved target | Approved current-public/non-credentialed policy and bounds, no stale withdrawal/conditional/cache bypass or fabricated domain |
| AC16-13 | Static fallback restored after canonical withdrawal/private change | Refused until equivalent current eligibility enforceable; approved recovery exposes only current eligible answers or safe unavailable state |
| AC16-14 | Accepted static/source text map and authorized future homepage consumer | Plain presentation/product meaning/line breaks/accessibility/order match approved review; loss of link click behavior explicitly accepted, no cutover assumed |
| AC16-15 | Synthetic authentication/secret markers and logs | No passwords/tokens/session/recovery material/private payloads/unsafe reflected text in public/audit/error logs under approved observability policy |
| AC16-16 | Approved record-specific legacy normalization/remediation | Original/provenance preserved, only authorized transform/records affected, safe validation/recovery/meaning review; no migration performed by this document |

### I2. Gate assessment, distinct product versus full readiness

PASS means only the precisely named approved/evidenced gate scope, not every dependency or implementation readiness. PENDING APPROVAL, BLOCKED — ENVIRONMENT, NOT VERIFIED and NOT AUTHORIZED retain their ordinary evidence meanings. No FAIL or technical PASS invented when application does not exist.

| Gate | Status | Evidence / what remains |
|---|---|---|
| Product first-candidate selection | **PASS — PRODUCT SCOPE ONLY** | PO-2.16-01 explicit confirmation; operational/custodian/API/implementation boundaries still separate |
| Product answer-format direction | **PASS — PRODUCT FORMAT ONLY** | PO-2.16-02 explicit plain-text/no HTML-rendered links; source normalization/security/schema/parity not passed |
| Lifecycle/public intent/display/model | **PENDING APPROVAL** | G16-01/02/03/06 unresolved, no lifecycle/toggle shortcut |
| DTO/validation/text data policy | **PENDING APPROVAL** | G16-04/05/A16-01 exact two-field packet/limits/envelope still pending |
| Security review and verification | **PENDING APPROVAL / NOT VERIFIED** | S16-01/02 actual appropriate security policy/adversarial/runtime evidence absent; this phase's document review not approval of controls |
| Concrete transport/order/errors/cache/CORS/rates | **PENDING APPROVAL** | A16-02–04/S16-02 exact decisions unapproved, appropriate target environment not verified |
| Accepted data/consumer compatibility | **NOT VERIFIED** | D16-01/02 per-record mapping/link-function/plain parity unresolved; product format direction not migration acceptance |
| Test design and runtime results | **PENDING APPROVAL / NOT VERIFIED** | E16-03 plan pending expected-result adoption, no tests implemented/run; no health/static regression passes copied as FAQ evidence |
| MySQL/test environment | **BLOCKED — ENVIRONMENT** | E16-01 approved target/access/isolation/safe verification unavailable; no SQL/connection attempted |
| Target production infrastructure / local parity | **BLOCKED — ENVIRONMENT / NOT VERIFIED** | E16-02 actual origins/Cloud/environment not proved; unrelated production facts not arbitrary blockers for explicitly approved local-only work |
| Operational recovery/cutover | **PENDING APPROVAL / NOT VERIFIED** | O16-01/02 scope-appropriate safe recovery and separate production fallback/runbook/parity absent |
| Implementation authorization | **NOT AUTHORIZED** | I16-01 new bounded task required; two product decisions do not authorize code |

**Overall: NOT READY.** Narrow candidate and format gates are closed; mandatory governance/security/API/data and environment/evidence gaps remain. This is not READY FOR IMPLEMENTATION AUTHORIZATION REVIEW as a fully closed packet, and not implementation ready. An API-only local task may exclude real production cutover/full auth/MFA and other modules after its own approved dependency set, but cannot skip local source/model/data/security/test/contract requirements.

## J. Phase exit and exact closure plan

1. **Owner closes the next actual governance choices:** FAQ lifecycle/public-intent/optional tampil (G16-01–03), exact DTO/validation/text policies (G16-04/05/A16-01) and accepted record-specific handling of static/source markup/URLs. Do not ask again whether FAQ is first candidate or whether rich HTML is preferred—those directions are settled. Decide P1/P2/P3 by affected accepted records, not silent broad cleanup.
2. **Designated security/API reviewers close transport and verification expectations:** S16/A16 current-public/text/query/limits/error/origin/cache policy, mandate evidence and expected adversarial tests. Product owner is application Administrator, not automatically every security/infrastructure approver.
3. **Data/DB/test custodians supply non-secret readiness evidence:** accepted content mapping/compatibility and safe test/canonical model/recovery requirements; actual grants/access/isolation and separately authorized safe connectivity validation privately. No credential values requested here. Real consumer/cutover/rollback approval remains a separate O16/D16 package; no static fallback until its eligibility limitation is resolved.
4. **User may then issue new bounded implementation instruction** only after applicable closure, naming model/API files, tests/environment, excluded writers/consumers/production and reviews. No scope is granted now. Independent documentation/specification can continue; another phase merely restating confirmed product choices is unnecessary.

Only `backend/docs/PHASE-2.16-FAQ-FIRST-SLICE-DECISION-RECORD-AND-APPROVAL-CLOSURE-PLAN.md` is new. Validate six predecessor/source consistency, two exact PO statuses, no DTO/normalization/API/security approval invented, separate eight registers/counts/gates/planned tests, paths and secret-free complete Markdown/newline/whitespace, `git diff --check` and unchanged baseline fingerprints/status with only this path excluded. Recheck branch/upstream/origin/remote and staging immediately before literal target-only staging; review complete staged diff/`git diff --cached --check`, commit `docs(pintu): record FAQ first-slice decisions and approval plan`, ordinary push to `origin laravel-development`, verify single-file commit/parent/remote/upstream and all 385 prior paths unchanged.

Stop for branch/upstream/conflict/staging/divergence/isolation/hook/access uncertainty; no stage-all, reset/clean/restore/discard/amend/force/history rewrite or previous artifact inclusion. No source/tests/config/dependencies/HTML/UI/schema/data/migration/normalization/audit/auth/MFA/content/API/cache implementation, SQL/connection/runtime test/production probe/deployment/cutover. Documentation publication is not external release authorization. Report actual verification/review/commit/push only after observed results; this phase records product choices and open gates, not implementation approval.
