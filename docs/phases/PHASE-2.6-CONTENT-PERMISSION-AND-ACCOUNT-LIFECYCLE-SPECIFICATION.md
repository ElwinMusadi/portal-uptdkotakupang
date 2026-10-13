# PINTU — Content Permission and Account Lifecycle Specification

**Phase:** 2.6. **Date:** 2026-10-12. **Status:** confirmed owner decisions plus proposed controls/unresolved policy; documentation only, no application implementation authorization.

## A. Purpose, scope and source precedence

Formalize the current owner's initial content/publication and User Management decisions without creating application code, roles, accounts, database states or endpoints. Exactly two application roles are authorized: **`Administrator`** and **`Pegawai UPTD`**. Owner is **`PINTU Project Owner (Application Administrator)`**; no legal name or official job title inferred. Role count is not account count or a requirement for exactly one Administrator.

**Immediate baseline:** Phase 2.5 ownership/two-role boundaries. **Current update:** Pegawai UPTD may create, edit and directly publish authorized content, but may not delete content or use User Management. Only Administrator creates accounts, assigns/changes roles and manages account status. This supersedes earlier unapproved recommendations **only where they would require Administrator approval for each Pegawai publication or imply additional roles**. It does not approve unrestricted content scope, a concrete lifecycle enum, every prototype transition or immediate in-place edits of already published records.

The earlier client workflow `draf`/`diperiksa`/`persetujuan`/`terbit` is source behavior, not a mandatory institutional approval process. Do not require Pegawai UPTD to pass an Administrator approval step for publication it is authorized to perform. Optional author review, audit, validated input and approved content-eligibility controls are distinct from imposing that prohibited mandatory approval. Published-edit/revision policy remains undecided; direct publication does not automatically reject revision history or all workflow metadata.

### Repository and evidence preflight

Branch `laravel-development`; local/remote tip `94ff739e4e7ec44cf3bb73a0385a86112ce6a1d6`; no staged changes or divergence. Raw Git status: **385 outstanding paths = 6 tracked unstaged + 379 untracked**. Same earlier working-tree changes as Phase 2.5 preserved. New deliverable path did not exist. Tracked binary diff fingerprint `cac31712e8536b629d9fec47bb290ddea1f9c29e`; existing-file inventory fingerprint `b88ebb9942a71147cf830702f443531d92127527` in Git output order. Final comparison excludes exactly this new document.

| Evidence | Current document reviewed and relevant sections | Publication / interpretation |
|---|---|---|
| EV-01 | `backend/docs/PHASE-1.1-SECURITY-AND-INTEGRATION-DISCOVERY.md`, contracts/workflow/browser identity and verification | Local/untracked; historical verified source/tests, not production authorization |
| EV-02 | `backend/docs/PHASE-2-ARCHITECTURE-AND-API-DESIGN.md`, Sections4/5/7/9/11 | Local/untracked; prototype workflow/review/revision recommendations unapproved; D15 local identity confirmed, D16 MFA open |
| EV-03 | `backend/docs/PHASE-3-IDENTITY-AND-PERSISTENCE-FOUNDATION-SCOPE.md`, Packages A-D/gates | Local/untracked; candidate lifecycle/specification, no application execution authorization |
| EV-04 | `backend/docs/PHASE-3-READINESS-GATE.md`, Gates A-E | Tracked/remote; dependency-specific gates and permitted preparation |
| EV-05 | `backend/docs/PHASE-2.2-SECURITY-AND-IDENTITY-LIFECYCLE-SPECIFICATION.md`, L1-L11, BOOT/ADM/MFA and planned tests | Tracked/remote; conceptual states/controls and unresolved policy, not an approved invitation/request/recovery system |
| EV-06 | `backend/docs/PHASE-2.3-WORKING-TREE-AND-PHASE-3-READINESS.md` | Tracked/remote;385-path audit and separate publication boundary |
| EV-07 | `backend/docs/PHASE-2.4-GOVERNANCE-DECISIONS-AND-PHASE-3-AUTHORIZATION.md`, GOV register/matrix | Tracked/remote; earlier governance status, partial updates in later documents |
| EV-08 | `backend/docs/PHASE-2.5-GOVERNANCE-OWNERSHIP-AND-READINESS-UPDATE.md`, OWN-01-04 and scoped GOV deltas | Tracked/remote; immediate ownership/two-role baseline, granular content/lifecycle decisions were open |
| EV-09 | Current explicit project-owner instruction, Phase2.6 | Confirmed permissions/publication/Administrator exclusivity only; no security/infrastructure/code approval |

