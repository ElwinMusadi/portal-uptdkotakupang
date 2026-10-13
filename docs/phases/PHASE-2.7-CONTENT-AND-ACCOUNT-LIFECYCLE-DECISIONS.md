# PINTU — Content and Account Lifecycle Decisions

**Phase:** 2.7. **Date:** 2026-10-12. **Status:** owner-confirmed decisions with qualified specification; documentation only, not Phase 3 implementation authorization.

## A. Purpose, scope and source precedence

Record current decisions about published-content editing/unpublishing, Administrator creation/activation and initial credential setup. Exactly two application roles remain **`Administrator`** and **`Pegawai UPTD`**. Owner remains **`PINTU Project Owner (Application Administrator)`**; no legal name, official title, extra role/sub-role or alternative authorization identity inferred.

This document is authoritative for the precise owner-confirmed Phase2.7 updates, not all unresolved implementation policy. Earlier deliverables remain unchanged. The new decisions settle permissions and intended administrative activation outcome; they do not choose object ownership, public revision behavior, every account state, security policy, delivery provider or infrastructure. No application functionality, dependency, config, tests, DB state or UI is changed.

### Verified repository preflight

Branch `laravel-development`; local/remote tip `92208623b58e79740b0513bd2b98173a57ccff2d`; no staged changes or divergence. Actual status is **385 outstanding paths: 6 tracked unstaged modifications + 379 untracked files**, consistent with Phase2.6. New path did not exist. Tracked diff fingerprint `cac31712e8536b629d9fec47bb290ddea1f9c29e`; existing-file inventory fingerprint `588444b73d31f9247e7edbe28c2238b9bc4b0f45` in Git output order. Final preservation comparison excludes only this document; pre-existing HTML, handover, backend, scripts, skills/tooling and older reports stay intact.

| Evidence ID | Actual source reviewed | Interpretation / publication |
|---|---|---|
| EV-01 | `backend/docs/PHASE-1.1-SECURITY-AND-INTEGRATION-DISCOVERY.md` | Historical prototype contract/workflow and health/static tests; local/untracked, not current auth/persistence proof |
| EV-02 | `backend/docs/PHASE-2-ARCHITECTURE-AND-API-DESIGN.md` | Proposed revisions/review/topology, D03/D13/D15-D17; local/untracked, recommendations not owner policy |
| EV-03 | `backend/docs/PHASE-3-IDENTITY-AND-PERSISTENCE-FOUNDATION-SCOPE.md` | Candidate lifecycle/packages/gates; local/untracked, no executable schema approved |
| EV-04 | `backend/docs/PHASE-3-READINESS-GATE.md` | Tracked/remote, prior PARTIAL/NO-GO/BLOCKED gates and independent preparation limits |
| EV-05 | `backend/docs/PHASE-2.2-SECURITY-AND-IDENTITY-LIFECYCLE-SPECIFICATION.md` | Tracked/remote, L1-L11/BOOT/AUTH/SES/SPA/MFA/audit and planned tests; parameters still open |
| EV-06 | `backend/docs/PHASE-2.3-WORKING-TREE-AND-PHASE-3-READINESS.md` | Tracked/remote, exact385 audit and no cleanup/publication boundary |
| EV-07 | `backend/docs/PHASE-2.4-GOVERNANCE-DECISIONS-AND-PHASE-3-AUTHORIZATION.md` | Tracked/remote, GOV authority/approval/dependency register; no blanket Phase3 authorization |
| EV-08 | `backend/docs/PHASE-2.5-GOVERNANCE-OWNERSHIP-AND-READINESS-UPDATE.md` | Tracked/remote, OWN-01-04 and known owner/two-role design versus security/infrastructure mandates |
| EV-09 | `backend/docs/PHASE-2.6-CONTENT-PERMISSION-AND-ACCOUNT-LIFECYCLE-SPECIFICATION.md` | Tracked/remote, CP-01-09, matrix/candidate LC/U gaps; immediate preceding permission baseline |
| EV-10 | Current explicit project-owner Phase2.7 instruction | New permission/activation/credential-direction confirmation only; no technical policy or code approval |

All nine files exist and their actual contents were read directly or through read-only architectural review. The three local/untracked predecessors and foundation artifacts are not published by this commit; references are evidence paths, not promises about clean remote checkout. No earlier document is rewritten. Historical passing tests do not become new execution claims here.

