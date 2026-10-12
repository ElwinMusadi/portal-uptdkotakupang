# PINTU — Public-Read Contract Finalization and Lifecycle Mapping

**Phase:** 2.14. **Date:** 2026-10-12. **Exit:** specification drafted for approval; no application implementation or production readiness declared.

## A. Executive summary and decision status

This document supplies an actionable **recommended contract packet**, not fabricated approval: module-specific lifecycle mapping, proposed public DTOs, rendering rules, transport, limits, compatibility and verification gates. The owner has confirmed the target canonical source and required public behavior, but has not supplied exact schemas, routes, lifecycle encoding, public-intent representation or security/configuration approvals. Those proposed details require the decisions in Section K before implementation.

| Label | Meaning in this document |
|---|---|
| `CONFIRMED — OWNER DECISION` | Explicit recorded project-owner direction, limited to that statement |
| `CONFIRMED — SOURCE EVIDENCE` | Current inspected repository source/definition, not a governance approval or runtime test |
| `RECOMMENDED — PENDING APPROVAL` | Concrete proposed contract/control awaiting the designated approval; not implemented or selected |
| `PENDING — OWNER / GOVERNANCE DECISION` | Policy, mapping, accepted data or authority remains to be decided |
| `BLOCKED — EXTERNAL DEPENDENCY` | Necessary access/environment/security mandate or verified evidence unavailable |

**CONFIRMED — OWNER DECISION:** four modules `berita` / Berita including Pengumuman category, `layanan` / Layanan including nested Persyaratan, `faq` / FAQ, `halaman` / Halaman Statis. No independent Pengumuman/Persyaratan assignments. Exactly `Administrator` and `Pegawai UPTD`; owner `PINTU Project Owner (Application Administrator)`, no invented legal name/title. Laravel is the **target canonical source for all four**, static HTML a consumer/controlled transitional fallback. Incremental migration is approved in principle; order/timing/cutover procedures are not. FAQ and Halaman require explicit lifecycle; optional FAQ `tampil` is not a lifecycle substitute.

**CONFIRMED — OWNER DECISION:** anonymous read-only published **AND explicitly public-intended** content; unknown/malformed/contradictory eligibility fails closed. Assigned employees manage own and peer content, directly publish/edit published/unpublish/republish with no mandatory Administrator re-approval. Successful published edits have immediate public effect. Unpublish hides but retains editable management record, not deletion/archival; eligible republish restores visibility. Only Administrator deletes content or manages users/roles/status/assignments. Multiple employee assignments allowed; revoked module denies subsequent protected requests without re-login, others remain valid.

### Safety gate and evidence register

Root/current directory `D:\Herd\portal-uptdkotakupang`; branch/upstream `laravel-development` / `origin/laravel-development`; origin verified. Preflight local/remote tip `4551086915f2039eca3e6c5ecdbfbfc35a6976d5`, no divergence. Fresh raw Git inventory: **385 outstanding paths = 6 tracked unstaged + 379 untracked, 0 staged**. Target document absent; no overwrite/conflict. Existing diff fingerprint `cac31712e8536b629d9fec47bb290ddea1f9c29e`; file-inventory fingerprint `33f3ae44c6ffdca470d3146961c3b0eb4c9ab1cd` in Git output order. Final comparison excludes only this document. All earlier edits/untracked work remain intact; no branch switch/cleanup/source mutation.

| ID | Current evidence read directly or by read-only source reviewer | Supports / limitation |
|---|---|---|
| EV14-01 | `backend/docs/PHASE-2.10-CANONICAL-CONTENT-MODULE-BOUNDARIES-AND-PUBLIC-READ-SPECIFICATION.md` | Four canonical boundaries/read-first slice, pending concrete transport, no prior schema/code approval |
| EV14-02 | `backend/docs/PHASE-2.11-AUTHORITATIVE-CONTENT-SOURCES-VISIBILITY-MAPPING-AND-PUBLIC-PROJECTION-SPECIFICATION.md` | Local/static duplicates, visibility/DTO/consumer gaps; historical source evidence not production truth |
| EV14-03 | `backend/docs/PHASE-2.12-CONTENT-AUTHORITY-AND-PUBLICATION-GOVERNANCE-DECISIONS.md` | Options/rendering/migration/approval questions; recommendations not retrospectively approved |
| EV14-04 | `backend/docs/PHASE-2.13-GOVERNANCE-DECISION-FINALIZATION-AND-IMPLEMENTATION-READINESS.md`, D-213-01–04 | Confirmed Laravel authority, incremental migration, FAQ/Halaman lifecycle; other choices still pending |
| SRC14-01 | `backend/routes/api.php:6-10`; `backend/bootstrap/app.php`; `backend/app/Models/User.php`; `backend/app/Http/Controllers/Controller.php`; `backend/database/migrations/` | API file defines health only; scaffold model/controller and three scaffold migrations, no content model/controller/migration in inspected directories. No live DB/schema existence asserted. |
| SRC14-02 | `dashboard-app/src/lib/api/types.ts:20-57,76-93,127-154,219-225`; `dashboard-app/src/lib/alur.ts:7-29`; `dashboard-app/src/lib/meta.ts:96-163` | Actual types/workflow values, FAQ toggle and Halaman missing publication field; prototype labels not extra roles or approved backend lifecycle |
| SRC14-03 | `dashboard-app/src/lib/api/index.ts:9-12`; `dashboard-app/src/lib/api/http.ts:31-129`; `dashboard-app/src/lib/api/local.ts:74-82,117,151,198,244,316`; `dashboard-app/src/lib/api/seed.ts` | Adapter selection, unfiltered local management data/example seeds, bare admin JSON/errors, no network-error fallback to LocalApi |
| SRC14-04 | `dashboard-app/src/pages/berita/editor.tsx:143-145`; `dashboard-app/src/pages/layanan/editor.tsx:134-135`; `dashboard-app/src/pages/halaman/editor.tsx:67-68`; `dashboard-app/src/pages/faq.tsx:17,49,121`; `dashboard-app/src/lib/format.ts:105-141`; `dashboard-app/src/lib/validasi.ts:40-44`; `dashboard-app/src/components/form/rich-text-editor.tsx:77,150-160` | Snapshot-only slug checks, plain FAQ string, slug/text helpers and editor link/schema limits; not DB uniqueness or sanitizer proof |
| SRC14-05 | `assets/js/main.js:154-161,511-536,592,600`; `index.html`; `informasi.html`; `berita-detail.html:189-248`; `layanan.html:224-333`; `kebijakan-privasi.html`; `syarat-ketentuan.html` | Static public content/DOM filters/checklists and generic detail/service anchor mismatches; no fetch/XMLHttpRequest content API call found in inspected main.js |
| SRC14-06 | `backend/tests/Feature/HealthCheckTest.php`; `backend/tests/Feature/ExampleTest.php`; `backend/tests/Unit/ExampleTest.php` | Existing health/error/scaffold assertions, not public-content filter/DTO tests. Read only, not run. |