All eight preceding files exist and their actual contents were reviewed directly or by read-only architecture review. Earlier three local/untracked documents and backend artifacts remain excluded from this commit; citations are local repository evidence, not promises of their presence in a clean remote checkout. No preceding document is rewritten. Historical tests are not new runs in this phase.

## B. Confirmed decision table

`CONFIRMED` applies only to owner statements. `PROPOSED` denotes a technical safeguard/design still subject to implementation approval; `UNDECIDED`/`SCOPE VERIFICATION REQUIRED` denotes a gap, not permission. Confirmed denial is also a confirmed policy decision.

| ID | Decision statement | Status / source | Implication and boundary |
|---|---|---|---|
| CP-01 | Exactly two application roles: Administrator and Pegawai UPTD; no additional role/sub-role/permission group represented as a role | **CONFIRMED**, EV-08/09 | Any future extra role requires explicit scope approval; workflow functions are not extra identities |
| CP-02 | Project owner is application Administrator; use PINTU Project Owner (Application Administrator) | **CONFIRMED**, EV-08/09 | Application ownership only; no legal identity, security/MFA risk or infrastructure mandate inferred |
| CP-03 | Administrator full application access including account creation, role assignment, account details/status and content management/publication/deletion | **CONFIRMED**, EV-09 | Active/status/auth/security controls still apply; exact lifecycle transitions/retention and technical channels remain open |
| CP-04 | Pegawai UPTD may create and edit authorized portal content | **CONFIRMED**, EV-09 | Module/object/ownership/field and published-edit scope not yet established |
| CP-05 | Pegawai UPTD may directly publish content it is authorized to manage; no mandatory Administrator approval for each publication | **CONFIRMED**, EV-09 | No blanket authority to publish every module/object; eligibility and concurrent-edit safeguards require specification |
| CP-06 | Only Administrator may delete content; Pegawai UPTD may not delete it | **CONFIRMED**, EV-09 | Includes denial of indirect deletion via bulk/reset/import/patch flags. Unpublish/archive is not automatically deletion or allowed; its policy is separate. |
| CP-07 | Only Administrator creates accounts, assigns/changes roles, updates account status and deactivates accounts | **CONFIRMED**, EV-09 | Pegawai UPTD denied User Management, activation/suspension/reactivation/deactivation and full administrative privileges; procedures still to be defined |
| CP-08 | Public self-registration not authorized | **CONFIRMED**, EV-09 and established local-account direction | No public signup/setup workaround. No invitation/account-request/email verification system invented in its place |
| CP-09 | Backend enforces authenticated identity, eligible active status and effective permissions; browser labels/state are not authoritative | **CONFIRMED enforcement direction**, EV-08/09 | Required future server checks and negative tests, not implemented middleware/policies |

## C. Permission matrix

`ALLOW — CONFIRMED` means owner grants the operation within authorized application scope; not current endpoint functionality. `DENY — CONFIRMED` means explicit prohibition. The matrix's only role columns are the two authorized roles; operation categories are not new roles/groups. A scope decision must name modules/objects/actions without turning capabilities into alternative authorization identities.

