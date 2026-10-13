# PINTU — Content Authority and Publication Semantics

**Phase:** 2.8. **Date:** 2026-10-12. **Status:** owner-confirmed policy and qualified technical specification; documentation only, no Phase 3 implementation authorization.

## A. Purpose, scope and verified baseline

Formalize module-based content authority, immediate public changes to published content, retained editable records after unpublishing and direct republishing. Exactly two application roles remain **`Administrator`** and **`Pegawai UPTD`**. Owner is **`PINTU Project Owner (Application Administrator)`**; no legal name, official title, additional role, sub-role or alternative authorization identity is introduced.

This document is authoritative for the exact new owner-confirmed decisions, not an approved module catalog, API, schema, persistence model, cache/CDN design or operational implementation. Preceding documents remain unchanged. Earlier revision/ownership/approval suggestions cannot reverse the confirmed immediate visibility or require Administrator approval for authorized Pegawai actions. Revision history/audit/recovery may be considered separately without becoming a blocking approval workflow.

### Repository preflight and preservation

Branch `laravel-development`; local/remote preflight tip `87e073d78183ab9b2b4fc56dd0d9d0e862e91060`. No staged changes/divergence; actual outstanding paths **385 = 6 tracked unstaged modifications + 379 untracked files**, matching Phase 2.7. New document path did not exist. Existing tracked binary diff fingerprint `cac31712e8536b629d9fec47bb290ddea1f9c29e`; existing-file inventory fingerprint `9600a249488e41d0fb5bddd47a6d5e93be4e5b06` in Git output order. Final comparison excludes only this new document. HTML/handover/backend/skills/scripts/tooling/earlier documents stay untouched.

| Evidence ID | Actual document reviewed | Relevant evidence / publication |
|---|---|---|
| EV-01 | `backend/docs/PHASE-1.1-SECURITY-AND-INTEGRATION-DISCOVERY.md` | Historical prototype collections/contracts/workflow/static health results; local/untracked, not actual authorization module catalog |
| EV-02 | `backend/docs/PHASE-2-ARCHITECTURE-AND-API-DESIGN.md` | Proposed data model/revision/approval design, confirmed local identity, unapproved security/infrastructure; local/untracked |
| EV-03 | `backend/docs/PHASE-3-IDENTITY-AND-PERSISTENCE-FOUNDATION-SCOPE.md` | Candidate packages/states/gates; local/untracked, no application execution authorization |
| EV-04 | `backend/docs/PHASE-3-READINESS-GATE.md` | Tracked/remote; conditional preparation versus unresolved auth/admin/DB/deployment prerequisites |
| EV-05 | `backend/docs/PHASE-2.2-SECURITY-AND-IDENTITY-LIFECYCLE-SPECIFICATION.md` | Tracked/remote; proposed server security/audit controls and planned tests, no implemented auth/security-policy closure |
| EV-06 | `backend/docs/PHASE-2.3-WORKING-TREE-AND-PHASE-3-READINESS.md` | Tracked/remote;385-path audit and publication limitations |
| EV-07 | `backend/docs/PHASE-2.4-GOVERNANCE-DECISIONS-AND-PHASE-3-AUTHORIZATION.md` | Tracked/remote; GOV-01-16 responsibilities/dependencies, no blanket authorization |
| EV-08 | `backend/docs/PHASE-2.5-GOVERNANCE-OWNERSHIP-AND-READINESS-UPDATE.md` | Tracked/remote; owner/two-role and broad Administrator/Pegawai boundaries |
| EV-09 | `backend/docs/PHASE-2.6-CONTENT-PERMISSION-AND-ACCOUNT-LIFECYCLE-SPECIFICATION.md` | Tracked/remote; CP decisions, Administrator-only delete/User Management, unresolved module/operation details |
| EV-10 | `backend/docs/PHASE-2.7-CONTENT-AND-ACCOUNT-LIFECYCLE-DECISIONS.md` | Tracked/remote; D27/R27 confirmed edit/unpublish rights, formerly open cross-employee/immediate/withdrawal mechanics, account activation/credential direction |
| EV-11 | Current explicit Phase 2.8 owner instruction | Confirmed module authority/cross-employee/immediate edit/retained unpublish/direct republish statements only |