## B. Decision register

`CONFIRMED` is limited to owner statements; `PARTIAL` means direction settled but details open; `UNDECIDED` means no policy choice; `PROPOSED` means engineering safeguard/design awaiting approval. Confirmed denial remains a confirmed decision, not a new feature. No role is created by these IDs.

| ID | Decision | Status / source | Implication and remaining qualification |
|---|---|---|---|
| D27-01 | Exactly Administrator and Pegawai UPTD; owner is application Administrator | **CONFIRMED**, EV-08/09/10 | No added roles or inferred number of accounts; use owner label above |
| D27-02 | Administrator full application access, create/view/edit/publish/unpublish/delete supported content, exclusive User Management | **CONFIRMED**, EV-10 | Supported modules and security/lifecycle policy still apply; full application role not external DB/Cloud/security-risk authority |
| D27-03 | Pegawai UPTD can create and edit authorized content | **CONFIRMED**, EV-09/10 | Exact module/object/field/ownership and view scope not universal |
| D27-04 | Pegawai UPTD may edit already-published content within authority without mandatory Administrator approval | **CONFIRMED**, EV-10 | Whether public changes are immediate or revision-based, and cross-employee authority, remain open |
| D27-05 | Pegawai UPTD directly publishes authorized content without mandatory Administrator approval per publication | **CONFIRMED**, EV-09/10 | Publication eligibility/content restrictions/scope/concurrency still require specification; do not hide an approval step in safeguards |
| D27-06 | Pegawai UPTD can unpublish authorized content without mandatory Administrator approval | **CONFIRMED**, EV-10 | Destination/public removal semantics not selected; not deletion or permission over every employee/module |
| D27-07 | Only Administrator can delete content; Pegawai UPTD cannot delete or bypass via other operations | **CONFIRMED**, EV-09/10 | Physical/soft-delete/restore/retention details remain open; unpublish is distinct permitted action within scope |
| D27-08 | Administrator alone creates/activates accounts, assigns one of the two roles and manages supported status transitions; Pegawai no User Management | **CONFIRMED**, EV-09/10 | Complete state graph, safeguards/recovery and session effects unresolved |
| D27-09 | Newly created account is active immediately **after Administrator creates and activates it** | **CONFIRMED intended workflow/outcome**, EV-10 | Does not mean create-only autoactivation; single atomic versus two operations/intermediate non-active state undecided |
| D27-10 | Initial credential setup follows an approved secure activation or password-reset mechanism direction | **CONFIRMED direction**, EV-10 | **PARTIAL mechanism specification:** choice/design/channel/provider/token/password/abuse/recovery parameters unresolved; no implemented reset/activation delivery claim |
| D27-11 | No public self-registration or Pegawai account/role/status management | **CONFIRMED**, EV-09/10 | An approved initial credential flow is not public signup, automatic role assignment or self-activation authority |
| D27-12 | Backend checks verified identity, eligible active status and effective action/object permissions; secrets protected and audit attributable | **CONFIRMED enforcement/security requirements**, EV-09/10 | Actual controls/interfaces/logging not implemented; technical/policy details remain to approve |

Scoped changes to EV-09: its U-02 **permission to edit published content** and U-09 **permission to unpublish** are now settled within authority; public revision/unpublish destination and object scope remain open. U-03 intended create+activate outcome is settled, not atomicity/all edges. U-04 initial activation/reset direction is settled, not delivery/procedure. Earlier CP create/edit/direct publish/no-delete/User Management restrictions retained, not reversed. Prior mandatory Administrator approval suggestions cannot override D27-04/05/06; do not erase all workflow/revision concepts simply because authority is direct.

## C. Content permission matrix

The two role columns record permissions, not existing endpoints. ALLOW is within authorized/supported scope; DENY is confirmed prohibition. Scope uncertainty must be resolved explicitly, not blanket read-all/write-all assumptions or a new approval role.