All listed evidence exists locally and was inspected for relevant findings; no full general code audit or private environment/database inspection. Current source checks corroborate prior gaps. Historical/local reports remain references, not proof of their availability in a clean remote checkout. Earlier docs/foundation/artifacts are not bundled into this commit. No application test, SQL/connection, package/config/frontend/previous-document change or deployment occurs.

## B. Canonical source and consumer boundaries

**CONFIRMED — OWNER DECISION:** Laravel owns accepted authoritative content for all four modules as the target architecture. **CONFIRMED — SOURCE EVIDENCE:** today there are static HTML representations and per-browser IndexedDB/memory/seed representations, optional administrative HttpApi calls, and only health in inspected Laravel API source. No content persistence/public DTO/filter/assignment enforcement is established; source direction must not be confused with implementation.

| Boundary | Responsibility and preserved rule | Pending implementation evidence |
|---|---|---|
| Laravel canonical records | Current accepted content/lifecycle/public intent and authorized mutations; no competing browser/static truth after cutover | Approved schema/data mapping, MySQL/model/test access, lifecycle/intent representation |
| Public-read API | Anonymous safe allowlisted projection of currently eligible records; never administrative record serialization | Proposed Sections D/F need approval; no routes installed |
| Public HTML | Existing presentation consumer or explicitly controlled transitional fallback, no independent writes/divergence | Link/anchor/render mapping and immediate-public consistency proof; no current consumer code changed |
| Dashboard | Protected management representation under two roles/current modules; can see authorized non-public data | Existing DTO/error contracts preserved; no silent replacement with public-only DTO/envelope |
| Local/seed representations | Non-authoritative inputs/examples until explicitly reconciled and accepted | Owner per-record adoption/provenance, no auto-import/publication or timestamp priority |

Conflicts require accepted identity/provenance/content/visibility decisions by owner or explicitly designated custodian. No latest-time-wins/seed-over-HTML choice. Fallback/rollback may serve only reconciled current-eligible projection; source failure cannot switch to unfiltered seeds/static snapshots that restore withdrawn/private data. If current eligibility cannot be established, fail closed using approved error behavior. Backup/copy retention and actual withdrawal-safe fallback mechanism need separate verification.

## C. Lifecycle and public eligibility — four separate mappings

**CONFIRMED — OWNER DECISION:** public eligibility always includes current publication and explicit public intent. State/display/timestamp fields alone cannot override invalid, missing or contradictory eligibility. Logical labels below are semantics, not database enums. **RECOMMENDED — PENDING APPROVAL:** reject/quarantine invalid source records for management remediation and omit them from public results; exact quarantine mechanism not chosen. Field-format defects handled by Section D/E, never fabricated eligibility defaults.

### C1. Berita / Pengumuman

**CONFIRMED — SOURCE EVIDENCE:** `Berita` extends `Entri,Beralur`; `status` has `draf`, `diperiksa`, `persetujuan`, `terbit`, `dikembalikan`, `arsip`; `kategori` includes `pengumuman`. `patchAlur` sets `terbitPada` when entering `terbit` and retains prior value otherwise. Old client `Tarik dari portal` maps `terbit` to `arsip`. None proves public-intent or approved unpublish=archive mapping.

| Observed/logical condition | Recommended public mapping, awaiting approval | Required intent/display/invalid rules and lifecycle effects |
|---|---|---|
| Source `terbit` | Treat as candidate current-public lifecycle only after validated source mapping | Eligible only with independently resolved explicit public intent and safe DTO; category/feature/date not overrides |
| Source `draf`, `diperiksa`, `persetujuan`, `dikembalikan` | Exclude; no mandatory review-stage path imposed for future direct publication | Logical draft/review metadata can remain management-only if approved; all protected changes follow role/module checks |
| Source `arsip` | Exclude publicly for first read slice until archive policy explicitly decided | Do not equate to logical unpublish or automatically delete; any future historical public representation needs separate approval |
| Missing/invalid/contradictory state, old non-null time | Exclude; timestamp alone not candidate eligibility | No scheduling/future-time rule invented; metadata time semantics pending |
| Unpublish/republish/edit logical actions | Hide and retain editable record; republish eligible again; successful published edits immediate | Internal unpublish encoding, history/time/retention/concurrency/success boundary pending; no Administrator re-approval |

This mapping proposal is T14-01; it is not an approved `WHERE status='terbit'` schema/query and never a status-only predicate. Pengumuman uses the same rules/assignment as Berita; no separate endpoint authority/module.

### C2. Layanan / Persyaratan

**CONFIRMED — SOURCE EVIDENCE:** Layanan also extends Beralur; requirements are nested objects with source `id,dokumen,jumlah`, no separate lifecycle. Full service visibility/source-intent model unimplemented.

| Observed/logical condition | Recommended public mapping, awaiting approval | Parent/child and action semantics |
|---|---|---|
| Valid mapped current publication (candidate source `terbit`) | Parent eligible only with explicit public intent | **RECOMMENDED — PENDING APPROVAL:** requirements inherit parent as outer visibility bound, with only approved child fields/items; no independent module/grant/state |
| Parent draft/unpublished/internal or unknown/invalid state | Exclude parent and every nested public representation | Known child ID or request projection cannot reveal ineligible parent data |
| Parent public with potentially private/malformed child fields | Apply approved nested allowlist/validation; unresolved child classification fails closed for affected output | **PENDING — OWNER / GOVERNANCE DECISION:** whether all accepted requirements are public or selective child/item restrictions needed; no new child flag mandated |
| Parent unpublish/republish/published edit | Retain record/requirements; hide/restore eligible projection; updates immediate after success | No automatic file/child deletion, physical status/history/retention design pending; assigned peer management/direct publication preserved |

Candidate source workflow values should be validated/mapped like Berita, but concrete lifecycle transitions/intent and nested classification require T14-02 approval. Owner parent/module boundary is confirmed; storage/query representation is not.