| Operation | Administrator | Pegawai UPTD | Scope/policy note |
|---|---|---|---|
| View content | **ALLOW — CONFIRMED**, full app content scope | **SCOPE VERIFICATION REQUIRED** | Reading target content is necessary to authorized edit/publish, but global drafts/private/all-module visibility is not explicitly decided. Do not infer a general read-all grant or block necessary target access once scope approved. |
| Create content | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, authorized scope | Specific modules, ownership and field limits remain unresolved |
| Edit content | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, authorized scope | Own versus others' content and already-published edits unresolved; no automatic Administrator approval |
| Publish content | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, directly for authorized content | No per-publication Administrator approval. Publication eligibility/modules/objects not unrestricted |
| Unpublish/withdraw/archive, if supported | **SCOPE VERIFICATION REQUIRED**; full app access applies once the operation is within approved scope | **UNDECIDED** | Prototype `arsip` does not decide permission or public lifecycle; do not implement unsupported operation or infer Pegawai grant |
| Delete content, including delete-capable bulk operations | **ALLOW — CONFIRMED** | **DENY — CONFIRMED** | Physical/soft delete/restore/retention implementation undecided; no generic PATCH/import/reset bypass |
| View user accounts as User Management | **ALLOW — CONFIRMED** | **DENY — CONFIRMED** | Self-profile display/edit is a separate **UNDECIDED** operation, not permission to list/manage other users |
| Create user accounts | **ALLOW — CONFIRMED** | **DENY — CONFIRMED** | Initial status/credentials/procedure unresolved |
| Assign/change application role | **ALLOW — CONFIRMED**, values only Administrator/Pegawai UPTD | **DENY — CONFIRMED** | Self-demotion/last-admin/multiple-admin continuity/assurance and session effects unresolved; third role always outside current scope |
| Update account details through User Management | **ALLOW — CONFIRMED** | **DENY — CONFIRMED** | Allowed fields/normalization/identity verification remain to define; no self-service profile system invented |
| Update account status | **ALLOW — CONFIRMED**, approved lifecycle only | **DENY — CONFIRMED** | Role authority settled, exact states/edges/verification not settled |
| Activate account | **ALLOW — CONFIRMED**, when approved lifecycle supports it | **DENY — CONFIRMED** | Initial-active versus separate activation undecided |
| Suspend account | **ALLOW — CONFIRMED**, when approved lifecycle supports it | **DENY — CONFIRMED** | Reason/duration/reversal/active-session effect require policy |
| Reactivate account | **ALLOW — CONFIRMED**, when approved lifecycle supports it | **DENY — CONFIRMED** | Suspended versus deactivated restoration not interchangeable; permitted source states/conditions undecided |
| Deactivate account | **ALLOW — CONFIRMED** | **DENY — CONFIRMED** | Denied access does not equal permanent account deletion |
| Access full application administrative functionality | **ALLOW — CONFIRMED**, approved application scope | **DENY — CONFIRMED** | Does not approve cloud/DB/OS access or bypass authentication/MFA/CSRF/audit/security policy |
| Account permanent removal, bulk account import/reset or audit-clear | **UNDECIDED / SCOPE VERIFICATION REQUIRED** | **DENY** where operation entails User Management/full admin; no positive grant | Full admin access is not proof these destructive features/policies exist or are approved. No implementation. |

Where a confirmed ALLOW depends on unresolved scope, record the gap and deny unverified targets in future implementation rather than inventing unrestricted access. This is a proposed safe enforcement behavior, not a new role/blanket deletion of the owner's positive permissions.

## D. Server-side authorization requirements (specified, not implemented)

| ID | Requirement / acceptance implication |
|---|---|
| AZ-01 | Every protected operation authenticates the server account, verifies eligible active status and checks effective role/action/object/field scope. Permission and target/status rechecked consistently under concurrent changes where required. No browser role or session JSON trust. |
| AZ-02 | All User Management account reads/creation/details/role/status/activation/suspension/reactivation/deactivation restricted to Administrator. Pegawai direct requests cannot gain access via forged target IDs, role/status fields, generic settings/collection patches or hidden route names. |
| AZ-03 | Pegawai UPTD permitted create/edit/direct publish within explicitly authorized content scope; no mandatory Administrator sign-off per publication. Server validation/eligibility/status/audit/concurrency checks remain required and cannot conceal an invented approval prerequisite. |
| AZ-04 | Pegawai deletion denied through direct/batch/delete flags/reset/replace/import or destructive side effects of generic update. Unpublish/archive permission separately resolved; do not equate it to delete, or use it as an unauthorized removal bypass. |
| AZ-05 | Invalid/non-approved roles rejected; client cannot self-assign role/status/grant. Exactly two approved role values in the future approved contract; no new API role aliases/sub-roles invented here. |
| AZ-06 | Unauthorized/unauthenticated/inactive operations fail securely, with no mutation or unaudited partial success. Appropriate error/status/concealment contract still needs approval; do not imply existing 401/403 business endpoints. CORS or hidden UI is not authorization. |
| AZ-07 | Positive and negative direct-request tests must cover both roles, relevant content scope, inactive identities, revoked roles/status, concurrent publication and bypass attempts. No tests implemented/run now. |

