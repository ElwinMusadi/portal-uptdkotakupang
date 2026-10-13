# PINTU — Phase 3 Identity and Persistence Foundation Scope

**Date:** 2026-10-12. **Status: BOUNDED IMPLEMENTATION PLAN — NOT AUTHORIZED OR STARTED.**

This scope follows the [Phase 2 decision register](PHASE-2-ARCHITECTURE-AND-API-DESIGN.md), especially D03 and D13-D17, and the historical [Phase 1.1 discovery](PHASE-1.1-SECURITY-AND-INTEGRATION-DISCOVERY.md). It defines future work packages, prerequisites and stop/go gates. The current task edits documentation only; none of the application work or tests below is implemented or executed.

## 1. Confirmed direction, recommendations and current baseline

| Classification | Current decision/status |
|---|---|
| **Confirmed user decision** | Identity source is local PINTU accounts managed by authorized administrators. No public self-registration by default; backend must verify identity; browser role/session/settings values are untrusted. |
| **Recommended architecture** | Sanctum cookie/session authentication for the first-party React SPA. Subject to approved domains/origins, CSRF, cookie, session-store and lifecycle configuration; no authentication package or integration currently implemented. |
| **Requires security approval — undecided** | MFA policy, coverage/factor, enrollment/recovery and authorized policy approver. Not approved, mandatory, optional, rejected or silently omitted. |
| **Requires organizational/security approval** | Who may provision/deactivate/reactivate accounts, assign/revoke permissions, bootstrap the first administrator, recover credentials and approve policy; actual organizational authorities unspecified. |
| **Technical blockers** | MySQL connectivity unverified; approved database access/credentials and isolated test database not established. Origin/session and identity schema/uniqueness requirements incomplete. |
| **Deferred safely** | Business roles and workflows, public content/file/calculator integration, browser business-data migration, full CRUD/bulk endpoints, deployment. Keep these excluded while approval is pending. |

Repository preflight: branch `laravel-development`; existing tracked changes in `.gitignore`, root/dashboard READMEs, Vite config, UIUX setup documentation and `index.html` preserved; no staged files. Existing untracked backend, handover, skill/Boost/Kilo configuration, `.vscode/` and scripts remain intact. Tracked binary diff fingerprint `cac31712e8536b629d9fec47bb290ddea1f9c29e`; current existing-content inventory excluding the Phase 2 document `43fbbc778c65d103f9bc6ac800d42c2e78f75571`. Final comparison excludes both authorized documents, preserving the same inventory order.

Read-only source inspection: `backend/routes/api.php` implements only `GET /api/health`, application health without DB/session; `backend/bootstrap/app.php` has no stateful/auth middleware customization. `backend/composer.json` has Laravel/Boost development tooling but no Sanctum dependency. Only scaffold `User.php` and three scaffold migrations exist; no verified identity database. Frontend `akun` is a settings singleton, not a trusted account collection or directory. Framework storage/web/health routes are not account APIs. This is source confirmation, not a new runtime test or connectivity check.

## 2. Hard scope boundary

Future Phase 3 requires a separate implementation instruction defining authorized packages, environment and tests after applicable gates are satisfied. Passing a gate is necessary, not implementation authorization.

Candidate in-scope work after approval: minimal server identity/status/credentials model; approved provisioning and lifecycle; Sanctum session/CSRF foundation; minimal identity-administration permissions; identity-related MySQL schema/session/audit support; isolated automated security tests. Exact routes/payloads, schemas, package changes and administrative interface are separate approved design artifacts, not fixed by this scope.

Excluded: public registration, organizational SSO migration, bearer-token issuance for other clients, full business RBAC matrices/endpoints, CMS publishing, public portal migration, attendance/message business data, calculator rules, object-storage/business uploads, bulk import/export/reset, browser identity migration, demo/default production accounts, frontend redesign/dependency upgrades, production release/provisioning. Future adapter changes may be technically necessary but need explicit authorization; no current frontend file is changed.

## 3. Package A — Identity and authentication foundation

### Prerequisites

- Confirmed D15 local-account direction plus separately approved minimal identity model: stable key, login identifier and normalization/uniqueness/reuse rules, account statuses and permitted lifecycle transitions. Default User scaffolding does not approve these choices.
- Defined authorized provisioning/deactivation/recovery process and attributable first-administrator bootstrap; obtain Gate 2's minimal identity-administration approval before exposing account management.
- Gate 1 details for origins, Sanctum/security configuration, password/session/recovery controls; Gate 3 approved persistence and test isolation before DB-dependent work.
- MFA gate addressed as below; do not complete a password-only implementation and call the undecided policy resolved.

