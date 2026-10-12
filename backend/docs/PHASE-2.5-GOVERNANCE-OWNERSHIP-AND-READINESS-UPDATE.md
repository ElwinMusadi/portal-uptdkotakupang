# PINTU — Governance Ownership and Readiness Update

**Phase:** 2.5. **Date:** 2026-10-12. **Status:** confirmed ownership/two-role decisions and scoped readiness update; documentation only, no Phase 3 implementation authorization.

## 1. Executive summary and evidence of confirmation

The current user instruction explicitly confirms that the project owner is also the application Administrator. Record this owner as **`PINTU Project Owner (Application Administrator)`**. No legal name, official job title or appointment is verified or inferred from Git authors, repository account names, prototype profiles or organizational labels.

**Exactly two application roles are authorized for current scope: `Administrator` and `Pegawai UPTD`.** Administrator has full application access, including creating/managing user accounts and managing content. Pegawai UPTD manages portal content within the permissions assigned to that role, without full administrative privileges or User Management. Backend authorization must enforce these boundaries; browser labels/session/profile settings do not create authority. No additional application role is authorized without explicit scope approval.

This resolves application ownership, User Management responsibility and top-level role boundaries. It does **not** approve detailed lifecycle/bootstrap/recovery procedures, granular Pegawai action grants, authentication architecture/configuration, MFA/session policy, MySQL access, deployment topology or security-risk acceptance. Application roles and project-level security/infrastructure responsibilities remain distinct.

This document is an explicit **additive update to the Phase 2.4 register**, not a silent rewrite or assertion that its historical unknowns were always resolved. Existing documents remain intact. Where earlier register text says application administrator or broad role model is unidentified, this confirmation resolves only those named portions; other authority/policy/evidence gaps continue.

## 2. Verified repository baseline and preceding evidence

Preflight: `laravel-development`, local/remote tip `356d511265206926f4ca84dfc714df951cee83bb`, no staged changes, no divergence. Working tree remains **385 outstanding paths: 6 tracked modifications + 379 untracked files**, matching Phase 2.3/2.4. New Phase 2.5 path did not exist. No cleanup or source change is needed to isolate this phase.

Existing tracked diff fingerprint: `cac31712e8536b629d9fec47bb290ddea1f9c29e`. Existing-file inventory fingerprint in Git output order: `3388184ada73433e1488ffd3767068f47befdf48`. Final preservation comparison excludes only this new document. Count/fingerprint changes from older reports' pre-creation inventories are explained by subsequent committed phase documents, not an authorization or cleanup.

| Evidence | Source and relevant content | Publication / interpretation |
|---|---|---|
| Current user confirmation | Ownership, exactly two roles, Administrator full application/User Management/content access, Pegawai limited content access, backend enforcement | Explicit approval of those statements only; not actual deployed users, role middleware or permissions |
| Phase 2.4 | `backend/docs/PHASE-2.4-GOVERNANCE-DECISIONS-AND-PHASE-3-AUTHORIZATION.md`, GOV-01/02/04/05/07/08/10-16 and activity matrix | Tracked/remote; baseline register updated by the scoped deltas here, earlier file unchanged |
| Phase 2.2 | `backend/docs/PHASE-2.2-SECURITY-AND-IDENTITY-LIFECYCLE-SPECIFICATION.md`, lifecycle L1-L11, BOOT, MFA, ADM, P1-P11 | Tracked/remote; proposed control/test requirements not implemented or automatically approved |
| Readiness gates | `backend/docs/PHASE-3-READINESS-GATE.md`, Gates A-E | Tracked/remote; partial governance closure below, no blanket gate pass |
| Bounded Phase 3 scope | `backend/docs/PHASE-3-IDENTITY-AND-PERSISTENCE-FOUNDATION-SCOPE.md`, Packages A-D | Local/untracked; actor identity now confirmed in application context, technical prerequisites still open |
| Phase 2 architecture | `backend/docs/PHASE-2-ARCHITECTURE-AND-API-DESIGN.md`, D03/D04/D15-D17 | Local/untracked; local identity remains confirmed, Sanctum proposed and MFA unresolved; old illustrative role bundles cannot authorize more application roles |
| Working-tree audit | `backend/docs/PHASE-2.3-WORKING-TREE-AND-PHASE-3-READINESS.md` | Tracked/remote; preservation/publication boundary and 385-path explanation retained |