| Operation | Administrator | Pegawai UPTD | Scope/state qualification |
|---|---|---|---|
| View content | **ALLOW — CONFIRMED**, supported full app scope | **SCOPE VERIFICATION REQUIRED** | Access to authorized editing targets is necessary; global drafts/private/other modules not blanket granted |
| Create content | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, assigned authority | Module/ownership/field scope unresolved |
| Edit draft content | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, assigned authority | Own/other employee/object boundaries unresolved |
| Edit published content | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, assigned authority | No mandatory Administrator approval; immediate public update versus revision behavior undecided |
| Publish content | **ALLOW — CONFIRMED** | **ALLOW — CONFIRMED**, directly in authority | Content eligibility may differ by module; do not invent a review prerequisite |
| Unpublish content | **ALLOW — CONFIRMED**, supported modules | **ALLOW — CONFIRMED**, in authority | Destination state/public withdrawal process undecided; no mandatory Administrator approval |
| Delete content | **ALLOW — CONFIRMED** | **DENY — CONFIRMED** | Unpublish cannot silently hard/soft-delete the record; approved delete implementation/retention open |
| View another employee's content | **ALLOW — CONFIRMED**, supported full app scope | **UNDECIDED / SCOPE VERIFICATION REQUIRED** | No automatic same-unit/all-employee access granted |
| Edit another employee's content | **ALLOW — CONFIRMED**, supported full app scope | **UNDECIDED / SCOPE VERIFICATION REQUIRED** | Confirmed edit permission does not establish cross-owner authority |
| Publish/unpublish outside the employee's assigned authority | **ALLOW within Administrator's supported full scope** | **DENY outside resolved scope**; definition of assigned scope **UNDECIDED** | No positive out-of-scope grant; proposed fail-closed checks cannot guess the scope |
| Create/activate/manage accounts, assign roles or statuses | **ALLOW — CONFIRMED**, approved lifecycle | **DENY — CONFIRMED** | No profile/settings/credential reset workaround for User Management |

## D. Content lifecycle — permission versus mutation semantics

| Action | Confirmed performer/authority | Intended outcome / unresolved transition | Proposed security/audit guardrail, not implementation |
|---|---|---|---|
| Create | Both roles in authorized scope | New content record; initial draft/status/ownership/default publication values **UNDECIDED** | Server identity/time, valid scoped fields, no client actor/role injection; record create outcome |
| Edit draft | Both roles in authorized scope | Valid authorized changes; exact fields/version/conflict behavior **UNDECIDED** | Scope/active-status validation and edit attribution; no silent cross-owner update |
| Direct publish | Both roles; Pegawai no per-publication Administrator approval | Authorized eligible content becomes publicly published under approved module semantics; status/visibility/cache/timestamp mechanics **UNDECIDED** | Validate scope/content/version and audit actor/target/result; no additional app role or hidden approval step |
| Edit published | Both roles; Pegawai may edit published content in authority | Edit permission **CONFIRMED**; immediate in-place effect, independent working revision, subsequent publication/visibility and conflict rules **UNDECIDED** | Protect data/history and sanitized content; revision model cannot introduce mandatory Administrator approval of these actions |
| Unpublish | Both roles; Pegawai authorized direct action | Withdraw from publication as specified; destination draft/archive/other **UNDECIDED**. Must not equate to delete or automatically choose `draf`/`arsip`. | Record authorizing actor/action/target and subsequent approved visibility/version changes; safe public cache behavior to specify |
| Delete | Administrator only | Removal under approved physical/soft-delete/retention/restore policy **UNDECIDED** | No Pegawai delete via patch/bulk/reset/import; audit and recovery references protected. Distinct from permitted scoped unpublish. |

Prototype statuses (`draf`, `diperiksa`, `persetujuan`, `terbit`, `dikembalikan`, `arsip`) are historical client labels, not this phase's approved complete state machine. No new status, route, role or content-module authority invented. Administrators' full application access remains subject to authentication/security protections, not unverified infrastructure privilege.

## E. Account lifecycle — intended active outcome, incomplete technical graph

**Confirmed workflow:** Administrator creates and activates the account; after completion it is active immediately. Do not say account stays inactive after that completed action, require a later email verification/credential reset to reverse the confirmed active outcome, or infer automatic activation on mere creation. Whether creation+activation is one transaction/action or two recorded steps, and whether a non-active intermediate exists, remains open.

**Active status is not automatic authentication.** Valid credentials/approved authentication assurance and permissions are still required before issuing a session or permitting protected actions. Secure credential setup may be coordinated with creation/activation under an approved future process, but its timing does not silently redefine status. No `credentials_initialized` field/schema, default password or operational login bypass is selected.