All ten preceding paths exist and their actual contents were reviewed directly or through read-only architectural analysis. The three local/untracked reports and Laravel foundation are not included in this commit; citations are local repository evidence, not promises about clean remote checkout. No missing/unreadable source is claimed reviewed. Historical tests are not fresh execution; no DB connection/runtime or infrastructure test occurred.

## B. Confirmed decision register

`CONFIRMED` denotes explicit owner direction; `UNDECIDED`/`SCOPE VERIFICATION REQUIRED` denote implementation questions, not reopened settled policy; `PROPOSED` denotes technical consideration requiring review. These statuses do not claim implemented functionality.

| ID | Decision statement | Status / source | Implication and limit |
|---|---|---|---|
| D28-01 | Exactly Administrator/Pegawai UPTD; owner is application Administrator | **CONFIRMED**, EV-08-11 | No third role, role alias or inferred number of accounts/security/infrastructure mandate |
| D28-02 | Pegawai content authority is module-based; each employee manages authorized content within assigned modules, not outside | **CONFIRMED**, EV-11 | Module assignment is the key protected authorization boundary; actual catalog/assignment/revocation design still unresolved |
| D28-03 | Within assigned module, authorized own and other employees' content may be managed; authorship alone must not prohibit otherwise authorized action | **CONFIRMED**, EV-11 | Cross-employee management not blanket all-module access; creator metadata remains useful audit information, not exclusive authority gate |
| D28-04 | Edits to authorized published content take effect immediately on public portal after successful operation | **CONFIRMED**, EV-11 | No mandatory Administrator approval or delayed draft/revision approval workflow for ordinary authorized edits; optional history/audit does not delay public effect |
| D28-05 | Unpublish removes public visibility while retaining the content record | **CONFIRMED**, EV-11 | Not deletion/permanent removal/archival; no assumed internal state enum or schema migration |
| D28-06 | Unpublished retained content remains editable | **CONFIRMED**, EV-11 | Same role/module/action checks apply; no implicit history erasure, record recreation or attachment deletion |
| D28-07 | Authorized Pegawai directly republishes retained content within assigned modules, without mandatory Administrator approval | **CONFIRMED**, EV-11 | Restores public visibility under same authority; no mandatory new account role or separate publisher identity |
| D28-08 | Pegawai may create/edit/publish/unpublish/republish authorized content but may not delete | **CONFIRMED**, EV-09-11 | Delete restrictions apply to direct/batch/destructive side-effect routes; granted unpublish remains distinct allowed operation |
| D28-09 | Administrator full approved application access; only Administrator may delete content and perform User Management/account roles/status | **CONFIRMED**, EV-08-11 | No Pegawai User Management privilege; full app access remains subject to status/auth/security controls and supported features |
| D28-10 | Server authority required for identity, active status, effective role/module/action; client state not a boundary | **CONFIRMED enforcement requirement**, EV-09-11 | Proposed implementation must deny unauthorized requests; no middleware/assignment source/API exists by implication |
| D28-11 | Public self-registration remains unauthorized | **CONFIRMED**, EV-09-11 | Module assignments cannot create accounts/roles or grant administrative identity |

Scoped delta to EV-10: R27-01's **module-based/cross-employee policy direction** resolved; actual module definitions/assignments still open. R27-03's **immediate public effect** resolved; implementation delivery/consistency/history details still to verify. R27-04's **logical unpublish/retain/edit/republish** resolved; not an approved `draf`/`arsip`/new enum mapping. All prior account/credential/MFA/infrastructure questions remain unless separately confirmed; no earlier file rewritten.