### C3. FAQ

**CONFIRMED — SOURCE EVIDENCE:** Faq has `pertanyaan,jawaban,tampil` and Entri metadata, no Beralur/status/terbitPada. **CONFIRMED — OWNER DECISION D-213-03:** explicit publication lifecycle required, tampil at most an optional additional display control.

| Logical/observed condition | Recommended mapping / visibility | Remaining decision and consequences |
|---|---|---|
| Explicit approved current publication AND public intent | Eligible candidate FAQ representation | Lifecycle physical encoding not selected; do not use `tampil=true` as either prerequisite proof |
| Draft/unpublished/private/unknown lifecycle or intent | Excluded even when `tampil=true` | Existing toggle-only records must be reconciled, not automatically published |
| Optional display control retained | **RECOMMENDED — PENDING APPROVAL:** treat false as suppression from all first-slice public FAQ reads, true only permits otherwise eligible records | Adoption and per-surface policy not confirmed. If option adopted, missing/invalid control suppresses display; no new default/backfilled value approved. Alternative list-only presentation requires explicit separate contract. |
| Unpublish/republish/published edit | Retain editable FAQ; hide/restore eligibility; successful eligible published edit immediate | No forced admin approval or optional-toggle override; approved physical mapping/transitions/time/history pending |

T14-03 must decide explicit lifecycle/intent encoding and whether the proposed conservative display rule is adopted. No schema column or new status enum introduced; no reversal of explicit lifecycle requirement.

### C4. Halaman Statis

**CONFIRMED — SOURCE EVIDENCE:** Halaman fields are slug/title/continuation/preamble/body plus Entri, no publication field; static pages/seed draft text coexist. **CONFIRMED — OWNER DECISION D-213-04:** Laravel canonical page management with explicit lifecycle.

| Logical condition | Required visibility / recommended implementation mapping | Pending requirements |
|---|---|---|
| Current published AND explicitly public-intended accepted page | Eligible safe page projection | Approved included pages, lifecycle/intent representation and body sanitizer; source existence/slug not proof |
| Draft/unpublished/private | Excluded | Explicit model capability required; no auto-public static exception |
| Missing/unknown/invalid eligibility | Excluded | Current source lacks sufficient state: hold from public integration until classification/mapping approved |
| Unpublish/republish/published edit | Stored/editable record retained, public hidden/eligible restored, successful edit immediate | Internal state names/fields/history/retention pending; no draft/revision approval delaying valid edits |

T14-04 approval must specify capability/encoding/transition guard/public-intent, without claiming current draft/published/unpublished enum. Safe page-body rendering is required; legal/reference source acceptance remains the owner's/content custodian's decision, not inferred legal approval.

### C5. Cross-module failure and consistency

**RECOMMENDED — PENDING APPROVAL:** write success must not be reported until the chosen canonical/public eligibility and freshness boundary can deliver the confirmed subsequent-read behavior. Specify failure/retry/version semantics separately; no multi-system transaction guarantee, chosen cache purge or in-flight cancellation asserted. Unpublish must not destroy management record or history/files implicitly; archive/removal/retention policies still pending. Already copied/downloaded content cannot be remotely erased, but live public paths cannot serve it as current after withdrawal. Optional display and accepted projection restrictions may narrow eligible output, never broaden eligibility.

## D. Proposed public field allowlists / DTO packet

**Every public field selection in this section is RECOMMENDED — PENDING APPROVAL (T14-06), not an approved schema.** Source names/types are confirmed evidence; public DTOs would be separately constructed, never spread/serialize entire models/admin records. No new stored fields proposed. Unspecified fields omitted. Lists and details share eligibility, even when list projection omits larger body/nested data.

| Module | Proposed list DTO / proposed detail additions | Source types, nulls and malformed-data recommendation | Exposure decision |
|---|---|---|---|
| Berita | List: `judul,slug,kategori,ringkasan,sampul,keteranganSampul,tanggal`. Detail adds `isi,kreditFoto`. | Strings; kategori validated against approved source-supported values; sampul string or null; tanggal valid calendar-date string. Preserve approved empty caption/credit strings, no guessed date/cover. Invalid eligibility excludes record; malformed required identity/body/date holds record from this proposed representation. | Slug is proposed public lookup, not proof of server uniqueness. `sorotan,pengumumanBeranda` **not** in baseline DTO; may be added only by approval. `terbitPada` not emitted/filter proof. |
| Layanan | List: `nama,slug,ringkasan,loket,jenis,cekFisik,nonTunai,diwakilkan,label,unggulan`. Detail adds `persyaratan:[{dokumen,jumlah}],catatan`. | Strings, booleans; jenis source enum; label string array; requirements retain textual jumlah and server-approved order. No child ID. Empty catatan/labels may be preserved. Malformed required parent/child content rejected/held, not raw arbitrary object. | Proposed public notes only after accepted-content review. Parent/child id/time/order fields not exposed. Source values represent content, not verified official service requirements. |
| FAQ | List/detail: `id,pertanyaan,jawaban`. | Proposed source-compatible opaque public resource ID string (new public exposure needs approval), other strings; initial recommendation escaped plain text, no raw HTML. Empty/malformed required content held under agreed validation. | FAQ lacks slug; exposing current opaque content ID is proposed to support detail, not account ID. Alternative list-only/no-ID contract remains pending; choose one before coding. `tampil` and urutan not serialized. |
| Halaman Statis | List: `slug,judul,judulLanjutan,pengantar`. Detail adds `isi`. | Strings; body is approved sanitized HTML; empty continuation/preamble may remain empty, no placeholder silently presented as approved text. Null/malformed body handled by approved hold/error policy. | Proposed slug lookup/public body only for accepted pages; source page ID/common metadata omitted. No automatic public status from page presence. |

**Excluded:** raw `penyedia,pemeriksa,catatanRevisi,riwayat`, approval/review history/actor metadata, account/user/assignment/audit records, credentials/session/reset/access tokens, private media paths, storage keys/checksums/debug/stack/env details. Common `dibuat,diperbarui,urutan` and resource `status` not baseline public fields. `id` excluded except explicitly proposed FAQ public resource reference; no blanket rule all IDs safe/private. `Layanan.catatan` is candidate public service copy, not internal `catatanRevisi`.