### Future deliverables

| Subpackage | Deliverable/requirements to define before code | Approval still needed |
|---|---|---|
| A1 Stable local identity | Backend-generated stable identity independent of login/display names; explicit status, permission-assignment association and attributable lifecycle history. Reject browser `akun`/role/session claims. | ID format, username/email/NIP choice, uniqueness/case/rename/reuse, status vocabulary and any organizational scope |
| A2 Authorized provisioning | No anonymous self-registration. Reviewed operator request/identity verification, least-privilege initial assignment, secure activation/credential delivery, deactivation/reactivation; initial administrator and last-admin recovery documented. | Actual authorized stakeholders/approval evidence, administrative channel and bootstrap process; no invented job titles, default password or bypass |
| A3 Password handling/recovery | Framework-supported one-way adaptive hashing/verification; approved parameters/rehash and credential policy; no plaintext/reversible storage or secret logging. Verified recovery/reset flow, bounded single-use credential material, abuse controls and session consequences. | Hash/password policy, delivery/recovery channel/authority, validity limits, enumeration protection and recovery verification. Client email/NIP not proof of ownership; recovery must not bypass future MFA. |
| A4 Login/logout/session lifecycle | Server credential/status checks; rotate session on login; safe failure responses; server logout/invalidation; approved idle/absolute expiry, remember-device and current/all-session revocation. Enforce deactivation and permission changes on subsequent requests, not only at login. | Session limits/store, revocation guarantees/timing, password/recovery lifecycle, concurrent device and reactivation policy; prototype eight-hour/seven-day values not defaults |
| A5 Sanctum SPA transport | First-party cookie/session integration and protected middleware; credentials included for cross-origin requests, CSRF bootstrap/header handling, appropriate 419 recovery; explicit credentialed CORS, trusted hosts/proxies and Secure/HttpOnly/SameSite/domain/path policy. | Selected actual domains/origins including dev/staging/previews, stateful domain list, cookie scope/TLS, persistent session storage, safe auth endpoint contract and later adapter authorization |
| A6 Abuse protection/audit/status | Approved login/recovery throttling and enumeration resistance; inactive-account refusal; attributable provisioning, status, credential recovery, session and permission security events with safe metadata. | Thresholds/lockout policy, audit access/retention/minimization, status/revocation transaction semantics, first-admin attribution |
| A7 MFA policy gate | Separate approval record evaluating all-account/risk-sensitive coverage, phishing-resistant factor versus TOTP/other factor, enrollment/lost-device/recovery/step-up and support requirements. No MFA dependency selected here. | Organization must identify the authorized security approver and record an explicit decision; unresolved MFA cannot be described as optional/no-MFA approval |

### What the current decision permits versus blocks

The local-account source settles the direction for identity modeling and administrative rather than public enrollment. It does **not** settle schema, credential rules, login identifier, administrative authority, role grants, recovery, origin/session security or MFA. Only decision-neutral design refinement and acceptance-test specifications are dependency-independent preparation candidates under a subsequent narrow authorization. Application implementation remains unapproved in this task.

An MFA-neutral stable identity design may be separated from factor-specific work after its own approvals; do not add speculative factor columns/dependencies. An incomplete prototype cannot issue unrestricted operational sessions or be accepted as ready while MFA remains unresolved. Any separately requested isolated prototype must remain explicitly incomplete/non-production with no operational account access; this scope itself does not grant that exception.

### Planned verification criteria (not executed)

Verify approved credential/status checks, no public registration, session fixation protection and revocation, safe failures and throttling, recovery verification/single-use/expiry, no browser role trust, and MFA assurance behavior only after policy approval. Confirm plaintext credentials/session tokens never appear in responses/logs/audit. Acceptance blocked when delivery/recovery/authority or factor policy is undefined.

### Stop conditions

Stop the dependent subpackage if any required authority, credential/session rule, endpoint contract, MFA decision for authentication readiness, origin configuration or DB isolation is missing. Stop if implementation would use a default admin password, undocumented bootstrap bypass, browser identity import, or unchecked inactive account. Do not silently substitute bearer auth, SSO, shared credentials or omitted MFA.