## C. Updated permission matrix

The two role columns express confirmed permissions within approved/supported scope, not existing endpoints. Protected administrative viewing is distinct from ordinary public portal reading; denying unassigned-module management does not introduce authentication gates for already public published pages. No module list, organizational-unit mapping or particular assignment schema is supplied by this matrix.

| Operation | Administrator | Pegawai UPTD | Qualification |
|---|---|---|---|
| View/manage authorized content within assigned module | **ALLOW — CONFIRMED**, full approved scope | **ALLOW — CONFIRMED**, assigned module and authorized content | Necessary management access includes own/other employee targets; any exceptional sensitive fields/content-type eligibility must be evidenced, not invented |
| Protected content/admin access outside assigned modules | **ALLOW — CONFIRMED**, full approved application scope | **DENY — CONFIRMED** | Does not change ordinary anonymous/public published-content reads; no implicit module assignment from public visibility |
| Create content in assigned module | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED** | Catalog and trusted assignment source unresolved |
| Edit own draft content in assigned module | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED** | No extra ownership-only grant mechanism |
| Edit other employees' draft content in assigned module | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, otherwise authorized content | Authorship alone not denial; no permission outside module |
| Edit published content in assigned module | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, immediate public effect | No Administrator/draft approval gate; validation/authority still required |
| Publish authorized content | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, directly in assigned module | Module/content eligibility details require verification; no mandatory Administrator approval |
| Unpublish authorized content | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, assigned module | Record retained/editable; no delete or implicit archive |
| Edit retained unpublished content | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, assigned module | Remains non-public unless republished; editing alone does not grant publishing outside module |
| Republish retained unpublished content | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, directly in assigned module | Public visibility restored; no Administrator approval/recreation prerequisite |
| Delete content | **ALLOW — CONFIRMED** | **DENY — CONFIRMED** | Physical/soft delete/recovery/retention behavior not selected here |
| View user accounts through User Management | **ALLOW — CONFIRMED** | **DENY — CONFIRMED** | Self-profile needs are distinct unresolved contract, not account-directory permission |
| Create accounts | **ALLOW — CONFIRMED** | **DENY — CONFIRMED** | Earlier account activation/credential requirements unchanged |
| Assign/change application roles | **ALLOW — CONFIRMED**, only two approved roles | **DENY — CONFIRMED** | No self-escalation through module/client state |
| Activate/suspend/reactivate/deactivate/status-manage accounts | **ALLOW — CONFIRMED**, under approved lifecycle | **DENY — CONFIRMED** | Complete technical state graph and security consequences still unresolved |
| Module-assignment administration mechanism/workflow | **SCOPE VERIFICATION REQUIRED** for the precise Administrator workflow | **No module self-grant**; no assignment-administration privilege confirmed | How assignments are created/changed/revoked and audited is a prerequisite, not an assumed endpoint/table or role |
| Separate archival operation, if any | **SCOPE VERIFICATION REQUIRED** | **UNDECIDED / SCOPE VERIFICATION REQUIRED** | Unpublish is explicitly not archival; do not invent an archive feature |

## D. Content lifecycle and logical state semantics

Logical labels **published**, **not-yet-published/draft**, **unpublished-retained** describe visibility/record behavior for this table, not new database fields/enums, migrations or a complete business state graph. Prototype statuses are not automatically adopted; unpublish is not assumed to set `draf` or `arsip`. Creating content is not automatically publishing it; any create-and-publish default remains a separately verified behavior, not chosen here.

