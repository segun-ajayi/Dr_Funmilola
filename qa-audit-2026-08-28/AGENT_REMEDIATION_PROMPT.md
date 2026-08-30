# Prompt for the Original App-Building Agent

Continue building and remediating the Dr. Funmilola application in the existing repository and working history. You are the same implementation agent that built the app and performed earlier remediation, so use your existing context, code knowledge, commits, and evidence—but do not assume earlier implementation is sufficient merely because it exists or previously passed local tests.

Your job is to close the complete independent QA inventory without skipping a finding or requirement, and to deliver the newly specified **true in-place visual website editor** exactly as required. Work through implementation, migrations, automated tests, production-like runtime checks, evidence, and progress recording. Do not stop after analysis, a plan, scaffolding, or partial UI.

## Authoritative files

Read these files completely before changing code:

1. `qa-audit-2026-08-28/QA_EXECUTIVE_SUMMARY.md`
2. `qa-audit-2026-08-28/QA_FINDINGS.md`
3. `qa-audit-2026-08-28/QA_FINDINGS.csv`
4. `qa-audit-2026-08-28/REQUIREMENTS_TRACEABILITY_MATRIX.md`
5. `qa-audit-2026-08-28/FINDINGS_REMEDIATION_PLAN.md`
6. `qa-audit-2026-08-28/TRUE_IN_PLACE_VISUAL_EDITOR_ACCEPTANCE_CHECKLIST.md`
7. `qa-audit-2026-08-28/REMEDIATION_PROGRESS.md`
8. `qa-audit-2026-08-28/RETEST_PLAN.md`
9. `qa-audit-2026-08-28/RELEASE_CHECKLIST.md`
10. All remaining security, accessibility, responsive, content, UI/UX, coverage, and discovery reports in that folder.

Treat `QA_FINDINGS.csv` as the canonical finding inventory, `FINDINGS_REMEDIATION_PLAN.md` as the required dependency order, `TRUE_IN_PLACE_VISUAL_EDITOR_ACCEPTANCE_CHECKLIST.md` as the atomic release gate for QA-002/QA-003, and `REMEDIATION_PROGRESS.md` as the status/evidence ledger.

Do not edit the original QA reports or traceability matrix to make a defect appear fixed. Record implementation progress and new evidence in `REMEDIATION_PROGRESS.md` and the relevant evidence folders. Independent QA—not you—owns final retest status and the release recommendation.

## Non-negotiable visual-editor requirement

The editor must be a **true in-place WYSIWYE editor on the actual rendered public website**.

A conventional dashboard page manager, a form-based editor, a side-panel editor operating on abstract fields, a redirect to a separate editing route, or a substitute canvas is not acceptable, even if it can save, preview, and publish content.

The required user experience is:

1. Power Admin opens the actual public website.
2. Power Admin switches from View Mode to Edit Mode without leaving that page.
3. Power Admin selects or double-clicks the visible content where it appears.
4. The content, media, component, navigation, section, and presentation controls act on that exact rendered element.
5. Changes are visible immediately in place.
6. Save Draft persists through a full refresh while remaining private.
7. Preview shows the exact draft at desktop, tablet, and mobile sizes.
8. Publish makes the exact version visible to a logged-out visitor.
9. Rollback restores the previous published version.

Only Power Admin may see or use the editor. Public users, Patients, Moderators, and Admins must be denied through both the UI and direct APIs.

Implement the complete persistent toolbar: Select, Add Section, Undo, Redo, Preview, Save Draft, Publish, and Exit Edit Mode.

Implement every individual capability in `TRUE_IN_PLACE_VISUAL_EDITOR_ACCEPTANCE_CHECKLIST.md`, including:

- inline text editing and all listed typography controls;
- in-place image replacement and all listed image properties;
- in-place button and link editing;
- section identification, selection, properties, responsive values, reordering, duplication, hiding, deletion, and editing;
- the complete 18-item component library;
- page/section/component/element nested selection and breadcrumb;
- visual navigation editing;
- blank/template new-page creation followed by visual editing on the actual site;
- private drafts, exact preview, desktop/tablet/mobile preview, publish, previous-version retention, rollback, undo/redo, save-state clarity, failure recovery, and safe concurrency;
- a persistent searchable and reusable media library;
- the structured Page/Section/Component/Element/Props/Styles/Responsive Styles/Draft/Published model instead of arbitrary whole-page HTML;
- validation, sanitization, authorization, accessibility, responsive behavior, failure handling, auditability, and schema migration coverage.

QA-002 must remain `REOPENED`, `IN PROGRESS`, `BLOCKED`, or `READY FOR RETEST` until every VE-001 through VE-243 row has its own fresh evidence and passes. QA-003 must remain open until its linked draft, preview, rendered navigation/theme, publish, version-retention, rollback, and logged-out-renderer gates pass. Earlier commits and tests may be linked only when they directly prove the stricter checklist row; do not grandfather prior work or batch-assume coverage.

## Required work order

Follow the dependency order in `FINDINGS_REMEDIATION_PLAN.md`:

1. Re-establish the baseline, current revision, clean production builds, disposable role accounts, and evidence folders.
2. Reconcile the ledger with exactly QA-001 through QA-029; no gaps and no duplicate IDs.
3. Reconcile the visual-editor evidence with exactly VE-001 through VE-243; no gaps and no duplicate IDs.
4. Close or finish QA-001.
5. Close QA-004, QA-005, and QA-006.
6. Close QA-022, then QA-028.
7. Close QA-007, QA-008, QA-017, and QA-029 as one consistent appointment-change workflow.
8. Implement and verify the full true in-place editor for QA-002 and QA-003. Start with a row-by-row gap analysis against VE-001 through VE-243, then implement all gaps. Do not accept the earlier looser editor as completion.
9. Close QA-013 and QA-014.
10. Close QA-011, QA-012, QA-025, and QA-027.
11. Close QA-018, QA-019, QA-020, and QA-024.
12. Close QA-009.
13. Close QA-010.
14. Close QA-015, QA-021, and QA-023.
15. Close QA-016 using the corrected backend contracts.
16. Close QA-026.
17. Run complete regression, production builds, production-like runtime checks, independent retest preparation, and the final release gate.

If earlier progress means a step is already implemented, verify its original reproduction, linked requirements, negative cases, role boundaries, runtime behavior, and evidence before retaining `READY FOR RETEST`. Reopen it when the current implementation or evidence does not satisfy the exact gate.

## Cycle for every QA finding

For each QA-001 through QA-029, without exception:

1. Read the full finding and every linked requirement.
2. Reproduce the original defect in an isolated environment and record the result.
3. Identify root cause and affected dependent workflows.
4. Add a failing regression test first where practical.
5. Implement the complete domain fix, not only the visible symptom.
6. Run targeted tests, then the relevant domain suite and production build.
7. Run positive, negative, authorization, validation, accessibility, responsive, concurrency, and failure-path checks appropriate to the finding.
8. Run Patient A/Patient B isolation whenever patient or private data is involved.
9. Test on production-like PostgreSQL and Redis whenever scheduling, locking, sessions, queues, workers, caching, or concurrency is involved.
10. Record revision, changed files, migrations, automated test names/results, runtime environment, screenshots/logs, evidence location, remaining blockers, and residual risk.
11. Update the finding status accurately. Never use `PASS` without complete evidence and independent QA acceptance; use `READY FOR RETEST` when implementation-agent work is complete.
12. Commit the completed step independently so it can be reviewed or reverted. Push only when the repository's established workflow and authorization require it.

Do not skip an item because it looks similar to another finding. Do not close a parent finding while a linked acceptance row is failed, missing, blocked, or unevidenced.

## Cycle for every VE checklist row

For each VE-001 through VE-243, without exception:

1. Add or update a row-specific evidence record using the format in the checklist.
2. Link the exact implementation revision and automated test when one can validate it.
3. Perform the interaction on the actual rendered website whenever the row describes visible behavior.
4. Record the browser/device, authenticated role, page, action, observed rendered result, refresh result, draft/public state, and evidence path.
5. Test the corresponding unauthorized direct API call where authorization is relevant.
6. Leave the row `FAIL` or `BLOCKED` when any required part cannot be demonstrated. Never convert missing runtime evidence into PASS from a unit test or mock.