Candidate pending/provisioned, active, suspended and deactivated labels are conceptual review vocabulary from EV-05/09; only the specified active outcome is confirmed. The complete enum/source/destination graph is not approved or implemented. Suspension, deactivation, recovery, reinstatement and deletion are not interchangeable.

| Source state | Action / destination | Authorized actor / preconditions | Security effect and audit evidence | Status / unresolved conditions |
|---|---|---|---|---|
| No account | Create account -> part of intended create+activate workflow; immediate post-create intermediate **UNDECIDED** | Administrator only; approved identity/unique login and one of two roles; details/credential coordination pending | Proposed consistent record/role/audit, no public registration/default secret/client account import; actor/target/action/outcome/time | Actor/create **CONFIRMED**; atomicity/separate records/intermediate **UNDECIDED** |
| Created account, or within combined creation action | Administrator activates -> **active immediately after completed create+activate** | Administrator only; future validated transaction/eligibility/security requirements, no invented email verification prerequisite | Active account status; not automatic session issuance; record creation+activation attribution as approved transaction model requires | Intended administrative outcome **CONFIRMED**; technical operation ordering/concurrency/failure behavior **PARTIAL** |
| Active, if complete model adopts label | Suspend -> candidate suspended | Administrator only; approved target/reason/transition/control | Proposed deny protected access and invalidate/revalidate old sessions/grants consistently, record from/to/actor/outcome | Actor authority **CONFIRMED**, state edge/conditions/session process **UNDECIDED** |
| Suspended, if adopted | Reactivate -> candidate active under approved rules | Administrator only; reinstatement criteria/credentials/grants/current version not decided | Proposed no resurrection of old sessions/privileges, fresh approved authentication and audit | Conditional actor authority **CONFIRMED**, eligible sources/conditions **UNDECIDED** |
| Active/other approved source | Deactivate -> candidate deactivated | Administrator only; approved lifecycle/source/continuity rules | Proposed stop new/existing protected access, authoritative revocation, preserve identity/evidence as retention requires; no automatic permanent delete | Operation/exclusivity **CONFIRMED**; source edges/retention/failure/recovery details **UNDECIDED** |
| Deactivated, if adopted | Reinstate -> active/pending/other or forbidden | Administrator only if approved lifecycle permits | Proposed no stale session/grant resurrection, verification/audit under actual policy | Reinstatement availability/source/destination **UNDECIDED**, not implied equivalent to suspended reactivation |
| Existing account | Assign/change role -> exactly Administrator or Pegawai UPTD; status unchanged unless separate approved transition | Administrator only; target/current-version and first/last/multiple-admin/self-change safeguards pending | Proposed current permissions revalidated, audit safe old/new role; whether sessions destroyed or another freshness method **UNDECIDED** | Two-role actor/operation **CONFIRMED**, session/concurrency/continuity policy open |
| Ineligible account (candidate pending/suspended/deactivated) | Protected access attempt -> no state change | Neither role grants protected action while inactive; backend verifies status | Deny safely/no mutation; protected failure event without secret or false actor claim; no session from mere role label | Enforcement requirement **CONFIRMED**, exact status vocabulary/response/revocation mechanism **PROPOSED / UNDECIDED** |

Full state model, removal/archive/retention, Administrator bootstrap/recovery and all unlisted edges stay unresolved. No public invitation/request workflow is required by the selected credential direction. Deactivation does not authorize record deletion; role assignment/status authority does not automatically settle security-risk/Cloud/DB custody.

## F. Initial credential setup and recovery

**Chosen direction:** an approved secure activation or password-reset mechanism for initial credential establishment. This is confirmed as a direction, **not** a chosen package, emailed link, invitation workflow, exact token format/endpoint/provider or working delivery service. No email delivery/verification or password-reset availability is claimed. Choosing the direction does not approve all earlier proposed recovery parameters.