| Action / logical transition | Public visibility | Record persistence / editability | Publication/deletion authority | Confirmation versus open mechanics |
|---|---|---|---|---|
| Create content: no record -> stored content | Initial public state/default **SCOPE VERIFICATION REQUIRED**; creation alone not claimed to publish | Created record stored, editable within authority | Both roles in authorized scope; direct publishing separately allowed | Create authority confirmed; initial technical mapping/defaults unresolved |
| Edit draft/not-yet-published content | Remains non-public until a publish action | Existing record editable and retained | Both roles within scope, including peers in assigned module | Edit permission confirmed; validation/concurrency/internal representation unresolved |
| Publish stored authorized content -> published | Publicly visible after successful publication | Record remains stored/editable | Administrator/full scope or Pegawai/assigned module directly | Public behavior confirmed; public read/integration/cache mechanics not implemented |
| Edit published -> published with updated public content | **Updated content immediately publicly visible after success** | Record retained/editable; optional history separate | Both roles authorized; Pegawai no Administrator approval | No draft/revision approval or scheduled delay introduced; version/conflict/cache/recovery design unresolved |
| Unpublish published -> unpublished-retained | **No longer visible on public portal** | **Record stored and editable**, can republish; no record/history/file erasure inferred | Both roles authorized, Pegawai assigned module | Logical outcome confirmed, not deletion/permanent removal/archival; exact internal mapping unresolved |
| Edit unpublished-retained -> unpublished-retained with edits | Remains hidden; editing alone does not republish | Same record remains editable/retained | Both roles authorized, peers allowed within assigned module | No need to recreate record; technical revision/audit/file behavior still to verify |
| Republish unpublished-retained -> published | Public visibility restored after successful republish | Stored record remains editable; no recreation/deletion required | Both roles authorized; direct Pegawai action with same module scope | No Administrator approval; exact consistency/read-path handling unresolved |
| Delete content | Public/record effects depend on separately approved deletion semantics | Physical versus reversible deletion/recovery/attachment/retention **UNDECIDED** | **Administrator only** | Pegawai always denied; do not reuse delete to implement permitted unpublish |

Unpublishing alone must not delete the database record, associated files or history. Their visibility/access/history/retention details require separate design: retaining files does not settle whether a previously public direct file URL must be hidden, retained publicly or served privately. No claim of complete media revocation or erasure is made. Administrator deletion and any future archival operation are separate from this logical unpublish behavior.

## E. Module-based server authorization requirements

These are specification requirements, not implemented middleware/policies/assignment persistence:

- **MA-01:** every protected content action verifies backend identity, eligible active status, effective role, authoritative assigned module scope and requested action. Resolve target record's actual module using trusted server data, not a request's alleged module value alone.
- **MA-02:** Pegawai can manage otherwise authorized own or peer content in assigned modules; authorship/creator ID alone must not deny that right. Creator attribution is retained for audit, not an exclusive ownership gate. No organizational-unit/peer restriction invented to reverse the decision.
- **MA-03:** requests for unassigned modules or denied actions fail server-side without mutation. Content query/list/get/update/publish/unpublish/republish and any batch/alternate route must respect the same scope. Public-read representations are separate from protected module management.
- **MA-04:** trusted backend assignment source required; client role labels/local storage/request parameters cannot grant, expand or restore module assignments. Assignment invalid/missing/ambiguous => fail closed for the protected employee operation, not default-all-modules. No specific table/pivot/JSON/ACL package/API selected.
- **MA-05:** no Pegawai deletion or User Management via direct requests, generic patches, bulk/reset/replace/import, malicious module changes or publication-state flags. A permitted scoped unpublish is not falsely rejected as deletion; unauthorized destructive side effects prohibited.
- **MA-06:** assignment creation/change/revocation control and freshness/concurrency must be specified before dependent code; stale cached client grants cannot establish authority. Exact administrator assignment workflow/operator safeguards and session handling are unresolved, no additional role inferred.
- **MA-07:** permitted and prohibited actions need planned direct-request/target/state/missing-assignment tests; no successful authenticated request is automatically authorized. Administrator full approved application scope remains, with account-status/security controls, not employee module restrictions or external DB/Cloud powers.

Module definitions and assignment audit/workflows are implementation prerequisites. The existence of prototype collections/settings does not authorize treating them one-for-one as content modules, making every collection public content, or inventing the actual catalog in this specification.