These current documents were inspected directly or through a read-only architectural review. Repository-relative references exist locally; the two local/untracked predecessors above are not included in this commit or promised to exist in clean remote checkout. Foundation source and other earlier local artifacts remain untracked/preserved. No runtime test, database connectivity or external appointment verification was performed. Confirmed role **design** is not a claim that server-side roles exist today.

## 3. Confirmed project ownership and application responsibilities

| Confirmed item | Responsible identity / decision | Limit of approval |
|---|---|---|
| Project ownership and application Administrator | **PINTU Project Owner (Application Administrator)** | No fabricated legal name, government job title or security/infrastructure appointment |
| User Management | Administrator, including creating/managing application user accounts | Workflow/verification/delivery/status/recovery safeguards and actual implementation still require approval and readiness |
| Content management | Administrator full application access; Pegawai UPTD within assigned content permissions | Pegawai per-module/action/object scope and workflow assignment details not yet enumerated |
| Server enforcement | Backend validates identity/status/role and relevant permissions independently of frontend state | Requires future explicitly authorized implementation, contracts and negative tests; not satisfied by editable role text |
| Role vocabulary | Exactly `Administrator` and `Pegawai UPTD` | New roles require explicit scope approval; no inferred editor/reviewer/approver/auditor/infrastructure application role |

Two roles does **not** mean two user accounts, one shared account or exactly one Administrator account. The owner holds Administrator responsibility; the number of additional accounts/Administrator holders, delegation, first/last-admin continuity and role-change process remain explicit decisions. Do not create accounts, assign users or import browser `akun` values from this document.

## 4. Two-role permission boundaries

The table records only confirmed access boundaries, not endpoint definitions, permission names, schema or a complete content action matrix.

| Capability | Administrator | Pegawai UPTD | Unresolved implementation/policy detail |
|---|---|---|---|
| Full application access | **Confirmed** | **Not granted** | Access remains subject to valid account/status/session and applicable approved security controls |
| User Management: create/manage user accounts | **Confirmed** | **Not granted** | Account lifecycle/role assignment/verification/bootstrap/recovery/concurrency/audit process still to be specified; no privilege path through content API |
| Portal content management | **Confirmed, full application content access** | **Confirmed within assigned permissions** | Explicit Pegawai module/action/object and ownership limits, including edit/delete/publish/archive, need owner's approval; no blanket grant inferred |
| Full administrative privilege or role/permission administration | Within Administrator's confirmed full application access | **Not granted** | Technical writable fields, grant/change safeguards, continuity and audit are not bypassed; no client role injection |
| Security-policy/risk acceptance, direct MySQL/OS/Cloud access | **Not established by application role** | **Not established by application role** | Requires separate responsibility/authority confirmation and verified resources, not another application role |

**Full application access is not a security exemption.** It does not waive password verification, account status, CSRF, origin/cookie/session controls, future explicitly approved MFA, security audit integrity or legal/retention rules that may later be approved. It does not authorize infrastructure superuser access, reading stored passwords or erasing authoritative security evidence. Those are protection/governance requirements, not a restriction invented to remove the confirmed normal Administrator application privileges.

Earlier references to content suppliers/reviewers/approvers are workflow functions/labels, not permission to create additional roles. Any approved workflow function must map into the two-role model through an explicitly approved action/assignment design, or require a future scope decision; no third role is added. Detailed duties/self-actions/exceptions are unresolved rather than an invented mandatory multi-person process. Limited staffing is a governance question, not justification to bypass protections.

## 5. Additive governance register update

Use existing GOV IDs with scoped sub-decisions so partially resolved rows are not falsely marked entirely APPROVED. Status vocabulary remains `APPROVED`, `UNDECIDED`, `PROPOSED`, `BLOCKED`, `REJECTED`; no rejection of a specific security option is inferred. OWN IDs below record current explicit confirmation as evidence.

