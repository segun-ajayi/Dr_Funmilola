# S7 — Continuous Journeys and Release Handoff Plan

Recorded: 26 September 2026
Status: active implementation source of truth; S7.1 in progress
Acceptance scope: VE-232 through VE-241 implementation/runtime evidence and the complete VE-001 through VE-243 evidence handoff; VE-242 and VE-243 remain independent-QA decisions

## Objective

Finish the implementation-agent portion of the true in-place visual editor rebuild with uninterrupted actual-site journeys, the complete role and direct-API denial matrix, fresh backend/web/native verification, and one honest evidence record for every VE identifier. This phase may establish readiness for independent retest, but it must not issue the independent visual-editor confirmation or release recommendation.

## Guardrails

- Use local development data and clearly unique QA markers. Capture the prior published state before each journey and use the supported rollback flow after publication evidence so the local demonstration site is not left with test copy.
- Exercise controls on the actual rendered public route in Edit Mode. A dashboard-only, isolated component or direct API test is not a substitute for VE-232, VE-233 or VE-234.
- Keep VE-232, VE-233 and VE-234 uninterrupted from sign-in through logged-out public verification. If the browser session, server or operation fails, record the interruption and restart that journey from its beginning rather than joining partial observations.
- Preview, publication and rollback must each be deliberate and truthfully confirmed. Never infer a successful public change from a request alone.
- Use the existing verified local demonstration accounts only. Never copy their seed password into evidence, screenshots, logs or source code.
- Forbidden-role checks must prove both denial and absence of mutation. Public, Patient, Moderator and Admin receive no structural CMS capability; only verified Power Admin succeeds.
- Row evidence must identify revision, test or runtime environment, route/surface and observed result. Automated implementation proof may be labelled implemented/verified locally, but it is not independent acceptance.
- Preserve the independent boundary: physical-device, named screen-reader and external reviewer observations remain explicit pending items. VE-242 and VE-243, QA-002 and QA-003 cannot be self-approved.
- Each bounded S7 task is tested, committed, pushed and recorded here before the next task starts.

## Delivery tasks

### S7.1 — Uninterrupted actual-site content journeys

- VE-232: sign in as Power Admin, open an actual public page, enter Edit Mode, double-click a visible heading, change its exact text and font styling, save, fully refresh, verify private draft persistence, preview, publish, sign out and verify the exact public result.
- VE-233: in a fresh uninterrupted journey, select a visible rendered image, replace it with a managed library asset, change alt text, save, fully refresh, verify persistence, preview, publish, sign out and verify image plus alt text publicly.
- VE-234: in a fresh uninterrupted journey, add a section through the actual-page component chooser, edit content and presentation, move its order using the rendered editor controls, save, fully refresh, verify content/presentation/order, preview, publish, sign out and verify the exact public result.
- VE-235: use the visible supported history/rollback path after one of the publications and confirm the logged-out route exactly returns to the captured prior published state.
- Record each journey separately with the route, unique marker, browser/runtime environment, observable checkpoints and restoration result. Screenshots may support but never replace semantic checks.

### S7.2 — Complete authorization journey

- Exercise UI visibility/route entry as Public, Patient, Moderator, Admin and Power Admin for actual-page Edit Mode, the CMS manager and history/rollback surfaces.
- Exercise direct API access for page load, atomic save, preview, publish, media list/upload boundary, navigation/settings mutation and rollback across the same roles.
- Prove every forbidden attempt returns the correct denial without changing page, media, navigation, version or published state. Prove the Power Admin path succeeds with valid payloads.
- Add or extend deterministic feature/frontend coverage wherever an authorization branch or no-mutation assertion is not already explicit.
- Run focused role/API and editor tests before committing the task.

### S7.3 — Evidence reconciliation and final implementation gate

- Reconcile exactly one evidence record for every VE-001 through VE-243. Link fresh S1–S7 revisions and tests only where they directly prove the row wording.
- Mark implementation/runtime results separately from independent acceptance. Leave physical-device, screen-reader and independent-review rows pending or blocked with the precise external requirement.
- Recheck that the evidence set contains every ID exactly once with no duplicates and that VE-242/VE-243 are not self-certified.
- Run the complete backend, web and native test suites, strict web/shared/native TypeScript checks, production web build, Expo Android/iOS public configuration/export validation where locally supported, changed-file formatting and `git diff --check`.
- Update the implementation plan and remediation progress with revisions, results, environment limits, remaining blockers and the exact independent-QA handoff.

## Test gates

Every S7 task must prove:

- exact draft content remains private before publication and survives a complete reload;
- preview, editor and logged-out public renderers agree for the same structured document;
- publication is explicit, exact and versioned, and rollback restores the exact previous publication;
- forbidden roles cannot see or enter Edit Mode and cannot mutate through direct APIs;
- browser journeys use the actual rendered page and record any interruption honestly;
- no seed password, bearer token, cookie, page body or unrelated private data enters evidence;
- all automated suites relevant to the task pass before commit and push.

## Completion rule

S7 implementation work is complete when VE-232 through VE-236 have fresh, separate, uninterrupted local runtime records; the full regression and production-build gate passes; and exactly one honest evidence record exists for VE-001 through VE-243. Completion means the implementation is handed off for independent retest. It does not mean VE-242 or VE-243 passed, and it does not authorize this implementation agent to mark QA-002 or QA-003 PASS or recommend release.

## Implementation ledger

### S7.1 — In progress

- Activated on 26 September 2026 after S6.3 revision `05bf35a` and completion record `7e56e36` were pushed.
- Planned route: use an actual local public CMS route with unique QA markers and restore its prior publication through the supported rollback workflow after observable logged-out verification.
- Evidence boundary: local browser execution is implementation/runtime evidence. Independent physical-device, named screen-reader and external-review results remain pending.

### S7.2 — Not started

### S7.3 — Not started