## F. Immediate public effects and operational implications

Confirmed contract: an **authorized successful edit of published content is immediately reflected publicly**; successful unpublish hides it while retaining/editability; successful republish restores visibility. Public read paths must respect current publication visibility and must not display unpublished content in public pages/lists/details or API representations. No public endpoint currently implementing this contract is asserted. Already viewed/downloaded/copied client data cannot be remotely erased; this does not permit a public read path to keep serving unpublished content.

No success claim for an authorization failure; no content/version/visibility mutation on denied requests. Content validation/sanitization and version/conflict safeguards may reject an invalid operation, but must not add Administrator approval or a mandatory draft/revision publication gate to successful ordinary authorized edits. Non-blocking history/audit is distinct from delayed release.

Implementation considerations **requiring verification, not selected mechanisms**:

| Consideration | Required analysis / evidence; no invented behavior |
|---|---|
| Public read integration | Identify actual public paths/storage/representation; specify how server-authoritative updates reach existing portal without redesign or static pages falsely claimed live-integrated |
| Cache/CDN, if present | Determine actual caches/output and freshness strategy capable of immediate visibility changes; no TTL, CDN provider, purge call or deployment pipeline assumed. Unresolved mechanism cannot justify reporting success while intentionally retaining stale public content. |
| State/data consistency | Design and validate successful-operation/public visibility boundary, failure/retry/version/competing-write behavior; no transaction/distributed atomic guarantee selected or claimed |
| Revision/history and accidental change recovery | Decide whether/how to record non-blocking history or restore content; do not require approval before ordinary edits take effect. No irreversible revision model or retention period invented. |
| Media/files | Verify validation/storage/references and visibility of retained attachments after withdrawal; unpublish must not silently delete files or assert private file enforcement already exists |
| Audit and delivery failure | Define attributable actor/action/target/server time/outcome and success/error semantics under actual operational failures; no logging implementation or provider architecture assumed |

These requirements describe desired public semantics, not measured propagation performance or current cache guarantees. Implementation/success-boundary design must be verified in a separately authorized task, without inventing a grace period that weakens the confirmed immediate behavior.

## G. Security, audit and data integrity

Server-side permission enforcement and cross-module protection are mandatory design requirements. Unauthorized direct API attempts, forged role/module/target/publication fields and missing assignment must fail securely with no unintended state changes. Public visibility and stored record state must stay logically consistent under approved success/failure/version behavior; details of persistence/concurrency are still open.

Create/edit/publish/unpublish/republish/delete should be attributable through safe server actor, action, target, time, outcome and correlation where supported; protect audit against unauthorized mutation and avoid secrets/passwords/tokens/session/recovery material. Audit schema, mandatory events including assignment changes, read/review access, consistency/retention/minimization and operational ownership remain for approval (Section H). No audit code or cryptographic immutability exists by implication.

Immediate multi-employee edits increase accidental/conflicting change and public-impact risk; recommend reviewing validation/sanitization, conflict detection and recoverability without reintroducing an Administrator approval gate. No particular concurrency header/schema/lock, recovery guarantee or sanitizer is selected. Assignment changes require authoritative handling; token authentication or CORS alone is not module authorization.

Separate unchanged dependencies: local-account source and Administrator-only User Management remain confirmed; active status follows completed Administrator create+activate and initial credential activation/reset direction from Phase2.7, with technical details open. MFA remains undecided; Sanctum SPA remains conditional recommendation; session/CSRF/CORS/cookies, MySQL/isolated test access, actual domains/Cloud configuration and security/operations authority remain unverified or unapproved. No new role or infrastructure approval inferred.

## H. Remaining questions and closure evidence

Owner application responsibility is **PINTU Project Owner (Application Administrator)**. Separate security-risk/domain/DB/Cloud/operations authority still **NOT IDENTIFIED — OWNER CONFIRMATION REQUIRED** where not explicitly evidenced. Module assignment capability/workflow must be approved as administration within the two-role design, not fabricated as another role.