Public date fields beyond source tanggal, timestamps/featured flags/ordering controls and category extensions need explicit field/meaning/precision/timezone/exposure approval. Proposed DTO uses existing keys only, without changing administrative representations. Distinguish nullable source media versus malformed unsafe media: **recommend** null for an optional rejected/missing cover with protected validation evidence, but owner must approve whether to reject publication instead. No credential/private URL exposed as a fallback.

Before approval, specify exact string/array/body limits and source uniqueness/public-ID mapping, missing/null/legacy normalization, allowlisted media/link output and any per-module field-policy exceptions. Detailed generic JSON-schema validation is a future artifact; this proposed shape is not a migration or serializer implementation. No automatic data loss by coercion/truncation.

## E. HTML, rich text, media and link security

**CONFIRMED — OWNER DECISION / earlier security requirements:** public allowlists and no private/credential leakage; safe rendering requires server enforcement, not trust in editor. **CONFIRMED — SOURCE EVIDENCE:** Tiptap limits some client tags/links, `teksPolos` uses DOMParser to extract text rather than produce safe HTML, and LocalApi accepts merge patches; no inspected backend content sanitizer exists. Unimplemented administrative HTTP routes are not claimed live exploitable endpoints.

**RECOMMENDED — PENDING APPROVAL (T14-07/08):**

1. Render plain-text strings with context-appropriate escaping/text APIs; prefer plain text for FAQ questions/answers, Layanan descriptions/notes/requirement strings and titles/preambles. FAQ static inline links need an explicit product format choice; text-only proposal is not permission to silently remove existing links. Approve a safe linked/structured representation or reviewed sanitized HTML if required, with a separate contract change; no new fields invented as approved.
2. For approved Berita/Halaman body HTML, a maintained server-side parser/sanitizer with context-aware output—not regex—should permit only a reviewed formatting vocabulary. **Proposed initial tag set:** `p,h2,h3,ul,ol,li,blockquote,a,strong,em,u,br`. **Proposed attributes:** href/title on links only plus server-generated safe rel; no arbitrary style/class/id/events/data attributes or embedded objects/forms/iframes/scripts/SVG/math. This exact list and legitimate editor formatting compatibility need security/owner approval; no library installed/selected as approved.
3. Validate links structurally after decoding/normalization. **Proposed** allowed protocols: https/http/mailto/tel and reviewed same-site relative paths/fragments; prohibit javascript/data/vbscript/file/blob, protocol-relative `//` and control/obfuscated unsafe schemes. Approve per-context restrictions; same-site relative resolution must not allow private/admin/storage traversal. External links must not opener-manipulate: prefer same-tab; if new-tab allowed, server policy sets safe rel. No untrusted target/attribute pass-through.
4. Cover/media URL output only from approved public storage/reference boundaries after normalization/type/visibility validation. No raw private storage path, credential/signed secret query or arbitrary remote server fetch; SSRF protections needed if later approved media processing fetches remote sources. Binary/storage classification, public versus private URLs, host/protocol policy and retained-media behavior after unpublish need approval, not a guessed CDN/domain/provider. Body images not in baseline tag proposal; approved cover image separate.
5. Handle malformed HTML through safe parser policy: strip/reject scripts, event-handler attributes, embedded active content and unsafe URLs; choose reject/neutralize and feedback policy before code. No raw unsanitized HTML insertion in public consumers, no trusting imported seeds/editor output, and no arbitrary HTML in plain fields. Approved sanitizer applied before HTML is served; storing-original versus storing-sanitized and repeat-read defense choices remain pending.
6. Unknown eligibility fails closed independently of text cleanup. Do not transform an invalid state into publication, or let sanitizer omission select an unsafe fallback source. Validate Unicode/UTF-8, control characters, string/array/body bounds and safe HTML/link semantics without overwriting public Indonesian copy or silently mutilating accepted content.

Required evidence: accepted formatting/link/media/body/null rules, security reviewer mandate, adversarial parser/URL/media tests, and current consumer parity. Authenticated-path vulnerabilities must also be reviewed; HttpOnly/MFA/CSRF do not neutralize stored XSS. No CSP/CDN/storage/sanitizer package or rewrite is approved merely by this proposal.

## F. Recommended public API transport packet — pending adoption

**All routes, DTO/envelope choices, codes, numeric proposal values and transport controls here are RECOMMENDED — PENDING APPROVAL (T14-09–13).** They are new-contract options for anonymous read only, not existing capabilities or changes to admin responses. Use source-compatible module keys; no independent Pengumuman/Persyaratan routes required. No version prefix recommended for the first proposed contract; future version policy separately reviewed.

### F1. Base paths, methods and resources

| Module | Proposed collection | Proposed detail | Decision dependency |
|---|---|---|---|
| Berita | `GET /api/public/berita` | `GET /api/public/berita/{slug}` | Accept slug uniqueness/normalization/reuse/legacy continuity and approved category filter |
| Layanan | `GET /api/public/layanan` | `GET /api/public/layanan/{slug}` | Resolve source slug versus public anchor mapping; requirements returned within detail, no separate module endpoint |
| FAQ | `GET /api/public/faq` | `GET /api/public/faq/{id}` | Approve opaque content-ID exposure and stable lookup, or reject this detail proposal in favor of list-only; do not invent a slug field |
| Halaman Statis | `GET /api/public/halaman` | `GET /api/public/halaman/{slug}` | Approved included pages/slug identity and lifecycle/public-intent mapping |

Restrict explicit route/module allowlist; no dynamic route to arbitrary collection/model/table. GET is read-only; no anonymous writes or method-override mutation. Framework HEAD behavior must be reviewed/tested if available, not used as a visibility bypass. Proposed unsupported methods 405, absent route/module 404, no management capability. `GET /api/health` remains unchanged and separate. All exact routing needs approval before implementation; current admin `/koleksi/*` paths are not redirected or repurposed.

### F2. Response envelopes, schema and encoding

**Proposed:** successful collection is a bare JSON array of the approved list DTO, detail a bare JSON object of its approved detail DTO. This avoids an unnecessary new success envelope and matches existing source conventions, but is a **new public-contract recommendation**, not approval inferred from admin HttpApi. Valid empty result is `[]`, status 200. UTF-8 JSON with `Content-Type: application/json`; strings retain Indonesian Unicode, no HTML wrapper/JSONP/debug output. Safe absence is status 404, not detail null/204; future public client explicitly handles this independently of existing admin 404-to-null behavior.