No middleware/package/policy class, schema, endpoint route or dynamic permission framework is selected. Action/object constraints do not become third application roles. User Management ownership is confirmed; operating security/duty and implementation prerequisites stay separate.

## E. Account lifecycle specification

### E1. Candidate states, not approved schema

Earlier EV-05 describes conceptual request/creation/credential/activation/active/suspension/deactivation/reinstatement/disposition stages, not a concrete enum. For review, use **provisioned/pending activation**, **active**, **suspended**, **deactivated** as candidate state labels only. No state column/numeric value/duration or migration is approved. These labels are not additional roles.

Role changes and credential operations are actions, not automatically status transitions. Suspension/deactivation denote proposed ineligible access conditions but may have different policy/intent/reversibility; deactivation never automatically means physical removal. Exactly which states/edges exist needs U-03 approval. Account-request, invitation, email verification, initialization delivery, reset/recovery channels are not mandated or implemented here; record needs/gaps rather than inventing a system.

### E2. Transition/operation table

**Status rule:** confirmed actor permission does not confirm an entire transition edge. Rows explicitly mark proposed/undecided edge/control versus confirmed authority. Proposed controls derive from EV-05 lifecycle/security requirements and need approved expected results before code.

| ID / action | Source -> destination (candidate only) | Authorized actor / preconditions | Expected security effect / audit evidence | Confirmation level / remaining policy |
|---|---|---|---|---|
| LC-01 Create account | No account -> pending/provisioned **or** active; choice unresolved | Administrator only (CP-07); approved identity/login uniqueness, two-role assignment and account details validation | Proposed atomic creation/role/audit, no default/shared secret or browser identity import; pending account cannot authenticate if such state adopted. Actor/target/role/status/outcome/server time recorded. | Actor/create authority **CONFIRMED**; initial state, activation ordering, identity/credential issuance **UNDECIDED** (U-03/04/05) |
| LC-02 Activate | Pending/provisioned -> active if separate activation selected | Administrator only; approved initialization/eligibility/status/assurance prerequisites, current version | Proposed transition audit and eligible new authentication only after all security conditions; no blind upgrade of stale pending session | Actor **CONFIRMED**, edge/preconditions **PROPOSED / UNDECIDED**; no invitation or email verification requirement inferred |
| LC-03 Suspend | Active -> suspended if adopted | Administrator only; approved target/reason and safeguards, current status/version | Proposed deny new and existing protected access, invalidate/revalidate sessions/remember artifacts consistently; audit actor/target/from/to/reason/outcome | Actor **CONFIRMED**; state/reason/reversal/timing implementation **UNDECIDED**; revocation proposed control (U-03/06) |
| LC-04 Reactivate suspended account | Suspended -> active if approved | Administrator only; approved reinstatement evidence, current credentials/grants/eligibility checks | Proposed no old-session resurrection; fresh valid authentication/assurance, attributable audit | Actor conditional lifecycle authority **CONFIRMED**; permission to use this edge/conditions **UNDECIDED** |
| LC-05 Deactivate | Active/pending/suspended -> deactivated; permitted sources undecided | Administrator only; approved reason/target/account continuity and current version | Proposed stop protected access, cross-instance session/cached grant invalidation/revalidation; retain identity/history as policy requires, no automatic hard delete | Deactivate actor/operation **CONFIRMED**; source edges, retention/status/recovery procedure **UNDECIDED** (U-03/06/07) |
| LC-06 Reinstate deactivated account | Deactivated -> active **or** pending **or** not permitted; unresolved | Only Administrator could perform supported account reactivation; authorized policy/evidence required | Proposed no automatic restoration of previous sessions/grants; renewed approved checks and audit if permitted | Actor category confirmed by CP-07, actual reinstatement option/destination **UNDECIDED**; not interchangeable with LC-04 |
| LC-07 Assign/change role | Existing approved state -> same state unless lifecycle says otherwise; role -> one of two permitted values | Administrator only; approved target/allowed fields/role change/last-admin safeguards | Proposed reject unknown roles; revalidate permissions and log old/new role safely. Whether all sessions invalidated or another authoritative freshness mechanism used unresolved; stale privileges must not survive. | Role operation/exclusivity **CONFIRMED**; assurance/concurrency/continuity/session strategy **UNDECIDED** (U-05/06) |
| LC-08 Update account details/status | Approved current state -> same or separately allowed edge | Administrator only via User Management; approved writable/unique fields, explicit target and status-transition semantics | Proposed no mass-assigned privilege fields/invalid edges; audit safe changed fields/actor/target, status edges use dedicated lifecycle policy | Actor **CONFIRMED**; writable fields/validation/edge rules **UNDECIDED**; generic status update cannot bypass LC policy |
| LC-09 Inactive-account access attempt | Pending/suspended/deactivated (if adopted) -> unchanged | No role authorizes protected action for an ineligible account; server verifies status; state vocabulary pending | Proposed deny protected action/login and mutation, safe error and protected failure event without secret/false actor attribution | Active-status enforcement direction **CONFIRMED**; exact statuses/error/revocation design **PROPOSED**, tests depend on U-03/06/security policy |
| LC-10 Administrator recovery / credential change | State unchanged unless a separately approved lifecycle permits change | Recovery proof, performer/channel/authority unresolved; no Pegawai User Management capability created | Proposed no factor/status/role escalation, safe credential handling/session effects/audit; never invent reset email/invitation flow or public admin setup | Need to resolve **UNDECIDED** (U-04/05/06/08); only account-management exclusivity confirmed, not recovery procedure |
| LC-11 Permanent removal or archival | Any eligible source -> approved disposition if supported | Administrator full app role does not alone approve destructive retention/disposition process | Proposed revoke access first, preserve attributable approved history/references, protected backups/recovery; no permanent deletion inferred from deactivation | Feature/authority details/retention/reuse **UNDECIDED** (U-07); no record purge executed |