## 4. Package B — Authorization foundation

### Prerequisites

- Authentication verifies an active server identity; authentication is not authorization.
- Business owner identifies who is authorized to designate provisioning and permission administrators. Approve the **minimal identity-management capability matrix** and change approval/audit process before those operations are exposed.
- Define self-service versus administrative actions, authorized target scope, privilege grant/revoke boundaries and escalation checks. MFA/step-up requirements cannot be inferred before D16 resolution.
- Business module roles, review/approval/publication and separation of duties remain unapproved and excluded. They need their own signed matrix before protected business operations, not an invented full matrix now.

### Future deliverables

1. Server-side deny-by-default policy and protected middleware foundation for the explicitly approved identity slice; no arbitrary role strings, implicit superuser or automatic content-authority inheritance.
2. Controlled server permission assignment/revocation, account status enforcement and protection against forged browser fields, direct API privilege escalation and cross-account access.
3. Attributable account/permission change events coupled to changes with approved consistency; safe audit representation without passwords, session IDs or recovery material. Specify behavior when persistence/audit cannot commit.
4. Approved admin bootstrap, delegated authority/self-grant restrictions, concurrent grants/revocation and session revalidation. No management endpoint until administrative approval is concrete.

### Planned verification criteria (not executed)

Direct-request tests for unauthenticated/inactive identities, authenticated-but-forbidden users, forged IDs/roles/unit fields, self-grants, cross-account manipulation, approved scope, concurrency/revocation and audit attribution. Every allowed identity operation must have corresponding negative coverage; hiding UI buttons is not a control.

### Stop conditions

Gate 2 is NO-GO until minimal identity administration is approved. Do not infer administrators from `akun.peran`, `User` scaffolding or workflow labels. Unapproved business permissions cannot become default grants. A complete business role matrix may be deferred only while no business capability is enabled; identity privilege boundaries cannot be deferred to enable provisioning.

## 5. Package C — Identity-related persistence readiness

### Prerequisites

- Approved existing/selected MySQL database access supplied privately: host/port/socket/TLS, engine/version, database selection/existence, approved username/authentication credential and grants. No root/blank credential guess or database creation inferred.
- Separate approved isolated MySQL test database and privileges, fail-closed configuration; tests must never fall back to production/operational DB. Disposable reset permission, if eventually needed, is explicit and isolated.
- Identity model and login uniqueness/normalization/reuse approved; permissions/status/session/audit persistence boundaries identified for the authorized slice. Existing User/users/session/reset-token migration does not prove schema fit or live tables.
- Migration review, staging rehearsal, recovery ownership and rollback expectations documented; backups/restore capability verified before any data-bearing environment change.

### Future deliverables

1. Safe separately authorized connection verification before persistence execution, distinguishing listener/configuration readiness from successful connectivity. No connection or SQL is performed now.
2. Minimal approved identity/account status, credential, assignment/session and audit schema design. Reuse scaffold only after suitability review; no domain collection tables, broader business schemas or speculative MFA fields.
3. Approved stable identity and login-key uniqueness/collation; explicit mutable-profile separation. The frontend `akun` singleton is not an account directory or server role source; no trust/import of its values or browser sessions.
4. Reviewed migration/backfill/upgrade and transaction plan for account/status/permission/audit consistency; isolated MySQL tests for uniqueness, foreign keys, concurrent lifecycle updates and revocation persistence.
5. Recovery plan distinguishing application rollback, schema compatibility and restoring data. A `down()` dropping a table can destroy records; no promise of automatic non-destructive reversal. Forward fix or protected staged restore must be assessed for the approved migration.

### Planned verification criteria (not executed)

Connectivity verified using approved access with a non-destructive check; test target identity proven isolated before mutations. MySQL-specific constraints/concurrency tests, reviewed migrations, recovery rehearsal and negative missing-test-config checks pass without touching operational data. No account automatically created from seed/client data. No password/hash/security grants emitted in ordinary DTOs.

### Stop conditions

Gate 3 is NO-GO without approved credentials, confirmed database/test isolation or model/recovery decisions. Stop before any SQL, migration, reset/seed or test teardown that might target an unapproved DB. SQLite health-test success is not MySQL persistence readiness. Identity source confirmation does not authorize accounts/schema to be created here.

## 6. Package D — Integration and security verification