| Requirement | Current confirmation / qualification |
|---|---|
| Secure credential establishment/reset | User must establish/reset through approved secure process; no hardcoded/shared/default initial password, plaintext/reversible storage or client-authoritative identity. Exact verification/issuance/channel/procedure and password policy remain open. |
| Expiry and single-use | Appropriate token/capability expiry and single-use where applicable; binding to correct subject/purpose and safe concurrent/replay handling. Exact lifetime, issue/reissue/invalidation and consumption design **UNDECIDED**; no numeric policy invented. |
| Token/secret protection | Protect passwords, sensitive tokens and session material in storage/transport/access; never print them in logs/source/public URLs/ordinary messages. No raw initial password in URL; approved confidential delivery of reset/activation capability, if needed, must avoid log/referrer/history/analytics leakage. Whether tokens are in body, protected delivery link or another channel remains **UNDECIDED**; no chosen URL design or ordinary secret link permitted. |
| Abuse and safe errors | Approved rate-limit/verification/replay rules and safe non-enumerating failure responses; no secret-bearing error/stack/path. Thresholds/backoff/proxy trust and recovery/lockout tradeoffs **UNDECIDED**. |
| Status and permission isolation | Credential initialization/reset cannot create account, assign role, self-activate/reinstate or bypass future MFA/status controls. New active status after Administrator create+activate is not a logged-in session. Sequence with initialization remains to specify. |
| Administrator recovery | Performer/proof/channel/first-last-admin continuity and factor/session consequences **UNDECIDED**. Never invent public setup/backdoor/shared credential or recovery bypass. Initial setup direction is not a complete ongoing reset/recovery policy. |
| Actual delivery infrastructure | Channel/provider/email/SMS/other capability and secret custody **UNDECIDED / UNVERIFIED**. No provider/endpoint/env values or verified recipient channel inferred from prototype email field. |

Requirements describe necessary security design, not executed controls. Identify appropriate security/recovery authority separately; owner application Administrator does not automatically approve MFA/risk/infrastructure mandates. Credential/secret operational evidence must be supplied through approved secure channels outside docs/Git/chat, never requested here as values.

## G. Server-side authorization and audit requirements

- Authenticate verified backend identity, active eligibility and effective role/action/module/object/field authority for **every** protected operation. Browser label/button/session state is not a security control. No middleware/policy implementation claimed.
- Pegawai direct API requests cannot delete or use account/role/status management. Scope checks also protect against editing/unpublishing other employees or modules without resolved authorization; unknown scope is not universal permission.
- Authorized Pegawai edit-published/publish/unpublish actions require no mandatory Administrator approval; validation/assurance/content eligibility/concurrency must not conceal that disallowed step. Unpublish is a distinct granted operation, not deletion bypass.
- Inactive/unauthenticated/forbidden requests fail securely without unintended mutation or permission/status promotion. Version/session/status consistency and exact safe error contract still need approval.
- Audit should identify server actor, target account/content, action, authoritative time, outcome and safe correlation where supported; protected read/write integrity, minimal relevant state/role/version metadata and failure consistency require design. No client actor/time trust or routine clear-all assumption.
- No passwords/reset tokens/session/cookie secrets or other authentication material in logs/audit/ordinary errors. Unknown login identifiers do not falsely attribute a verified actor. Retention/read/review/minimization/disposition/operational policy unresolved; logging is **not implemented**.

MFA, session/CSRF/CORS/cookie/HTTPS/domain settings, MySQL/test isolation and Cloud ownership remain distinct security/infrastructure dependencies. Full app access or active account status cannot substitute for them. No roles or code/schema selected by the security requirements.

## H. Remaining decision register

Each row identifies actual gap rather than reopening confirmed rights. Application scope/lifecycle choices go to **PINTU Project Owner (Application Administrator)** where appropriate. Separate security/risk/infrastructure mandates remain **NOT IDENTIFIED — OWNER CONFIRMATION REQUIRED** unless explicit evidence designates them. No invented job title/appointment/credential/legal policy.