Lifecycle source/destination selection, initial credential issuance, first/last-admin recovery and assurance checks must be confirmed before executable transition contracts. No reactivation promise, default activation, lifetime, workflow approver or mandatory multi-person rule is selected. Future implementation must distinguish failed/denied attempt from committed transition; no partial status/grant change and no resurrection of revoked access is the proposed safety baseline.

## F. Security and audit requirements

**Specified requirements, not existing implementation:** server establishes verified actor identity, target account/content ID, action, authoritative timestamp and outcome; safe request/correlation identifier where supported, approved reason/change/version/status/role metadata for sensitive mutations. Unknown/failed login must not falsely identify the supplied username as a verified actor. Content create/edit/publication/deletion and account/role/status actions need attributable event coverage.

Propose protected audit write/read boundaries, consistent mutation/event persistence, no client actor/time injection or unauthorized audit modification/clear; fail-safe mutation handling on persistence/audit failure. Audit fields must exclude passwords/hashes, access/recovery tokens, session/cookie secrets and other sensitive authentication material. Logging a request ID must not log entire request bodies/environment. Event metadata may itself be personal/internal; collection/minimization/reader scope/review/retention/disposition and operator ownership remain unresolved (U-07/11). No append-only code, audit database or event log implemented.

Administrator full application access does not waive account eligibility, approved security checks, credential protection, CSRF/origin/session/MFA rules or audit integrity. Pegawai direct publication is a confirmed permission, not bypass of data validation, sanitization, authorized target scope or concurrency controls. No manual Administrator publication approval may be hidden inside these safeguards.

MFA remains undecided; Sanctum SPA remains recommended/conditional; CSRF/CORS/cookie/session configuration, approved domains/Cloud resources, MySQL connectivity/test isolation and recovery remain separate readiness dependencies. No package/config/environment/DB check performed here. Full application role does not imply external infrastructure or security-risk authority.

## G. Unresolved decision register

All authority for application permission/scope/lifecycle requirements is attributed to **PINTU Project Owner (Application Administrator)** where appropriate. Separate security/infrastructure/risk mandates remain **NOT IDENTIFIED — OWNER CONFIRMATION REQUIRED**; no appointments or legal title invented. Evidence is non-secret approved specification/decision, not credentials pasted into docs.