**Proposed error DTO:** `{"message": "<approved safe public text>"}`; validation may add `errors` containing only public allowlisted parameter names/messages. No raw rejected values, source/model existence, SQL/filesystem/stack/host/env/actor/record state. Exact Indonesian wording requires content approval and is not changed/created in UI here. Safe server-generated `X-Request-ID` header is a proposed correlation aid, not an identity or credential; validate/replace untrusted incoming correlation. Public success DTOs contain no diagnostics.

| Proposed status / case | Recommended body/meaning, not approved endpoint |
|---|---|
| 200 success / empty | Bare DTO array/object; valid empty collection `[]`, not source outage |
| 404 missing/non-public detail | Same safe error class for nonexistent/draft/unpublished/private/invalid target; no distinction or internal count |
| 400 malformed request | Safe message for structurally unreadable request/query/encoding, no reflected unsafe input |
| 422 invalid supported parameter | Approved safe message/errors, no private field discovery; unknown query parameter rejected under proposal |
| 405 unsupported method | Safe error, no mutation; review framework Allow header/path surface |
| 429 abuse/query throttling | Safe error + approved Retry-After policy; thresholds pending, no rate numbers approved |
| 503 unavailable canonical source/eligibility service | Safe error, no unfiltered seed/static fallback or pretending empty success |
| 500 unexpected error | Generic safe message/correlation, no implementation detail or private record |

These error codes/field names are proposal T14-09/11, not current accepted protocol. Public readers do not require 401/login/MFA; management failures remain a separate protected contract. Reject ambiguity rather than dynamically expanding filters/projection.

### F3. Deterministic bounded pagination, ordering and filters

**Proposed baseline:** cursor pagination on all four collections, bare arrays with optional standard `Link` header for next page, no totals. Proposed `limit` default 20 / maximum 100 and query-length validation; these are **review values, not approved workload/security policy**. Owner/technical review must approve limits after expected data sizes/consumer needs. Reject invalid limits/cursors/unknown query rather than silent truncation. Cursor is server-produced opaque validated public ordering reference, not authentication and never a source of permission/private counters; exact encoding/version/signing/storage scope pending.

**Proposed sort:** approved source `urutan` ascending with stable opaque record key as tie-breaker, applied only to eligible records; ordering metadata need not appear in DTO. Determinism does not imply immutable snapshots under concurrent edits; define paging restart/change behavior and optional snapshot/version design before coding. If source order/tie-breaker/public cursor identity isn't approved, pagination implementation is blocked rather than guessing fields.

**Proposed filter:** Berita permits `kategori` only with reviewed existing category values; Pengumuman stays under Berita. First baseline has no user-selected status/public-intent/owner/arbitrary field/sort/search filter and no `include`/preview/private projection option. Layanan/FAQ/Halaman use only approved pagination parameters initially. Any later search/related/filter/facet must apply the same eligible-set predicate before counts/cursor/links; static chip/search behavior is not silently replaced without authorized consumer compatibility work.

Collection bodies and detail/nested responses must be bounded. **Proposed provisional serialized-response cap:** 1 MiB including response/error overhead; select actual accepted record/body/array/media URL limits and predictable oversize-source behavior with product/security approval. Do not truncate text/requirements or advertise a read-success that drops essential content. Oversized canonical data is a validation/availability issue, not proof the client's request needs status 413; exact hold/error response decision pending. Page maximum items alone does not cap rich bodies/nested arrays or cost.

### F4. Caching, CORS and abuse

**Proposed safest first-slice cache policy:** `Cache-Control: no-store` on success/absence/error, no conditional 304/ETag/Last-Modified until a verified current-eligibility strategy is approved. This is a performance tradeoff and not an approved CDN purge mechanism. Any later public caching/conditional reads must revalidate current visibility after edit/unpublish/republish and guarantee the established success boundary; no stale 404/304/current-success response, stale-while-revalidate or TTL exception silently allowed. In-flight responses already started are separately handled; no global erasure/instant distributed atomic guarantee. Existing static fallback requires the same withdrawal-safe current classification or an approved safe error state.

**CORS:** actual frontend/API topology/origins unverified. Recommend exact approved public-consumer origin allowlist, anonymous requests without credentials, and no session/private representation variation on public endpoints. CORS is not authorization or privacy filtering. Different protected SPA credentialed CORS/CSRF/cookie rules remain separately gated. Proposed public `Link`/correlation headers need explicit expose-headers if cross-origin consumers use them. No production domains, wildcard configuration or framework middleware change selected.

**Abuse/query controls:** recommend validated bounded limit/cursor/category and detail identifier, explicit string/body/nested output caps, throttles appropriate to actual read cost/network trust and safe 429/Retry-After. Public rate thresholds/windows/per-IP/distributed policy and trusted proxy rules require infrastructure/security review, not sample numbers inferred as official policy. Do not deny all anonymous reads to avoid deciding them. Fault/source errors must not leak internal details or allow arbitrary expensive queries/URL fetching.

## G. Consumer compatibility and per-module migration

**CONFIRMED — SOURCE EVIDENCE:** current HTML is static, inspected main.js uses DOM filters/checklist counters but no content fetch/XMLHttpRequest/API calls. News detail links are generic `berita-detail.html` without record selector; related links share that same file. Service source slugs and static anchors differ, e.g. `perpanjangan-5-tahun` versus `lima-tahun`; FAQ source is plain textarea/string while static answers contain links; privacy/terms seed/editor HTML differs structurally. Admin HttpApi expects existing bare arrays/entities/204/error behavior and no automatic LocalApi fallback. Slug uniqueness checks use loaded client snapshots, not verified DB constraints.

| Module/consumer | Required compatibility acceptance before consumer integration |
|---|---|
| Berita homepage cards/announcements/info/detail | Approved slug/record mapping, proper specific article link/title/body/cover/date/category behavior; preserve current visible wording/design and no generic detail mismatch. Proposed public list/detail separation doesn't modify admin reads. |
| Layanan lists/anchors/home tabs/checklists | Reviewed anchor/slug mapping, requirements/note/quantity formatting and correct counts, additional-service placement and actual accepted attributes; no seed-as-official requirement or silent ID rename. |
| FAQ home details | Retain approved question/order/answer/link intent; plain-text baseline rendering proposal needs explicit resolution of existing links, no unsafe HTML or loss by assumption. Explicit lifecycle separate from display toggle. |
| Halaman privacy/terms | Reviewed accepted body/slug/page inclusion and sanitized prose/layout/links, not auto-published draft/legal-review seed text. No legal approval invented from static appearance. |
| Dashboard HTTP/LocalApi | Keep admin raw-entity editing/workflow/error expectations and local prototype behavior unchanged; no public DTO used to hide management drafts or overwrite browser records. Migrate only separately reviewed content, no browser actor/role trust. |