| ID | Question / status | Dependency and evidence needed | Affected area / proceed before resolution? |
|---|---|---|---|
| R27-01 | Module/object/field/unit versus record-owner authority, general/private views and cross-employee editing/unpublishing; **UNDECIDED** | Owner-approved precise scope matrix; own/other employee authority and content classification evidence | Content queries/policies/tests; neutral spec yes, target-dependent implementation no |
| R27-02 | Content-specific publication eligibility/restrictions; **UNDECIDED** | Owner-approved supported modules/valid publication criteria without mandatory Administrator approval for granted employee actions | Publish/edit/unpublish validation and public projection; no invented universal scope/requirements |
| R27-03 | Published-edit immediate public effect versus revisions/publication/version/conflict behavior; **UNDECIDED** | Owner selects public visibility/revision semantics; no reapproval of already confirmed edit permission, no imposed Administrator step | Edit/public representation/version/caches; dependent behavior no until specified |
| R27-04 | Unpublish destination/visibility/republish behavior; **UNDECIDED** | Owner defines draft/archive/other state, content preservation and supported module mechanics; not automatic deletion | Content lifecycle/caching; unpublish authority confirmed, destination implementation no |
| R27-05 | Creation+activation atomic versus two recorded actions/non-active intermediate and failure/ordering; **PARTIAL** | Intended active outcome confirmed; owner/technical spec confirms transaction/result/failure/audit and credential coordination | Account create/activate schema/contracts; no arbitrary intermediate or delayed status inserted |
| R27-06 | Full statuses/edges, suspension/reactivation/deactivation/removal/retention; **UNDECIDED** | Approved candidate state graph, allowed sources/destinations and safeguards, explicit deactivated reinstatement choice | Account lifecycle/persistence; actor authority settled, full graph dependent work blocked |
| R27-07 | Exact secure activation/reset mechanism, verification/channel/provider/issuance/credential policy; **PARTIAL direction / UNDECIDED details** | Approved process/channel capability and identity verification/custody; no assumed email/provider/invitation or password policy | Initial credential/recovery integration; design yes, delivery/reset code no |
| R27-08 | Capability expiry/single-use/replay/reissue/rate-limit/token leakage design; **UNDECIDED parameters** | Security-approved limits/proof/consumption/failure/abuse/privacy specs; no raw secrets in evidence | Credential endpoint/token/persistence/abuse; dependent implementation blocked |
| R27-09 | Administrator bootstrap/recovery and first/last/multiple-admin continuity; **UNDECIDED** | Owner process and separately confirmed security/operator mandate, verified proof/custody/recovery requirements | Bootstrap/recovery/account continuity; no default/public setup or recovery policy inferred |
| R27-10 | Role/status/credential changes' current/all-session effects and immediate authority freshness; **UNDECIDED** | Approved session invalidation/revalidation/current version/replica/in-flight strategy, lifetimes and failure semantics | Permissions/lifecycle/session tests; stale authority not acceptable workaround |
| R27-11 | MFA, auth/session/password security policy and security-risk approver; **UNDECIDED** | Explicit authorized security decision: all-user/privileged-first with other-user policy or permitted reviewed risk deferral, factor/recovery/session/credential requirements | Authentication/MFA readiness; neutral preparation only, operational acceptance blocked |
| R27-12 | Audit events/fields/retention/read/review/minimization/consistency and operations; **UNDECIDED** | Approved protected evidence/custodian/recovery/retention spec, no legal duration invented | Audit/lifecycle/content changes; not implemented logging or independent role |
| R27-13 | MySQL target/access/grants/TLS/test isolation/connectivity/schema/recovery; **BLOCKED / UNVERIFIED** | Appropriate custodian privately manages approved access; confirmed isolated test target and separately authorized non-destructive connection evidence, reviewed model/transactions/recovery | DB/migrations/MySQL tests; no connection/SQL now, independent docs only |
| R27-14 | Frontend/API domains/origins/HTTPS/stateful CORS/CSRF/cookie/shared sessions; **BLOCKED / UNVERIFIED** | Verified approved environment mapping/security config and later authorized parity tests; Sanctum still a recommendation | SPA transport/auth/security tests; no inferred wildcard or production domain |
| R27-15 | Laravel Cloud ownership/resources/environment/secrets/deployment/incident/backup readiness; **BLOCKED / UNVERIFIED** | Separate infrastructure/domain/release mandates and actual verified configuration; no platform documentation = account readiness | Production deployment/canary/operations; local design need not wait for every production resource |
| R27-16 | Exact future implementation scope; **UNDECIDED / NOT AUTHORIZED** | New explicit user task names closed decision dependencies/files/package changes/environment/test targets/exclusions and review | All code/execution; current task permits only new document and isolated publication |