| New confirmation ID | Precise decision / status | Approval evidence |
|---|---|---|
| **OWN-01 — APPROVED** | Project owner is application Administrator, identified as PINTU Project Owner (Application Administrator) | Current explicit user confirmation, 2026-10-12; application ownership only |
| **OWN-02 — APPROVED** | Exactly two authorized current application roles: Administrator and Pegawai UPTD; no extra roles without scope approval | Current explicit user confirmation; number of accounts/Administrator holders not set |
| **OWN-03 — APPROVED** | Administrator full application access, including User Management and content; Pegawai content within assigned permissions, no full admin/User Management | Current explicit user confirmation; granular Pegawai grants and technical safeguards not finalized |
| **OWN-04 — APPROVED** | These permissions require server enforcement; client state/role labels not authoritative | Current instruction and established backend-authority direction; enforcement remains unimplemented |

| Phase 2.4 entry / scoped delta | What is now resolved | What remains unresolved / evidence needed |
|---|---|---|
| **GOV-01.application — APPROVED**; broader GOV-01 remains **BLOCKED** in other mandates | Application owner/Administrator identity and responsibility established by OWN-01 | Security/MFA risk approver, infrastructure/domain/database/deployment/operational authorities: **NOT IDENTIFIED — OWNER CONFIRMATION REQUIRED**. Owner may confirm their separately intended mandates or designate others; no automatic appointment implied. |
| **GOV-02 — APPROVED**, confirmation extended | Local-account source/no public registration/backend authority retained; OWN-01-04 fix application ownership/two-role direction | No auth/security/model/production implementation approved |
| **GOV-04.account-manager — APPROVED**; lifecycle process remains **UNDECIDED** | Administrator owns User Management; owner is a confirmed Administrator | Define L1-L11 verification/activation/credential recovery/status/revocation/reactivation/removal/delivery and exceptions; no complete process from a role label |
| **GOV-05 — PROPOSED**, intended administrator identified | Known owner is intended application Administrator; no unknown app-role recipient need be invented | First-account identity verification, authorized bootstrap performer/channel/custody/replay/last-admin recovery still unapproved; no executed/provisioned account or default credential |
| **GOV-10.role-boundaries — APPROVED**; detailed grants/duties remain **UNDECIDED** | Exactly two roles and broad Administrator/Pegawai boundary established, Administrator also manages content | Owner confirms Pegawai content module/action/object matrix and role-change/duty safeguards; assigned permissions not yet enumerated. No reviewer/publisher/auditor extra role inferred. |
| **GOV-03/06/09 — UNDECIDED** | Owner can provide application requirements; no login/hash/session parameter selected | Login uniqueness/stable identity/status, credential/recovery policy, session/abuse/revocation rules and security approval where applicable |
| **GOV-07 — UNDECIDED** | No MFA coverage/factor/deferral approved by ownership confirmation | Authorized security approver still **NOT IDENTIFIED — OWNER CONFIRMATION REQUIRED**; explicit policy/risk decision and recovery/support evidence |
| **GOV-08 — PROPOSED** | Sanctum SPA cookie/session recommendation unchanged | Formal adoption, exact authentication contract and approved security/topology/session configuration; no Sanctum implementation |
| **GOV-11/12/15 — BLOCKED** | No new domain, connection or Cloud evidence supplied | Approved origins/TLS/CORS/CSRF/cookies; MySQL access/grants/test DB/connectivity; Cloud resources/ownership and release responsibility separately verified |
| **GOV-13/14 — UNDECIDED** | Two-role identity/admin direction informs later schema/audit design | Reviewed identity/grant/session/audit persistence/migration/restore; safe events/read/write/retention/minimization and operational custodians. Full application access does not approve retention/disposition policy. |
| **GOV-16 — UNDECIDED** for Phase 3 | Current task explicitly authorizes this new document/verification/commit/push only | New bounded implementation authorization after its prerequisites; no code authorization from role confirmation |

The Phase 2.4 register is **not modified** here. Future maintenance should cross-reference OWN-01-04 and the scoped GOV deltas instead of repeating that the application Administrator remains unknown. Likewise Phase 2/3 documents' historical unknown role/actor wording needs a separately authorized consistency update before implementation. Keep unresolved security/infrastructure markers; do not overwrite historical approvals or quietly promote proposed Sanctum/MFA values. No extra prior document is staged in this phase.