**CONFIRMED — OWNER DECISION:** incremental module migration with validation/cutover/rollback; order/date **PENDING — OWNER / GOVERNANCE DECISION**. Reusable acceptance package:

| Step | Required evidence / approval and stop condition |
|---|---|
| 1 Source inventory | Static/browser/seed/media/provenance inventory, no secret snapshots published; accepted scope and custody named |
| 2 Canonical mapping | Reviewed record/category/parent/nested identities, slugs/anchors/URLs; explicit owner conflicts, no latest-time/seed priority |
| 3 Quality/completeness | Per-record keep/merge/reject, approved data/links/body/media/required fields and rejection report; no content silently lost or made public to hide gaps |
| 4 Lifecycle/intent | Approved per-module visibility and invalid handling, FAQ/Halaman explicit lifecycle capability, unknown excluded |
| 5 Projection/rendering | Approved DTO/security policy and adversarial/field-leak evidence; actual sanitizer/media rules validated later |
| 6 Consumer compatibility | Actual authorized staging tests for public paths/static layouts/links/checklists/prose and unchanged admin contract |
| 7 Cutover | Explicit module/source/read/write/in-flight/freshness success boundary, operators/window/accepted evidence and rollback trigger; no operation authorized by this document |
| 8 Rollback | Preserve post-cutover withdraw/private classifications and mapping; restore only reconciled eligible public view. A temporary static consumer may be restored only with authoritative current eligibility or fail closed, never stale unfiltered pages. Procedure/backup/verification needs approval. |
| 9 Monitoring/reconciliation | Verify read/visibility/error/parity/drift/recovery and safe alerts, custodians/review cadence/incident responsibility; no SLA/retention values invented |

Backend canonical ownership doesn't itself prove consumer freshness or recovery. Rollback must not restore ineligible public content, erase new accepted records or corrupt other cut-over modules. Previously downloaded copies not remotely erased. No source switch, consumer change, migration order or backup/restore/test execution now.

## H. Management authorization and audit

**CONFIRMED — OWNER DECISION:** Administrator full approved app/User Management/roles/status/assignments/delete; Pegawai assigned canonical modules only, eligible own/peer create/edit/publish/unpublish/republish, no delete/User Management/self-assignment. Backend active status/current assignments/action/target controls, not frontend UI/local claims. Revocation denies every subsequent protected module request without re-login, leaves other valid assignments. In-flight/long-lived handling requires explicit design, no cancellation guarantee.

Management permissions are separate from anonymous public eligibility. Public read endpoints must not expose admin-only representations when presented with a cookie/bearer token; no implicit private expansion. Invalid/missing module assignment doesn't make public content private to everyone, and public visibility grants no write authority.

**RECOMMENDED — PENDING APPROVAL (T14-14):** lifecycle/assignment events record server actor/target/module/action/time/outcome/safe correlation and accepted visibility/version changes. Proposed action vocabulary `create`, `edit`, `publish`, `unpublish`, `republish`, `delete`, `module_assignment_changed` describes audit intent only; event names/schema/read/retention/disposition/transaction/failure rules unapproved. No new audit role or implemented log. Never log credentials, passwords/hashes, reset/access/session tokens, full cookie headers, sensitive request bodies or environment secrets. Failure/unknown identity not falsely attributed from a user-supplied name.

## I. Implementation-readiness gate

| Prerequisite | Current label / required decision owner category | Closure and blocking effect |
|---|---|---|
| Four modules/two roles/Laravel canonical target/incremental method/FAQ-Halaman lifecycle | **CONFIRMED — OWNER DECISION** | Direction closed, actual schemas/valid transitions/data and operational scope not implied |
| Current types/adapters/static sources/routes/tests | **CONFIRMED — SOURCE EVIDENCE** | Inform choices, not current DB/table/API/filter readiness |
| Lifecycle/public-intent encoding and optional controls | **PENDING — OWNER / GOVERNANCE DECISION**, owner with technical/security review | Approve T14-01–05 mapping/guards/unknown intent; prerequisite for each module query/schema/read acceptance |
| Public DTOs/identity/limits/HTML/media | **RECOMMENDED — PENDING APPROVAL**, owner/data and designated security authority | Approve T14-06–08/10/12 field schemas, formatting/limits and security tests; unsafe projection blocks read release |
| Routes/methods/envelopes/codes/paging/order/filter/cache/CORS/abuse | **RECOMMENDED — PENDING APPROVAL**, owner/API/security/infrastructure reviewers | Approve T14-09–13 packet or explicit alternatives, with exact expected negative behavior; no current contract accepted implicitly |
| Migration/source acceptance/consumer/cutover/rollback order | **PENDING — OWNER / GOVERNANCE DECISION**, owner and authorized data/operations custodian | Approved accepted data, identity/consumer mappings and module package; order/date pending; no retrospective bulk import or staging |
| Audit/governance/operational accountability | **PENDING — OWNER / GOVERNANCE DECISION**, actual relevant security/data/operations mandates to identify | T14-14 record/consistency/access/retention/incident requirements, safe delivery and authority; no invented official titles |
| MySQL/test target/access/model/recovery | **BLOCKED — EXTERNAL DEPENDENCY**, approved DB/infrastructure custodian | Verified privately managed grants/target/TLS/isolated test DB and separately authorized safe connectivity/model/recovery. No operational fallback/test teardown/SQL now. |
| Protected auth/MFA/session/assignment writes | **PENDING — OWNER / GOVERNANCE DECISION / BLOCKED — EXTERNAL DEPENDENCY** | Prior security policy/authority/config unresolved; separate protected implementation gates, not a login requirement for anonymous reads |
| Production infrastructure/origins/release/operations | **BLOCKED — EXTERNAL DEPENDENCY**, actual domain/Cloud/operations/security mandates unknown | Verify actual environments/resources/TLS/secret separation/recovery/ownership/release and relevant public CORS; no production domain/probe/deploy from documentation |
| Bounded implementation instruction and test scope | **PENDING — OWNER / GOVERNANCE DECISION**, explicit new user task | Names accepted decisions/module/files/package/test target/exclusions/review; no code authorization now |