Historical role/lifecycle/readiness IDs in preceding docs remain unchanged and must be interpreted with these latest scoped deltas, not silently rewritten. Approved initial setup direction and active outcome do not close R27-07-11; confirmed unpublish/edit permission does not close R27-01-04.

## I. Planned acceptance scenarios

**Every scenario below: `PLANNED — NOT IMPLEMENTED OR EXECUTED`.** Use approved isolated synthetic accounts/targets and future endpoint contracts. Missing prerequisite means blocked expected-result definition, not an engineer-selected default to make a test pass. No runtime/HTTP/database/credential flow is exercised here.

| Scenario | Setup / action | Expected behavior and acceptance criterion | Policy dependencies |
|---|---|---|---|
| AC27-01 | Eligible Administrator creates and activates an account using approved flow | Allowed within two-role/identity rules; completed intended workflow has **active** status immediately; safe attribution, no default/public registration | R27-05-10/12-14/16 |
| AC27-02 | Verify status at completed create+activate boundary and any approved intermediate/error path | Active after completion; intermediate existence/status/atomic rollback match approved model, never invented autoactive create-only or forced inactivity after completed activation | R27-05/06/07 |
| AC27-03 | Pegawai tries account creation/activation/role/status management through direct requests | Denied securely, no record/role/status mutation or access via editable frontend settings | Confirmed denial; approved auth/test contract R27-10/11/14 |
| AC27-04 | Pegawai edits valid published content in authorized scope | Edit allowed without Administrator approval; visible/revision/version effects follow resolved policy, not assumed immediate public replacement | R27-01-03/12-14 |
| AC27-05 | Pegawai publishes valid authorized content directly | Allowed, no mandatory Administrator approval; scoped eligibility/validation and audit maintained | R27-01/02/03/12-14 |
| AC27-06 | Pegawai unpublishes authorized content | Allowed without Administrator approval; approved destination/visibility applied, record preserved as policy defines, no deletion inferred | R27-01/02/04/12-14 |
| AC27-07 | Pegawai attempts direct/bulk/flag/replace/delete; Administrator deletes in approved scope | Pegawai denied/no mutation; Administrator delete follows approved supported deletion/retention semantics, distinct from allowed unpublish | R27-01/06/12-14 and deletion policy |
| AC27-08 | Pegawai targets another employee's record or module outside approved authority | Cross-employee change denied unless scope policy explicitly authorizes; no blanket all-owner grant; permitted authorized target remains usable | R27-01, actual scope approval |
| AC27-09 | Inactive account in approved candidate states uses password or old session against protected operations | Denied/no mutation/status promotion; effective status/permissions server checked; old grants/sessions do not restore access | R27-06/10/11/13/14 |
| AC27-10 | Administrator/Pegawai submits invalid/third role or forged client-role/status | Only the two approved roles accepted through authorized admin operation; other values/escalation denied, no new role/partial mutation | R27-06/09/10/16 |
| AC27-11 | Approved synthetic credential setup/reset capability is expired, replayed, reissued or concurrently consumed | Reuse/expiry bypass denied under approved single-use/lifetime/reissue policy; no secret/status/permission/MFA bypass | R27-07-11/13/14 |
| AC27-12 | Capture logs/audit/ordinary responses using synthetic secret markers during credential and lifecycle actions | No passwords/reset/session/token secrets or paths/stacks; only approved secure credential delivery mechanisms carry necessary capability; attributable safe events | R27-07/08/12/14 |
| AC27-13 | Active account before completed credential establishment attempts login; revoked credentials/reset attempts | Active status alone does not issue session; valid established credentials/approved assurance required; setup/recovery cannot create roles/self-activate | R27-05/07/08/10/11 |
| AC27-14 | Role/status change, concurrent edit/unpublish and Administrator recovery fixtures | Approved revocation/version/state/recovery semantics observed; no stale unauthorized commit, old session resurrection or recovery privilege/status bypass | R27-01-04/06/09-12/13 |

Documentation acceptance requires scoped CONFIRMED rows, explicit open mechanics, two exact roles, no mandatory Administrator edit/publish/unpublish approval, no invented credential channel/provider/state machine, preserved previous files, secret-free content and single-file commit/push verification. These are not successful application tests. Future implementation also needs applicable gate closure, actual security/persistence regression execution and independent review.