| ID | Remaining question / status | Evidence or decision required | Affected component / may proceed before resolution? |
|---|---|---|---|
| R28-01 | Actual supported content-module catalog, identifiers and mappings; **UNDECIDED** | Owner-approved list/definition and record-module mapping; verify authoritative source, do not promote frontend collections automatically | Content queries/policies/storage/contracts; neutral catalog proposal only, module-dependent code no |
| R28-02 | How assignments are created/changed/revoked, trusted source and invalid/missing cases; **UNDECIDED** | Approved technical assignment lifecycle/validation/freshness/session/concurrency design, fail-closed rules and contract | Assignment storage/API/authorization; no schema/API assumption or implementation |
| R28-03 | Exact Administrator workflows for module assignment, target/process/safeguards; **UNDECIDED / SCOPE VERIFICATION REQUIRED** | Owner specifies permitted admin workflow/controls and which operations administer assignments; no employee self-grant, no new role or current implemented interface | User Management/assignment operations; broad Administrator role known, actual mechanism not authorized by document |
| R28-04 | Audit requirements for assignment changes and content events; **UNDECIDED** | Decide mandatory events, safe attributes, attribution/consistency/read/review/minimization/retention/operations approval | Audit/assignment/content actions; recommendations yes, dependent implementation after policy approval |
| R28-05 | Actual public read/update integration and cache/CDN strategy if applicable; **UNVERIFIED / UNDECIDED** | Inspect actual paths/caches/environment in authorized work; choose/verify immediate-publication/unpublish success-boundary behavior without invented TTL/purge architecture | Public representations/cache/operational failure handling; design only, public integration no |
| R28-06 | Non-blocking history/recovery from accidental publication changes and conflicting edits; **UNDECIDED** | Owner/security/technical review selects requirements, conflict/error/retry/recovery behavior and non-secret acceptance evidence, no approval gate delaying immediate effects | Revision/history/concurrency/restore; design only, no assumed transactions/undo guarantees |
| R28-07 | Content-type validation/publication restrictions and attachments/files; **UNDECIDED details** | Approved supported data rules, sanitization/media handling, retained attachment visibility after unpublish; no invented module restriction defeating confirmed assigned peer authority | Forms/API/storage/public files; field/media-dependent implementation blocked |
| R28-08 | Audit retention/review/sensitive-data/operational responsibilities; **UNDECIDED** | Authorized safe event/read/write/disposition/custodian policy; no legal duration or actor invented | Logs/audit/recovery; no implemented audit from existence of requirements |
| R28-09 | Credential/account lifecycle/session/MFA and security-risk approver; **UNDECIDED / NO-GO** | Close prior R27-05-11 as applicable: exact activation/reset/state/session/assurance process with designated security approval. Do not reopen confirmed active outcome or infer MFA omission. | Authentication/accounts/security; only neutral preparation now |
| R28-10 | MySQL target/access/test isolation/connectivity/schema/recovery; **BLOCKED / UNVERIFIED** | Approved custodian privately provides access/grants/TLS, verified isolated test DB and separately authorized safe check, approved models/migration/restore | Persistence/MySQL tests; no SQL/connectivity action here |
| R28-11 | Frontend/API origins/HTTPS/CSRF/CORS/cookies/Cloud environment and ownership; **BLOCKED / UNVERIFIED** | Verified approved environment/security mappings/resources/mandates, integration/test/release authorization; platform docs not actual deployment proof | Transport/deployment/production canary; no guessed domain/resources/provider |
| R28-12 | Bounded implementation authorization; **NOT AUTHORIZED** | New explicit task names closed dependency IDs, files/packages/test environment/exclusions/review and stop conditions | All application/config/DB/test/deploy actions; current phase permits only document and isolated commit/push |