Actual separate security/DB/domain/Cloud/incident decision mandates remain **NOT IDENTIFIED — OWNER CONFIRMATION REQUIRED** where unevidenced. These are responsibility categories, not additional application roles or fabricated names. Owner app role alone doesn't accept security residual risk. Anonymous local read implementation need not wait for unrelated protected MFA/production Cloud resources once its own data/security/source/test/contract scope closes; it cannot bypass them when enabling protected actions/production cutover.

## J. Required acceptance test matrix — all future, none executed

**Every row: FUTURE REQUIRED TEST — NOT IMPLEMENTED OR EXECUTED.** Existing health/error/scaffold tests are source evidence only and were not run here; no public-content tests already passed. Synthetic isolated fixtures/actual routes follow only the approved future packet; proposed codes/limits/maps are not used as approved policy until accepted.

| ID | Setup / action | Required expected result and dependency |
|---|---|---|
| AT14-01 | Each module with mapped published/public-intended record | Allowed public DTO only, no auth/MFA required; exact map/field contract approved first |
| AT14-02 | Draft, retained-unpublished, internal/private, unknown/missing/malformed/contradictory eligibility via list/detail/related | Excluded; same predicate prevents known ID/category/filter/count leak; no all-public Halaman/tampil/timestamp shortcut |
| AT14-03 | Authorized unpublish then public lookup/list/cache path | No subsequent public representation, retained management record; safe current-eligibility boundary, no file/history delete inferred |
| AT14-04 | Authorized republish with/without current public intent | Eligible returns only when all conditions pass; stale intent/internal data not resurrected, no mandatory Admin approval |
| AT14-05 | Successful published edit then actual public read | Immediate updated approved projection, no approval-delay or intentional stale success; in-flight boundary defined, not magic global invalidation |
| AT14-06 | Public/ineligible Layanan parent, nested requirements/private malformed child | Ineligible parent leaks no child; approved nested allowlist/order/string quantity respected, no independent assignment |
| AT14-07 | FAQ draft/unpublished with tampil=true, eligible with adopted optional false/invalid rule | Toggle never grants eligibility; adopted optional display behavior tested independently, no invented enum/default |
| AT14-08 | Halaman missing/unknown current lifecycle/public intent | Hidden despite existing static page/body/slug; approved body policy and included pages enforced |
| AT14-09 | Source carries internal actor/workflow/history/account/storage/debug/secret markers | DTO strict allowlist excludes every unapproved field; FAQ public ID only under approved exposure; error doesn't reveal private source |
| AT14-10 | Script/event/style/embedded HTML, obfuscated/unsafe/protocol-relative URL and private media references | Reject or safely neutralize per approved sanitizer/link/media policy; no unsanitized insertion, text escaped, legitimate formatting preserved |
| AT14-11 | Stable collection fixtures, paging boundaries/duplicate order, malformed cursor/limit/filter/unknown parameters | Approved deterministic bounded eligible pagination only; no hidden totals/cursor/private field leak, no accidental arbitrary query/model |
| AT14-12 | Empty eligible set, missing/non-public key, unavailable canonical source, unexpected exception | Approved 200[]/404/503/500 proposal or selected alternative, safe messages; no seed fallback or disclosure; no unexecuted protocol pass |
| AT14-13 | Authenticated employee same session after one module revoked, other still assigned | Subsequent removed-scope management denied without re-login, other valid assignments remain, Administrator scope preserved; writer/assignment tests separately authorized |
| AT14-14 | Anonymous/public request attempts write/admin/credentialed-private expansion | No mutation/private data/User Management; separate public routes cannot dynamic-select admin collections |
| AT14-15 | Publish/edit/unpublish with cache/header/conditional/replica behavior | Initial proposed no-store honored if approved; any later conditional response checks current eligibility, no stale304/withdrawal exposure |
| AT14-16 | Restore static consumer during rollback after records withdrawn/private | Safe eligibility reconciled or fail-closed; no stale public snapshot re-exposure, other modules unaffected |
| AT14-17 | Module-specific cutover public paths/detail links/anchors/FAQ/prose/checklists plus admin DTO | Accepted data/parity/link/render/fallback semantics maintained, no silent copy/UI/schema break; authorized staging evidence required |
| AT14-18 | Invalid role/module/body/public query plus oversized source/nested data and rate abuse | Approved bounded validation/size/throttle behavior, safe Retry-After/error, no silent truncation or unauthorized grant |
| AT14-19 | Synthetic credential/token/session/private path markers and failing lifecycle/audit action | No secrets/unsafe reflection/false server actor/partial success; approved audit and protected failure behavior verified later |

Tests involving writes/assignment changes are acceptance requirements for separately authorized writer/source integration or fixtures, not permission to implement those capabilities in the read-first slice. Actual acceptance needs executable source/contract/security/test infrastructure and independent implementation review; specification checks are not application test passes.

## K. Decision register and phase exit

All T14 entries await approval; owner-confirmed decisions in Section A and D-213-01–04 remain confirmed, not reopened. A concrete proposal is not a completed governance choice.

**Per-module prerequisite slicing:** dependencies below apply only to the specifically authorized module and environment. A Berita slice needs T14-01 and its applicable parts of T14-05–13/15; a Layanan slice needs T14-02, not Berita lifecycle T14-01. FAQ and Halaman use T14-03 and T14-04 respectively. Shared transport/security decisions may be approved with module-specific scope; unresolved unrelated modules remain disabled, not blockers for an otherwise closed incremental slice. Migration/cutover needs the relevant T14-14 package. This is a dependency rule, not authorization or selection of a first rollout module.