## J. Updated Phase 3 readiness

| Area | Updated design/readiness assessment | What closes versus remains open |
|---|---|---|
| Role/content action permissions | **CONFIRMED within authority** | Published-edit and unpublish now granted to Pegawai; create/edit/direct publish/no-delete and no User Management retained; ownership/module/private scope open |
| Content lifecycle specification | **PARTIAL; implementation NO-GO** | Action authority settled, public edit/revision and unpublish destination/visibility/republish mechanics R27-01-04 incomplete |
| Account creation/activation | **CONFIRMED intended active outcome; PARTIAL technical lifecycle; implementation NO-GO** | After Administrator creates and activates, active immediately; atomicity/intermediate/full transition/error/credential coordination still open |
| Initial credential setup | **CONFIRMED direction; PARTIAL mechanism; implementation NO-GO** | Approved secure activation/reset direction chosen; channel/provider/expiry/password/abuse/recovery not approved or implemented |
| Server authorization | **PARTIAL design; implementation NO-GO** | Required positive/negative role/status directions refined, actual target/model/session/audit/auth contracts missing |
| MFA/authentication/session security | **NO-GO** | MFA undecided, Sanctum SPA recommendation and policy/security configuration still conditional; no package or operational auth implemented |
| MySQL/test isolation/recovery | **BLOCKED** | No verified access/DB/test target/connection evidence; no SQL/check/migration now |
| Origins/Cloud/deployment | **BLOCKED** | Domain/CORS/CSRF/cookie/security resources/ownership unverified; no new infrastructure approval |

Gate A/C gain precise design inputs, not PASS for implemented foundation. Audit/operational governance stays open. GO only for this explicitly authorized documentation phase; separately bounded non-executable preparation possible. No blanket authorization to begin Phase3 or conditional authorization to issue incomplete operational sessions. Actual implementation requires a new task and its closed dependencies; a later local slice need not wait for unrelated production Cloud resources but cannot bypass applicable security/DB/authority gates.

## K. Smallest next actions, dependency-first

1. **Owner decides content scope and behavior (R27-01-04):** whether cross-employee/module/unit authority exists, publication restrictions, immediate versus revision effect of published edits, and unpublish destination/republish semantics. Do not reopen the confirmed permissions or impose Administrator approval per action.
2. **Owner plus relevant security/process stakeholders specify account/credential flow (R27-05-10):** atomic versus separately recorded creation+activation and possible intermediate, exact state graph/reinstatement, secure activation/reset process/channel/proof/lifetime/single-use/abuse, first/last-admin recovery and session consequences. Preserve active outcome after completed admin flow; no assumed email/provider/invitation.
3. **Confirm security/infrastructure mandates and evidence (R27-11-15):** explicit MFA/credential/session/audit policies, approved origin/cookie/CSRF/CORS settings, privately managed MySQL/test access and authorized connectivity/recovery evidence; actual production ownership separately. No secrets requested or named appointments fabricated.
4. **Authorize one closed implementation slice (R27-16)** with exact prerequisites/files/test environment/exclusions. Resolve concrete decisions rather than producing unnecessary phases that repeat settled directions. No implementation scheduled by this specification.

### Verification and isolated publication

Only `backend/docs/PHASE-2.7-CONTENT-AND-ACCOUNT-LIFECYCLE-DECISIONS.md` is created. Check complete source/owner consistency, referenced paths, secret-free content/newline/whitespace, `git diff --check`, existing fingerprints/status and independent read-only review. Stage literal path only, inspect full staged diff and `git diff --cached --check`; commit `docs(pintu): finalize content and account lifecycle decisions` and normal push to `origin/laravel-development`. Verify single-file commit/parent, remote equality and preservation of all385 pre-existing outstanding paths. Stop for unsafe branch/remote/staging/isolation/hook/access; no add-all, cleanup/reset/restore/discard/amend/force or prior-document inclusion.

No PHP/TS/JS/HTML/CSS/UI/copy/dependency/runtime configuration/env/database changes; no authentication/MFA/authorization/User Management/content workflow/migration/credential delivery/audit implementation, tests, SQL/connection or deployment. Git publication is not release authorization or proof of external push automation. Actual verification/commit/push outcomes appear in the final report; Phase3 remains unstarted.