### Prerequisites and future deliverables

Requires separately authorized implementation from A-C and approved request/response contracts. Backend-only test harnesses are preferable first; real adapter changes need explicit frontend scope. This task creates test requirements, not tests. Package D verifies the exact accepted scope and must not invent policies to make tests pass.

| Planned scenario | Required observable evidence |
|---|---|
| Successful/failed authentication | Backend-verified active local identity; approved safe errors for bad credentials/unknown users; no prototype-session trust or information disclosure |
| Inactive/deactivated/reactivated accounts | Login/status and subsequent request enforcement, approved revocation timing; reactivation does not silently restore invalid sessions/privileges |
| Unauthorized/direct API requests | 401 versus403/approved concealment; no role/object/field bypass; authenticated forbidden identities denied even without UI |
| Sessions, expiry, logout and recovery | Session rotation/fixation protection; current/all-device logout per approved policy; idle/absolute expiration, password recovery and privilege/status revocation behavior |
| CSRF/origins/cookies | Approved origin succeeds; unapproved origin/invalid or missing CSRF rejected; credentialed CORS/preflight and stateful-domain consistency; Secure/HttpOnly/SameSite/domain/path and cache behavior |
| MFA (after explicit approval only) | Policy-specific enrollment/challenge/assurance/recovery/privilege behavior; no unauthenticated or lower-assurance bypass. While unresolved, result is BLOCKED, not a passing no-MFA test. |
| Login/recovery abuse | Approved rate limits and safe lockout/enumeration behavior; no denial-of-service shortcut or guessed threshold |
| Credential/session leakage | No passwords/hashes/session IDs or credential material in ordinary JSON bodies, logs, audit or public artifacts; session/CSRF cookies and recovery delivery use only their approved secure mechanisms with correct attributes/access. No stacks, paths or environment disclosure. |
| Audit/account changes | Server actor/time/target and approved status/permission events; transactional attribution, restricted reads, approved retention/minimization; no client names treated as authoritative actors |
| Persistence/regression | Isolated MySQL identity constraints/concurrency/recovery tests; health API and Phase 1.1 serving tests remain valid. API transport shape respected; no unrequested UI/build changes. |

Future test layers: narrow PHPUnit feature/security tests, approved MySQL integration tests, contract tests and controlled-local browser verification only if frontend integration/browser tooling is authorized. Current foundation health/serving results are historical and do not prove login/security work exists. No PHPUnit, browser, integration test or database check runs during this documentation task.

### Stop conditions

No security readiness sign-off with missing policy expected-results, unresolved MFA, insufficient permissions, unverified DB isolation or untested recovery/revocation. Stop if tests require exposing private internals, disabling CSRF/CORS restrictions, using real production credentials, changing UI or enabling business operations outside scope. Record BLOCKED, not PASS, for unexecuted scenarios.

## 7. Stop/go gates and current outcome

| Gate | GO evidence required before the dependent package | Current state and permitted limitation |
|---|---|---|
| **Gate 1 — architecture prerequisites** | D15 local accounts confirmed; lifecycle/auth/recovery/abuse requirements agreed; approved concrete domain/origin, Sanctum CSRF/cookie/session details; MFA explicitly tracked and authorized security approver designated. Full authentication-readiness acceptance additionally requires their explicit MFA policy decision. | **PARTIAL / NO-GO for dependent auth implementation/readiness.** Only identity source is confirmed. Origins/lifecycle/MFA authority/policy remain unresolved. Decision-neutral planning can be separately authorized; no production/password-only default inferred. |
| **Gate 2 — authorization prerequisites** | Organization defines authorized provisioning and permission-management actors/process/bootstrap and approves minimal identity-administration scope. Required business roles/duties approved before any protected business operation. | **NO-GO for provisioning/permission management or protected business operations.** No specific administrator position or permission matrix approved. Broader business matrix safely deferred only while business operations excluded. |
| **Gate 3 — database prerequisites** | Approved credentials/access and confirmed isolated MySQL test DB; safe connectivity verification separately authorized; approved identity uniqueness/schema/migration and rollback/data recovery expectations. | **NO-GO for SQL/persistence/migrations/database-dependent tests.** Connectivity unverified; access/test isolation not proven. No guessed credentials or live DB operation in this task. |