| ID / topic | Current status / evidence | Recommended decision or exact choice needed | Required approver / dependencies / blocks implementation? |
|---|---|---|---|
| T14-01 Berita lifecycle | **RECOMMENDED — PENDING APPROVAL**, source Beralur/old timestamp/arsip | Section C1 candidate mapping, explicit retained-unpublish/archive/intent validation, no scheduling/timestamp eligibility shortcut | Owner + technical/data/security review; T14-05 and Berita source acceptance; yes affected filter/state integration |
| T14-02 Layanan parent/nesting | **RECOMMENDED — PENDING APPROVAL**, source aggregate | C2 parent outer eligibility and approved child field/item classification, no new child module/grant | Owner + service data/security review; Layanan-specific T14-05/06 and source acceptance, not T14-01; yes service/nested projections |
| T14-03 FAQ lifecycle/display | **PENDING — OWNER / GOVERNANCE DECISION**, D-213-03/source toggle | Approve explicit lifecycle/intent encoding and optional display adoption; conservative all-public suppression for false/invalid if retained is only recommendation | Owner + technical review; FAQ-specific T14-05/source mapping; yes FAQ read/migration; toggle not sole lifecycle |
| T14-04 Halaman lifecycle/pages | **PENDING — OWNER / GOVERNANCE DECISION**, D-213-04/type lacks state | Included accepted pages/explicit current visibility capability and safe body, not automatic static-public | Owner + content/security review; Halaman-specific T14-05/07/consumer map; yes page reads |
| T14-05 Explicit public intent | **PENDING — OWNER / GOVERNANCE DECISION**, required dual predicate | Approve representation/attestation/current validation across all four, missing/contradictory fail closed; no new flag mandated | Owner and appropriate data/security authority;01–04; yes all visibility |
| T14-06 DTO/identifier/null metadata | **RECOMMENDED — PENDING APPROVAL**, source fields/types | Section D list/detail packet, FAQ ID versus list-only alternative, exact limits/null/allowlists and feature/date exposure | Owner/API/data/security review;05/07/08/10; yes public serializers |
| T14-07 HTML/plain/format security | **RECOMMENDED — PENDING APPROVAL**, editor not sanitizer | Section E tag/attribute/text rules or accepted safe alternative; exact engine/write/read/render policy and FAQ links parity | Owner format + designated security mandate;06/08/consumer acceptance; yes rich content release |
| T14-08 URLs/media/nested legacy | **RECOMMENDED — PENDING APPROVAL**, source URLs/data/strings | Approve protocols/hosts/storage/public-private/unpublish/null/reject/safe external-link policy and child validation | Owner/data/security/infrastructure mandate;05/06/07; yes affected media/links |
| T14-09 Routes/methods/success DTO | **RECOMMENDED — PENDING APPROVAL**, only health verified | Adopt F1/F2 separate public routes and bare arrays/details/no version, or explicit alternative; keep admin contracts unchanged | Owner/API reviewers; selected module's T14-01, 02, 03 or 04 plus applicable T14-05–08/10; yes selected endpoint implementation, unrelated modules need not close first |
| T14-10 IDs/slugs/cursor/order/paging/filters | **RECOMMENDED — PENDING APPROVAL**, snapshot-only uniqueness/urutan | Approve stable lookup/uniqueness/case/rename/legacy anchors, cursor/Link/page20 max100 review proposal, eligible order/tie-breaker and parameters | Owner/API/data reviewers;06/09/consumer map; yes queries/detail integration |
| T14-11 Errors/empty/absence | **RECOMMENDED — PENDING APPROVAL**, admin conventions not public | Adopt F2 proposed codes/message/body distinction or accepted alternative, approved wording and no reflected/private details | Owner/API/security review;09/10; yes public error/empty behavior |
| T14-12 Cache/freshness/size/rates | **RECOMMENDED — PENDING APPROVAL**, no content cache strategy verified | F3/F4 no-store first slice, proposed 1 MiB response review bound, actual data/query/rate limits, consistency/failure/caching acceptance | Owner/security/operations authority; canonical source, T14-11/13 and approved DB/read environment; yes safe bounded current-public read |
| T14-13 CORS/origins/environment | **BLOCKED — EXTERNAL DEPENDENCY**, topology unverified | Actual approved public-origin/no-credentials/read headers and separate protected-CSRF/cookie mapping, verify hosts/TLS/proxies | Actual domain/infrastructure/security mandate **NOT IDENTIFIED — OWNER CONFIRMATION REQUIRED** where unknown;09/12; yes environment integration/production, not all neutral design |
| T14-14 Audit/migration/cutover | **PENDING — OWNER / GOVERNANCE DECISION**, incrementalmethod confirmed | Event requirements/schema/retention/failure policy, per-module accepted-data/consumer package/order/rollback/operators and monitoring | Owner + designated security/data/operations;all relevant packet/model/test evidence; yes operational cutover, not approved from app role |
| T14-15 DB/test resources and execution scope | **BLOCKED — EXTERNAL DEPENDENCY**, no verified access/test DB | Approved private grants/target/TLS/isolated tests/schema/recovery, then new explicit bounded implementation instruction after adopted packet | Appropriate DB/infrastructure/security mandates and user task; no credentials requested; yes DB/API work and test execution |

Approval packet must record actual signatory/capacity, selected option/version/environment, dependencies, rationale/risk/accepted tests/limits/review triggers without secrets. Security/infrastructure/governance functions are not new application roles. No finalized third role, database enum/column or endpoint exists by this document. No rollout order/date, sanitizer library, credential, domain or risk acceptance selected by implication.

**Phase exit:** specification drafted and documentation validation/review/publication completed only when command results confirm it. Prior governance choices remain confirmed; recommended lifecycle/DTO/transport/rendering controls are **not yet approved**. **Implementation ready: NO**, applicable module-scoped T14 decisions/access/test/contract/source prerequisites and new explicit user authorization required. Next action is owner/API/security approval of the selected slice's packet and explicit exclusions (including its lifecycle, DTO/rendering, routes/errors/paging and current-public controls), plus bounded DB/test/environment evidence. FAQ/Halaman mapping is required for their slices, not an arbitrary blocker for another module. No rollout order selected and no subsequent phase or application code begins here.

### Single-file publication boundary

Only `backend/docs/PHASE-2.14-PUBLIC-READ-CONTRACT-FINALIZATION-AND-LIFECYCLE-MAPPING.md` is created. Check full source/governance/status consistency, references/secret-free content/newline/whitespace, `git diff --check` and original tracked/inventory fingerprints excluding this new path. Re-audit branch/upstream/remote/status before staging, stage literal target only, inspect full staged diff/`git diff --cached --check`; commit `docs(pintu): finalize public-read contract and lifecycle mapping`, normal push to `origin laravel-development`, verify commit scope/parent/upstream/remote and all 385 earlier paths intact. Stop on uncertainty/conflict/staging/divergence/hook/permission/isolation failure; no bulk staging, reset/clean/restore/discard/amend/force/history rewrite or prior artifact inclusion.

No application source/tests/routes/schema/migrations/DTO/policies/auth/MFA/assignments/HTML/UI/copy/dependency/config/env/DB change, SQL/connection/app tests/production probe/deployment or migration/cutover. Git documentation push is not external release authorization. Report actual validation/commit/push and preserved changes, never a guessed hash/test pass or clean-working-tree claim.