## 6. Readiness reassessment and blocker closure evidence

| Area | Updated assessment | Evidence required before dependent implementation |
|---|---|---|
| Application role model and User Management owner | **RESOLVED at top-level design:** owner/Administrator and exactly two-role broad permissions confirmed | Granular Pegawai grants and account/role lifecycle model still needed; no real user assignments or code |
| Account lifecycle / bootstrap (Gate A) | **PARTIAL:** User Management owner identified, procedures not closed | Owner-approved operational process/identity verification, initialization/recovery channel, statuses/deactivation/reactivation, controlled bootstrap and last-admin continuity, audit/session effects and relevant security approval |
| Sanctum authentication (Gate B) | **NO-GO:** still recommended, not approved/configured | Authorized adoption and contracts; exact trusted stateful origins/TLS/CORS/CSRF/cookie/session strategy, lifecycle and assurance readiness |
| MFA/session security (Gate B) | **NO-GO:** MFA undecided; lifetimes/revocation/abuse values open | Identify security-policy/risk approver, choose coverage/factor or permitted reviewed risk deferral explicitly; approved session/recovery/step-up/abuse/revocation and persistent-store rules |
| Server authorization (Gate C) | **PARTIAL design closure; NO-GO for implementation:** two roles/User Management restriction settled | Explicit Pegawai action/target/field scope; Administrator account/grant changes and workflow duty/exception safeguards; authoritative status/role checks, revocation/audit requirements and authorized implementation |
| MySQL/test database (Gate D) | **BLOCKED:** no connection or isolated test target verified | Approved custodian/access/grants/network/TLS, selected DB existence, dedicated test target/fail-closed isolation, separately authorized non-destructive connectivity check and reviewed identity schema/migration/restore |
| Origins/CORS/CSRF/cookies (Gate E) | **BLOCKED:** no actual topology/configuration approval | Domain/infrastructure and security responsibilities identified; approved dev/staging/production origins/cookie scope/attributes and CSRF/credentialed CORS mappings, stateful domains and parity verification |
| Deployment/Cloud (Gate E) | **BLOCKED:** platform target, resources/owner/release not verified | Explicit deployment/infrastructure mandate, actual account/environment/network/resources/public entrypoint/session/cache/secret isolation, backups/monitoring/incident/release and canary approval |
| Audit/operations | **OPEN:** actor identity direction improved, control/retention/custody incomplete | Approved events/attribution/safe fields, write consistency/read scope, credential/sensitive-data handling/retention/restore, custodianship and operational responsibility; no secrets in approval records |

Authority for the application owner is now evidenced; the same phrase must not be used to claim the owner is unidentified in that capacity. Security and infrastructure mandates remain **NOT IDENTIFIED — OWNER CONFIRMATION REQUIRED** unless explicit later evidence names an authorized responsibility. Designating security reviewers, risk acceptors, DBA or release operators does not create new application roles or give Pegawai User Management.

### Remaining-decision evidence packet

For each unresolved topic, record exact statement/option, scope/environment, authorized decision-maker and their separately confirmed mandate, rationale, non-secret evidence, permitted exceptions, acceptance/negative tests, residual risk and review triggers. Owner may specify Pegawai grants and application process proposals now; security-sensitive acceptance and external custody must still be confirmed in the proper capacity. Supply operational credentials only via approved secure channels outside this document/Git/chat; none are requested here.

MFA options remain all internal users, privileged users first with an explicit policy for other users, or permitted explicit risk-accepted deferral with scope/controls/review/release limits. Administrator is a confirmed privileged application role, but this does not choose that MFA option or exempt Pegawai. No optional/mandatory/no-MFA policy is inferred.

## 7. Updated Phase 3 authorization matrix

GO means this precise activity has current authorization/evidence. CONDITIONAL GO permits only explicitly bounded, separately instructed decision/design preparation without enabling operational capabilities. NO-GO means required policy/security/environment/implementation authorization remains missing. A resolved two-role requirement is not an application implementation instruction.