Scoped predecessor updates: policy direction R27-01 (assigned modules and peer content), R27-03 (immediate edit effect) and logical R27-04 (hide/retain/edit/direct republish, not archive/delete) are settled here. Remaining technical/catalog/validation questions above do not reopen those decisions. Earlier documents unchanged; no blanket scope approval or hidden alternate role.

## I. Planned acceptance criteria

**Every scenario is `PLANNED — NOT IMPLEMENTED OR EXECUTED`.** Synthetic approved fixtures identify assigned/unassigned test modules symbolically, not an invented actual catalog. Future routes/contracts/DB targets must be authorized and prerequisites closed; no HTTP/test/database operation occurs in this phase.

| ID | Setup / planned action | Expected result and policy trace |
|---|---|---|
| AC28-01 | Eligible Pegawai assigned an approved test module creates valid content there | Allowed within module/action scope; server actor/target/module authoritative; D28-02/08/10 |
| AC28-02 | Same Pegawai edits peer-created draft or other authorized content in assigned module | Allowed; authorship alone not denial, no new reviewer/owner role; D28-03 |
| AC28-03 | Pegawai requests protected views/mutations in unassigned module | Denied safely/no mutation or protected data disclosure; ordinary public content reading not a management grant; D28-02 |
| AC28-04 | Pegawai edits authorized published content, then public read follows successful operation | Updated content immediately visible publicly; no Administrator/revision approval or intentional delayed visibility; D28-04; actual integration/cache evidence needed |
| AC28-05 | Authorized Pegawai unpublishes published record | Public reads no longer display it; same record retained, no history/file deletion or archival; D28-05/06 |
| AC28-06 | Pegawai edits retained unpublished record in assigned module | Allowed, record retained; content remains non-public until republish; D28-06 |
| AC28-07 | Pegawai directly republishes retained record in assigned module | Visibility restored, no Administrator approval or recreation; same module checks; D28-07 |
| AC28-08 | Pegawai attempts delete through direct/batch/patch/reset/replace paths | Denied/no destructive side effect; granted unpublish still allowed and not mistaken for delete; D28-08 |
| AC28-09 | Pegawai attempts account list/create/role/status management | Denied/no account or role/status change; D28-09/11 |
| AC28-10 | Forged browser label, request module/record ID or stale local assignment attempts protected access | Cannot expand module scope; trusted backend resolves actual target and current assignment/action/status; D28-10/MA-01-04 |
| AC28-11 | Missing/invalid/ambiguous assignment for employee protected operation | Fail closed, no default-all-module access, no mutation; MA-04; no unintended restrictions on Administrator full approved app scope |
| AC28-12 | Public list/detail/page/read path asks for unpublished-retained content | Content not displayed by public representation; test applicable public paths once identified, no existing endpoint assumed; D28-05 |
| AC28-13 | Eligible Administrator manages supported content/modules and authorized User Management | Retains full application capabilities including deletion; only two roles, no employee self-grant/full admin; D28-01/09 |
| AC28-14 | Synthetic secret markers in publication/account context and audit capture | Safe actor/action/target/time/outcome/correlation evidence; no password/token/session/recovery secrets or paths/stacks in logs; Section G, policy prerequisites |
| AC28-15 | Assignment revoked/changed and record allegedly relocated by client | Current trusted module policy applies; stale grants/forged module cannot authorize operation; no claimed session/revoke mechanism before R28-02/03 resolves |
| AC28-16 | Unauthorized/inactive actor attempts publish/edit/unpublish/republish, then state/public read inspected | Safe denial, no record/visibility/permission mutation or false success; approved security/consistency failure behavior verified later |

Documentation acceptance: exact confirmed statements/peer authority/immediate edit/retained editable unpublish/direct republish preserved; no new roles/modules/internal enums/approval gate, no invented cache/CDN/schema or implemented control claim, previous files unchanged, secret-free paths/content and isolated commit verified. Future application acceptance additionally requires real positive/negative/direct-request/public-read evidence after dependencies and scope authorization; these planned tests are not passes.