| ID | Question / current status | Why it matters / evidence or approval required | Affected implementation / proceed before resolution? |
|---|---|---|---|
| U-01 | Authorized Pegawai modules/objects/ownership/edit/view/field scope; **UNDECIDED** | Owner confirms own versus others' records, private drafts/collections and exact scope. Permission to create/edit/publish exists, scope is not universal. | Content queries/policies/tests; decision-neutral design yes, dependent scoped endpoints no |
| U-02 | Publication eligibility and editing published content; **UNDECIDED** | Owner defines valid content/public visibility, required validation and in-place versus revision/edit behavior; no mandatory Administrator sign-off. Direct publication not reopened as a question. | Publish/update/public representation/concurrency; dependent behavior no until resolved |
| U-03 | Supported account statuses/edges, immediate-active vs separate activation, suspension/reactivation/deactivation; **UNDECIDED** | Owner approves candidate states/allowed sources/destinations, criteria and denied transitions; credentials/activation conditions considered. Administrator authority already confirmed. | Account schema/endpoints/state tests; no migration/implementation until edge semantics approved |
| U-04 | Secure initial credential issuance and optional need for invitation/request/email verification; **UNDECIDED** | Owner/security authority defines actual requirements/channel/proof; no system, delivery provider or email-verification mandate invented. | Account creation/initialization; analysis yes, credential delivery code no |
| U-05 | Identity fields/uniqueness, exact two-role assignment/change, first/last/multiple-admin bootstrap/recovery/self-change safeguards; **UNDECIDED** | Owner defines target/process/continuity; separately approved security/credential recovery proof and authorized bootstrap custody/channel; no shared/default account. | User Management/schema/bootstrap; authoritative role boundary settled, technical process no |
| U-06 | Status/role/credential changes' session consequences, current/all sessions, revocation freshness; **UNDECIDED** | Owner plus designated security authority approves revalidation/invalidation, cross-instance/in-flight rules, lifetimes and failure behavior. Stale authority cannot be accepted merely to avoid deciding. | Session/policy/status updates and concurrency tests; dependent operational code no |
| U-07 | Deactivation record preservation, permanent deletion/archival/reuse, content deletion semantics/restore and audit retention; **UNDECIDED** | Owner/governance/security custodian approves disposition/recovery/access rules; deactivation not permanent delete; physical vs soft content deletion unresolved despite Administrator delete permission. | Delete/lifecycle/audit/backup; destructive functions no |
| U-08 | Password/recovery/MFA policy, support and authorized security-risk approver; **UNDECIDED** | Explicit credential/session/MFA decision (all-user, privileged-first with other policy, or permitted documented deferral); no optional/no-MFA inference or password reset procedure assumed. | Auth/recovery/MFA readiness; neutral specs only, operational acceptance no |
| U-09 | Unpublish/withdraw/archive support and Pegawai permission; **SCOPE VERIFICATION REQUIRED / UNDECIDED** | Owner determines feature and permission distinctly from delete and publish. Earlier prototype `arsip` is not an approved privilege. | Content lifecycle; do not silently grant/deny as confirmed policy or implement unsupported feature |
| U-10 | Own profile read/edit versus User Management and other administrative features; **UNDECIDED / SCOPE VERIFICATION REQUIRED** | Owner approves precise self-service scope if needed; no account list/role/status edits to Pegawai through profile route. | Profile/settings/role field security; no implicit self-service grant/system |
| U-11 | Audit event fields, consistency/access/review/minimization/correlation/operations; **UNDECIDED** | Owner plus designated security/governance/operational mandates specify protected evidence and failure/retention policy, not an unimplemented audit claim. | Logging/account/content changes; design yes, dependent audit-sensitive code no |
| U-12 | Sanctum/HTTPS/origins/CSRF/CORS/cookies/session store, DB/test access and Cloud; **BLOCKED / UNVERIFIED** | Appropriate separately designated custodians approve actual configuration/access/isolation/non-destructive connectivity/recovery; secrets remain outside docs, no live checks here. | Authentication/persistence/integration/deployment; independent documentation yes, dependent implementation no |
| U-13 | Bounded Phase 3 implementation scope; **UNDECIDED** | New explicit user authorization names packages/files/test environment/closed dependency IDs and exclusions, after relevant decisions. | All future code/execution; this phase permits documentation/commit/push only |

## H. Planned acceptance criteria and test scenarios — unexecuted

No tests/code/requests/database runners implemented or executed. Future tests use approved isolated synthetic fixtures and endpoint contracts, not invented live routes or actual credentials. Policy-dependent setup is **BLOCKED** until its U decision closes; no guessed field/status/scope makes a test pass. Permission intent is confirmed even while execution remains gated.