| Activity | Current outcome | Now resolved and still required |
|---|---|---|
| This Phase 2.5 document/verification/isolated publication | **GO** | Explicit user instruction and verified safe branch/path/baseline; no prior deliverable edits |
| Independent local role/process/security design and test specifications | **CONDITIONAL GO** | Model proposals can now use only Administrator/Pegawai UPTD and known owner; exact separate task bounds, no speculative grants or policy selection |
| Authentication/session implementation | **NO-GO** | Owner/local identity known; credential/status/session/MFA/security-origin/persistence prerequisites and new scope still absent |
| MFA implementation | **NO-GO** | Role candidates known, MFA policy/authorized approver/factor/recovery still undecided; no dependency/code |
| Account management and server authorization implementation | **NO-GO** | Administrator User Management and Pegawai exclusion confirmed; lifecycle/grant/process/audit/assurance/persistence and explicit code scope not approved |
| Pegawai content authorization/business API integration | **NO-GO** | Limited assigned content access confirmed; explicit operations/objects/workflow permissions, data contracts and business slice authorization still required |
| Migrations/persistence and MySQL integration tests | **NO-GO** | Two-role direction can inform conceptual schema; actual model/access/test isolation/connection/recovery and execution authorization not verified |
| Deployment/production canary | **NO-GO** | Application owner is not automatically Cloud/DB/security-risk operator; actual infrastructure/domains/resources/release/security/test evidence and release authorization required |

Not all preparation waits for production hosting: approved non-executable designs can proceed without live Cloud/MySQL. Future narrowly authorized local implementation could close only its relevant slice's prerequisites, but cannot infer operational authentication or production readiness from an incomplete prototype. No blanket Phase 3 authorization is issued.

## 8. Recommended next action and separate future updates

**Next action by PINTU Project Owner (Application Administrator):** approve a concrete `Pegawai UPTD` content permission matrix (module, action, target/ownership scope, workflow assignment and any exceptions) and account/role-management process using the confirmed two roles. Explicitly distinguish allowed create/edit/delete/publish/archive operations from unassigned operations without adding roles. Record Administrator lifecycle/role-change/continuity and bootstrap/recovery expectations as application requirements, not shared credentials or invented security policies.

In parallel, confirm who holds the **separate security-policy/MFA risk-acceptance and infrastructure/domain/MySQL/Cloud/operations mandates**, including whether the owner intends to assume a named responsibility; do not assume it merely from application Administrator. Those mandated stakeholders then decide security configuration/MFA and privately establish environment/test access evidence. No credentials requested and no appointments fabricated.

After decisions are explicit, separately authorize consistency updates to GOV-01/04/05/10 and earlier architecture/scope wording using the scoped deltas, then a bounded implementation task with approved requirements/file list/test targets/exclusions. No retrospective whole-register approval, new roles, source cleanup, broad staging or production release is triggered by this phase.

## 9. Verification and isolated publication boundary

Only `backend/docs/PHASE-2.5-GOVERNANCE-OWNERSHIP-AND-READINESS-UPDATE.md` is created. All previous documents/runtime/UI/copy/dependencies/config/environment/database and 385 outstanding paths remain unchanged. No authentication/MFA/RBAC/user endpoint/migration/test or infrastructure operation is performed. Existing ignored files stay untouched; no secret values or legal identity are read/inferred/published.

Verify full document/source consistency, referenced paths, secret-free content, newline/whitespace, `git diff --check` and identical existing tracked diff/content fingerprints excluding this document. Stage literal path only, inspect staged diff and `git diff --cached --check`, commit `docs(pintu): record governance ownership and readiness update`, and ordinary push to `origin/laravel-development`; verify commit/file list/remote tip and restored 385 outstanding paths. Stop on wrong branch, unexpected staging, divergence or failed isolation/hook/permission; no add-all, reset/clean/restore/amend/force or unrelated file inclusion. Git publication does not authorize deployment or change unverified external push automation.

**Final verdict:** application ownership and broad two-role/User Management boundaries are now confirmed; specific lifecycle/security/infrastructure gates remain open. Phase 3 functionality has not started. Commit/push outcomes are reported after actual verification, not invented in advance.