Before QA-002 is ready for independent retest, run these as uninterrupted browser journeys exactly as written:

- **VE-232:** Power Admin opens the actual website, enters Edit Mode, double-clicks a heading, changes its text and font styling, saves, fully refreshes, confirms draft persistence, previews, publishes, logs out, and confirms the exact result publicly.
- **VE-233:** Power Admin selects an image where it appears, replaces it, changes alt text, saves, fully refreshes, confirms persistence, previews, publishes, logs out, and confirms the exact public result.
- **VE-234:** Power Admin adds a section on the actual page, edits it, drags it to a new order, saves, fully refreshes, confirms persistence/order, previews, publishes, logs out, and confirms the exact public result.
- Then run **VE-235 rollback** and the complete **VE-236 Public/Patient/Moderator/Admin/Power Admin UI and direct-API authorization matrix**.

All steps in each journey must pass in the same run. Passing isolated fragments is not sufficient.

## Environment, safety, and evidence rules

- Work only with development/staging systems and clearly disposable `.example.test` accounts. Never use real patient data.
- Preserve unrelated user changes and existing git history. Do not reset, discard, or overwrite work you did not create.
- Use PostgreSQL and Redis before claiming runtime closure for applicable findings. SQLite or in-memory tests are supporting evidence only.
- Use approved sandbox providers for malware scanning, mail, SMS, push, and video. A mock cannot prove a real provider integration.
- Keep debug mode off during acceptance checks. Do not expose secrets, tokens, private files, preview credentials, or patient data in logs or evidence.
- Validate migrations on a disposable copy, including upgrade and rollback. Back up disposable test data before destructive migration rehearsals.
- If infrastructure, credentials, a physical device, a browser, or independent QA is unavailable, complete every safe local implementation and test first, then record the precise remaining blocker. Never fabricate external evidence and never report false success.
- Do not weaken security, authorization, validation, accessibility, or audit controls to make a test pass.

## Required completion gates

Do not declare the remediation complete until all of the following are true:

- The canonical QA set is exactly QA-001 through QA-029 with no missing or duplicate finding.
- Every QA finding has a revision, tests, runtime evidence, and an accurate status.
- The visual-editor set is exactly VE-001 through VE-243 with no missing or duplicate checklist row.
- Every VE row passes with row-specific evidence, including the uninterrupted VE-232, VE-233, VE-234, VE-235, and VE-236 journeys.
- All 150 original requirements have been individually re-evaluated.
- All automated suites and production builds pass.
- PostgreSQL concurrency, Redis/worker/scheduler, external-provider failures, backup/restore, and migration rollback pass where applicable.
- Cross-role authorization and Patient A/Patient B isolation pass.
- Accessibility, responsive-width, browser, and physical-device matrices pass.
- Content, clinical/operational, security/privacy, and legal approvals are recorded where required.
- There are zero open Critical or High findings.
- Independent QA—not the implementation agent—issues the final release recommendation.

Until these gates are satisfied, retain the release decision **DO NOT RELEASE**.

## Progress communication

Work continuously through the ordered plan. After each meaningful step, update `REMEDIATION_PROGRESS.md` with:

- what was implemented;
- finding and VE IDs covered;
- revision and changed files;
- migrations and rollback result;
- exact tests and builds run with results;
- runtime/browser/device evidence path;
- remaining blocker or risk;
- next dependency-ordered action.

When you reach a genuine external blocker, state exactly what is missing, what has already been completed locally, why the missing evidence cannot be simulated, and the single next action needed from the environment or owner. Then continue with every other unblocked item in dependency order.

Your final handoff must report the exact QA and VE counts by status, list all remaining blockers, link the evidence and revisions, state whether all completion gates passed, and retain **DO NOT RELEASE** unless independent QA has issued a new release recommendation.