## J. Phase 3 readiness impact

| Area | Design update | Implementation readiness |
|---|---|---|
| Module-based authorization | **CONFIRMED policy:** assigned module authority including authorized peer records; no unassigned protected access | **PARTIAL specification / NO-GO:** catalog/assignments/workflow/contracts/freshness unresolved |
| Publication lifecycle | **CONFIRMED logical behavior:** immediate successful published edits, retained editable unpublish, direct republish | **PARTIAL technical mapping / NO-GO:** read integration/visibility consistency/media/history/failure design unverified |
| Public visibility | **CONFIRMED requirement:** published current content displayed; unpublished content not displayed | **NO-GO** until actual public paths/delivery/consistency verify the contract; no measured cache/CDN guarantee |
| Content deletion | **CONFIRMED Administrator-only**, employee denied; unpublish distinct | Deletion semantics/recovery/retention implementation still open; no APIs/code |
| User Management/two-role boundary | Prior **CONFIRMED** boundary unchanged | Account/auth/persistence safeguards and explicit scope still required; no new roles/account operations |
| Authentication/MFA/session security | No new approval; Sanctum still conditional recommendation and MFA undecided | **NO-GO**, security/authority/configuration prerequisites unchanged |
| MySQL/test isolation | No new connection/model/access evidence | **BLOCKED**, no DB work authorized |
| Origins/Cloud/deployment | No actual domain/infrastructure approval | **BLOCKED**, no deployment/canary/config change |

Design completeness is not executable readiness. Documentation GO only for this task; independent non-executable preparation can be separately scoped. No blanket Phase3 implementation authorization, no operational sessions based on an incomplete prototype and no cleanup/publication of older local artifacts. A later explicitly authorized local slice may close only applicable prerequisites without requiring unrelated production Cloud resources, but cannot bypass security/DB/module-policy gates.

## K. Minimum recommended next action

1. **Owner approves actual content-module catalog and assignment administration** (R28-01-03): identify exact modules/mappings, Administrator assign/change/revoke workflow and trusted-source/freshness requirements within two roles. Do not reopen peer-content authority, immediate edit semantics or direct unpublish/republish; no credentials requested.
2. **Specify the minimal implementation contract** (R28-04-08): actual public-read/update boundary satisfying immediate effects, retained record/media semantics, content validation, non-blocking audit/history/recovery requirements and verified cache/concurrency/failure considerations if applicable. Do not invent another review-approval stage or produce phases only to repeat decisions.
3. **Close relevant existing identity/security/DB/environment prerequisites** with separately identified authorized custodians (R28-09-11), then explicitly authorize a bounded slice (R28-12) with files/test target/dependency evidence/exclusions. No new role, provider or policy chosen by this specification.

### Verification and publication boundary

Only `backend/docs/PHASE-2.8-CONTENT-AUTHORITY-AND-PUBLICATION-SEMANTICS.md` is created. Check full document/owner/source consistency, referenced paths, secret-free content, whitespace/newline, `git diff --check`, exact existing fingerprints/status and independent read-only review. Stage literal path only, inspect full staged diff and `git diff --cached --check`, commit `docs(pintu): define content authority and publication semantics` and normal push to `origin/laravel-development`. Verify single-file commit/parent/remote equality and all 385 existing paths unchanged. Stop for wrong branch/divergence/staging/isolation/hook/access failure; no add-all, cleanup/reset/restore/discard/amend/force or prior work staging.

No application PHP/TS/JS/HTML/CSS/UI/copy/dependency/runtime config/env/DB changes; no auth/MFA/authorization/module assignment/content workflow/migration/schema/cache invalidation/audit implementation, runtime tests/HTTP/SQL/connection or deployment. Publication does not approve external push-triggered automation. Commit/verification outcomes reported after actual commands; Phase3 implementation remains unstarted.