| Test ID / setup | Planned action | Expected behavior / acceptance criterion | Required decisions |
|---|---|---|---|
| AT-01 Eligible active Administrator fixture; approved identity/creation rules | Create user and assign each of two allowed roles | Allowed, exactly approved role assigned, safe atomic audit/no secrets; no default credential or public registration | U-03/04/05/06/11/12, approved auth/test scope |
| AT-02 Eligible Pegawai fixture | Direct account create/role-change/list or admin account edit request | Denied with approved safe error, no state/grant/audit false-success; hiding UI irrelevant | Approved contracts/auth fixtures; role denial CP-07 already confirmed |
| AT-03 Eligible Pegawai and authorized content target | Create then edit content | Allowed within authorized module/object/field; safe server validation/audit; no fabricated global access | U-01/02/11/12 |
| AT-04 Eligible Pegawai, valid authorized publishable content | Publish directly, including through direct API | Allowed without an Administrator approval event/step; unauthorized modules/objects denied; audit server actor/time and approved concurrency | U-01/02/06/11/12; CP-05 not reapproved |
| AT-05 Pegawai target and Administrator target, approved deletion semantics | Pegawai direct/bulk/delete-flag/import-reset attempt; Administrator delete | Pegawai denied no removal/mutation; Administrator permitted approved delete behavior; generic update cannot bypass prohibition | U-01/07/11/12; deletion authority CP-06 fixed |
| AT-06 Pegawai, account targets including own account | Try status/activate/suspend/reactivate/deactivate or role assignment through User Management/settings | Denied, status/role unchanged, no entitlement from editable client state | U-03/05/06; confirmed role restriction |
| AT-07 Forged frontend role/session, cross-target ID or alternate route | Direct protected action without/with unauthorized server identity | Unauthenticated/forbidden securely denied, no mutation, applicable object/field/status role check; eligible authorized operations remain allowed | Auth contract/U-01/06/12 |
| AT-08 Approved inactive state fixtures for both roles | Login/protected content/admin operation using valid old credential/session | Denied no mutation; state/permission authoritative; no stale access after status revocation | U-03/06/08/12; no statuses invented |
| AT-09 Administrator/Pegawai fixture with invalid/unapproved role payload | Submit third role, alias/sub-role or client escalation string | Rejected per approved validation/permission contract, no persistence or privilege assignment; only exact two approved values | U-05, server contract |
| AT-10 Synthetic secret markers and approved audit design | Create/edit/publish/delete and account/role/status actions; inspect event/logs | Actor/target/action/server time/outcome, safe correlation if supported, no password/hash/token/session/cookie/recovery secrets; audit tampering denied | U-07/11/12; synthetic markers only |
| AT-11 Approved lifecycle fixtures and current version | Execute every allowed/forbidden LC edge, concurrent updates and role changes | Allowed edges only, no arbitrary interchangeability/deactivation=deletion; revocation/failure behavior matches policy, no stale privilege commit | U-03/05/06/07/11/12 |
| AT-12 Published content fixture, two editors | Edit/publish/unpublish/archive under approved final rules | Direct authorized Pegawai publication retained; edit/public representation and conflict control match approved policy; undecided unpublish not silently granted | U-01/02/09/11/12 |
| AT-13 Unknown/public actor and approved admin bootstrap specification | Attempt public signup, replay bootstrap/recovery or self-grant | No public registration/account creation; no default/shared password/backdoor or role/status recovery bypass | U-04/05/08/11/12; procedures remain unimplemented |

Documentation acceptance: CP decisions exact, matrix contains only two roles, unknown scope/state/procedure explicit, current direct-publication decision reconciled with older recommendations, no mandatory Administrator approval introduced, transitions/status confirmations differentiated, safety/audit and planned tests traced, no secrets, old documents unchanged and single new deliverable isolated.

Future implementation acceptance additionally requires resolved relevant U decisions, exact bounded authorization and tests actually executed in approved isolation, appropriate positive/negative/direct-request coverage and independent review. No implementation success claim made here.

## I. Phase 3 readiness impact and scoped governance delta