**Partial release rule:** approve each subpackage's dependency list and excluded capabilities in a written scope before future code. A blocked gate limits dependent work; it is not permission to implement another layer implicitly. MFA-neutral schema/planning must not add speculative factors or claim a complete authentication posture. Any isolated non-production prototype needs its own authorization and explicit incomplete-risk boundary; not granted here.

**Implementation authorization gate:** after relevant prerequisites are satisfied, the user must explicitly authorize the exact Phase 3 package(s), file/config/package changes and test environment. A document, installed Boost/skills, or approved identity source does not authorize running migrations or writing authentication/RBAC/frontend code. Production release remains separately gated and excluded.

## 8. Open decisions, stakeholder designation and safe deferrals

| Open decision | Stakeholder to designate/consult; no invented position | Blocks / safe deferral |
|---|---|---|
| Actual authorized provisioning/permission administrator and approval chain, first/last administrator process | Business owner must identify authorized administrative actors and decision authority | Blocks A2/B and operational account creation |
| MFA policy and authorized policy approver | Organization must designate the authorized security decision stakeholder; security/operations provide analysis | Blocks full authentication/security readiness; cannot silently defer by choosing no MFA |
| Login identifier, stable ID, uniqueness/reuse/status and profile rules | Product/identity data owner plus backend/security reviewer, actual authority to be designated | Blocks approved identity schema/A1/C |
| Password, initial delivery and recovery verification/channel/expiry | Designated identity/security authority and operator responsible for recovery | Blocks A3 and lifecycle readiness; no automatic email/SMS provider assumption |
| Domain/origin, cookie/session/CORS/CSRF, shared store and revocation lifetime | Infrastructure/domain owner plus designated security authority | Blocks A4-A5 and meaningful cross-origin verification |
| Minimal identity grants/role administration and security-event/retention rules | Designated administrative decision owner and security/governance reviewer | Blocks B/account/permission changes and audit acceptance; full business role matrix separately deferred |
| MySQL/test access, schema/migration/recovery privileges and safe restore | DBA/infrastructure/data custodian function; actual authorized person not specified | Blocks C and DB-dependent D; supply secrets outside docs |
| Business workflow roles, content authority, migration, uploads/tax/publishing | Business-specific owners identified in Phase 2 register | Safely deferred because all these capabilities remain outside Phase 3 scope |

## 9. Future acceptance criteria and handover

Before implementation: applicable Gate 1-3 evidence and explicit authorization recorded; approved identity/lifecycle/auth/admin/schema/test contracts; every unresolved decision has an exclusion/blocker; MFA not silently omitted; no bootstrap/default account or browser-derived privilege assumption.

Before accepting an implemented foundation: approved package deliverables and negative tests pass; identity/status/permissions backend-authoritative; approved credential/session/recovery/MFA posture verified; no public self-registration; scoped attributable admin changes; isolated MySQL persistence/recovery evidence; no secrets; health and serving regression maintained. No package claims completion for unexecuted tests or deferred required security policy. Any temporary prototype is labeled incomplete and cannot be production authentication acceptance.

Handover records approved policies and authorized stakeholders, exact scope/schema/route/config changes, tests and environment, remaining blockers, recovery instructions and exclusions. Production deployment, public/frontend integration and protected business operations need separate approval. No whole-architecture approval is inferred from D15.

## 10. Documentation-only verification and preservation

This task changes exactly two Markdown files: updates Phase 2 and adds this scope. Phase 1.1 report remains historical and unchanged. Preflight read actual routes/bootstrap/dependency manifest and default model/migration directories; no runtime file changed. No PHP/package/environment/frontend/test implementation, MySQL connection/SQL/migration, seed/reset, commit/push/deploy or destructive Git operation occurs.

Verification: `git diff --check`, complete document inspection, branch/status/staged checks and identical pre-existing inventory/diff fingerprints with both authorized docs excluded. Local environment compared privately without credential contents. New document checked separately for trailing whitespace/newline because ordinary `git diff --check` does not include untracked additions. Planned Phase 3 tests are not executed. Final report records actual documentation verification, not application feature success.

Results: **PASS** for Git whitespace check, both documents' trailing-whitespace/final-newline checks and unchanged pre-existing content/diff fingerprints. Branch remains `laravel-development`; nothing staged. Independent read-only architecture/security review: **PASS**, no material findings. All package verification scenarios remain planned and unexecuted; Gates 1-3 retain the outcomes in Section 7 and no implementation has started.