| Affected area | Updated readiness | Effect on earlier records |
|---|---|---|
| Role model/owner | **CONFIRMED design** | CP-01/02 reinforce OWN-01/02 and GOV-01.application; no extra roles or actor invention |
| Content permission direction | **CONFIRMED create/edit/direct publish and no Pegawai delete; PARTIAL scope specification** | Extends OWN-03/GOV-10.role-boundaries; unpublish/read/ownership/published-edit scope unresolved. No mandatory per-publication Administrator approval. |
| User Management authority | **CONFIRMED Administrator-only** | GOV-04.account-manager settled; roles/status/account actions prohibited for Pegawai; process/security/fields pending |
| Account lifecycle | **PARTIAL / dependent implementation NO-GO** | LC candidate states/edges reflect EV-05 and new actor decision, not fully closed GOV-03/04/05/06/09/13 or permanent deletion approval |
| Server authorization design | **PARTIAL; implementation NO-GO** | Required allow/deny directions now sharper; exact targets/contracts/policies/concurrency/audit/auth depend on U register and user scope |

Unchanged separate dependencies: MFA/security Gate B remains **NO-GO**, Sanctum still proposed, MySQL/test Gate D **BLOCKED**, origins/Cloud Gate E **BLOCKED**, audit/operational policy open. No new evidence closes access, security configuration, risk authority or deployment. Gate A/C gain defined actor/action policy but do not become implemented or globally PASS.

Only this Phase 2.6 document records additive deltas. Prior documents remain historical evidence and are not rewritten/staged; their unapproved approval-role suggestions cannot override CP-05. Later consistency/implementation tasks must honor direct Pegawai publication while preserving unresolved scope/control choices, not automatically remove every workflow/revision concept or mutate the UI.

**Phase 3 authorization:** this documentation phase GO; separately authorized non-executable design/test preparation may proceed. Authentication/MFA/account/content endpoints, role policies, MySQL/migrations/audit code, integration and deployment remain **NO-GO** for dependent implementation until actual prerequisites and bounded authorization. No blanket readiness granted.

## J. Smallest next decision set, ordered by dependency

1. **Owner resolves content scope and publication behavior (U-01/02/09):** identify authorized Pegawai modules/objects/ownership/read/edit scope, valid publication eligibility and published-edit behavior, and whether unpublish/archive is supported/allowed. Do not reopen already confirmed create/edit/direct publish/no-delete rights or add Administrator approval per publication.
2. **Owner resolves minimal account lifecycle/role continuity (U-03/04/05/06):** choose supported candidate states/edges, initial-active versus separate activation, secure credential delivery, suspension/deactivation/reinstatement and first/last-admin/role-change/session consequences. Record unanswered security/recovery requirements rather than invent invitation/email/reset systems.
3. **Designated security/infrastructure custodians close necessary technical blockers (U-07/08/11/12):** explicit credential/session/MFA and audit/disposition rules, origin/CSRF/CORS/cookie settings, approved MySQL/test access/connectivity and recovery. Actual security/risk/DB/Cloud mandates remain to confirm; do not paste credentials.
4. **Request a bounded implementation task (U-13)** naming only the slice with closed dependencies and an approved isolated test target. Independent designs can be scoped while unrelated production resources remain unverified. Do not create unnecessary new phases merely to repeat settled decisions; obtain concrete owner choices/evidence and implement only after separate authorization.

### Verification and publication boundary

Only `backend/docs/PHASE-2.6-CONTENT-PERMISSION-AND-ACCOUNT-LIFECYCLE-SPECIFICATION.md` is added. Verify complete document/source consistency, referenced paths, no secrets, whitespace/final newline, `git diff --check`, preservation fingerprints and exact baseline status. Stage literal path only, inspect complete staged diff/`git diff --cached --check`, commit `docs(pintu): specify content permissions and account lifecycle` and normal push to `origin/laravel-development`; verify remote tip/single-file commit and all385 pre-existing outstanding paths preserved. Stop for branch/remote/staging/permission/isolation failures; no add-all, clean/reset/restore/discard/amend/force or earlier work publication.

No app tests, SQL/DB connection, package/config/source/UI/environment change, new roles, lifecycle implementation, authentication/MFA/RBAC/audit code or deployment is performed. Actual command/review/commit/push outcomes are reported afterward; no planned test marked executed. Phase 3 remains unstarted.
