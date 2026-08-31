# Remediation Progress

Started: 28 August 2026  
Audit baseline revision: `2424cb1f68232f3b10d2e6f43be4536e7091d5df`  
Implementation owner: Codex implementation agent  
Authoritative inputs: the original audit reports plus `TRUE_IN_PLACE_VISUAL_EDITOR_ACCEPTANCE_CHECKLIST.md` in this directory. The original reports are preserved unchanged.

## Operating rules

- This file is the source of truth for remediation status.
- Status values are `NOT STARTED`, `IN PROGRESS`, `BLOCKED`, `READY FOR RETEST`, `PASS`, and `REOPENED`.
- `PASS` requires the original reproduction case, automated regression coverage, runtime evidence where applicable, and a linked revision.
- External-provider, physical-device, browser-matrix, PostgreSQL, Redis, and independent-QA evidence is never inferred from local mocks or SQLite tests.
- Every remediation step is committed and pushed independently.
- QA-002 and QA-003 cannot be closed until every VE-001 through VE-243 row has fresh evidence and the mandatory continuous journeys pass on the actual rendered website.
- A dashboard manager, form editor, separate editing route, or substitute canvas does not satisfy the true in-place editor addendum.

## Step 0 baseline

Status: **COMPLETE WITH EXTERNAL INFRASTRUCTURE BLOCKER**

| Item | Result | Evidence / remaining risk |
| --- | --- | --- |
| Starting revision | PASS | `2424cb1f68232f3b10d2e6f43be4536e7091d5df` |
| Backend suite | PASS | Audit baseline: 58 tests, 302 assertions. After Step 0 safeguards: 60 tests, 324 assertions, 11.37 seconds |
| Web suite | PASS | 3 files, 7 tests, 38.42 seconds |
| Web typecheck/build | PASS | TypeScript passed; JS 405.45 KB (124.93 KB gzip), CSS 268.23 KB (38.27 KB gzip) |
| Native suite | PASS | 4 suites, 6 tests, 12.313 seconds |
| Native validation | PASS | TypeScript and Expo public configuration passed; iOS and Android identifiers present |
| Isolated role accounts | PASS | `QaRemediationSeeder` creates Patient A, Patient B, Moderator, Admin, and Power Admin; verified, active, claimed, `.example.test` only; production execution is rejected |
| Fresh migration / seed / rollback | PASS on SQLite | Disposable database migrated, QA accounts seeded, and all migration batches rolled back; a pre-existing users-index rollback defect was corrected in `2026_08_26_000001_create_practice_core_tables.php` |
| Private storage | PARTIAL | Local private disk is configured; production volume behavior remains a staging retest item |
| PostgreSQL 17 | BLOCKED | PHP PostgreSQL driver is installed, but port 5432 is unavailable and the local Docker engine is stopped |
| Redis / workers / scheduler | BLOCKED | Port 6379 is unavailable, host PHP has no Redis extension, and the local Docker engine is stopped |
| Provider sandboxes | BLOCKED | No approved malware scanner, video, mail, SMS, or push sandbox credentials/approvals are present |
| Physical devices / browser matrix | BLOCKED | No physical iOS/Android devices or separate Safari/Firefox/Edge runtimes are connected |

## Closure ledger

| Finding | Step | Status | Minimum closure evidence | Revision / files / tests / runtime evidence / remaining risk |
| --- | ---: | --- | --- | --- |
| QA-001 | 1 | READY FOR RETEST | Numeric protected page 200, public slug works, role denials and real editor E2E | Implementation `cf396fd`; route/editor integration and public-browser evidence in `evidence/QA-001`; independent authenticated browser retest pending |
| QA-002 | 6 | REOPENED | Every VE-001–VE-243 gate and REQ-065–084 row passes with exact in-place E2E/accessibility evidence | Earlier implementations `b8bbdeb`, `bec0619`, `d905416` were assessed against the prior, looser definition. The release-critical true in-place addendum now requires a fresh row-by-row gap review and exact VE-232–VE-236 runtime journeys; prior local evidence may be linked only where it directly proves the stricter row. |
| QA-003 | 6 | REOPENED | Draft isolation, rendered nav/theme, preview, publish, version retention and rollback pass the linked VE gates | Earlier implementation `b8bbdeb` and expanded menu `bec0619` require fresh validation against the actual-page editor, private draft, exact preview, previous-version retention, rollback and logged-out renderer gates in the VE checklist. |
| QA-004 | 2 | READY FOR RETEST | Off-rule/closed/method-invalid POSTs fail; generated slot succeeds | Implementation `adf7156`; shared availability decision and regression evidence in `evidence/QA-004` |
| QA-005 | 2 | BLOCKED | Repeated PostgreSQL concurrent test commits exactly one overlap | PostgreSQL advisory-lock implementation `adf7156`; true concurrent PostgreSQL runtime unavailable; evidence in `evidence/QA-005` |
| QA-006 | 2 | READY FOR RETEST | slot_minutes/buffer semantics documented and boundary-tested | Implementation `adf7156`; cadence contract/test in `evidence/QA-006` |
| QA-007 | 5 | READY FOR RETEST | Declined cancellation can be resubmitted and displays accurately | Implementation `1df4fdc`; reset/reason/status/audit/API/web regression passed; evidence in `evidence/QA-007` |
| QA-008 | 5 | READY FOR RETEST | Reschedule lifecycle consistent in DB/API/UI/audit/notifications | Implementation `1df4fdc`; active `rescheduled` lifecycle and operational consumers covered; evidence in `evidence/QA-008` |
| QA-009 | 10 | READY FOR RETEST | Day/week/month/agenda plus operations/filters/responsive/a11y pass | Implementation `7212b73`; four views, create/detail/edit/status/reschedule, combined filters, scheduling management, conflict recovery and automated regressions pass; independent responsive/device/screen-reader acceptance remains; evidence in `evidence/QA-009` |
| QA-010 | 11 | BLOCKED | Approved live provider passes ownership/expiry/media/failure/device tests | Approved provider/privacy decision absent; independent shell work remains queued; evidence folder `evidence/QA-010` |
| QA-011 | 8 | READY FOR RETEST | Contact route and true 404 pass direct/link/status tests | Implementation `ee80e16`; real status and route regressions pass; evidence in `evidence/QA-011` |
| QA-012 | 8 | BLOCKED | Complete verified public profile/service/education/publication journeys | Technical journeys implemented; content-owner/institutional approvals remain unavailable; evidence in `evidence/QA-012` |
| QA-013 | 7 | READY FOR RETEST | Transactional state machine and retract/public-feed consistency tests | Implementation `a49c1a9`; lifecycle/feed/identity/audit regressions pass; PostgreSQL and independent browser retest remain; evidence in `evidence/QA-013` |
| QA-014 | 7 | READY FOR RETEST | Fresh seed uses sourced queue only; duplicate migration verified | Implementation `a49c1a9`; fresh-seed and legacy-upgrade regressions pass; PostgreSQL and independent retest remain; evidence in `evidence/QA-014` |
| QA-015 | 12 | READY FOR RETEST | Patient/account/role admin operations pass role/audit edge cases | Implementation `db49100`; patient/staff boundaries, invitations, re-authentication, deactivation and Power Admin invariants pass; independent review remains; evidence in `evidence/QA-015` |
| QA-016 | 13 | READY FOR RETEST | All required native mutations pass API, E2E, device and a11y tests | Implementation `998c871`; profile, secure documents, messages, notification read, consultation state, reminder preferences and owned-device workflows pass API/native regressions; physical iOS/Android, push/video providers and independent accessibility/security acceptance remain; evidence in `evidence/QA-016` |
| QA-017 | 5 | READY FOR RETEST | Web/native actions exactly match server allowed_actions for all states | Implementation `1df4fdc`; all-state API matrix and rendered web/native regressions passed; physical-device acceptance pending; evidence in `evidence/QA-017` |
| QA-018 | 9 | READY FOR RETEST | Mobile menu passes touch/keyboard/ARIA at all collapsed widths | Implementation `d90a2af`; disclosure, Escape/focus-return, close-on-route and target-size regressions pass; physical-device matrix remains independent acceptance; evidence in `evidence/QA-018` |
| QA-019 | 9 | READY FOR RETEST | Labels, skip link, focus, names and announcements pass manual/axe checks | Implementation `d90a2af`; shared skip/route focus, accessible names, labels and live error/status regressions pass; physical screen-reader/axe acceptance remains external; evidence in `evidence/QA-019` |
| QA-020 | 9 | READY FOR RETEST | Semantic text/control/focus tokens meet WCAG contrast thresholds | Implementation `d90a2af`; semantic colour contrast regression, focus-visible, reduced-motion and forced-colour rules pass locally; independent visual matrix remains; evidence in `evidence/QA-020` |
| QA-021 | 12 | READY FOR RETEST | Password reset revokes other web sessions and mobile/API tokens | Implementation `db49100`; database sessions and all personal-access tokens are revoked, and owned session/device management passes; non-database store and independent multi-device acceptance remain; evidence in `evidence/QA-021` |
| QA-022 | 3 | BLOCKED | Approved scanner/quarantine passes clean/reject/timeout/privacy tests | Quarantine/fail-closed implementation `c306f15`; approved provider/privacy/runtime approval unavailable; evidence in `evidence/QA-022` |
| QA-023 | 12 | READY FOR RETEST | Audit catalogue actions produce safe actor/resource/before-after records | Implementation `db49100`; catalogue, safe change records, recursive redaction, authorization and bounded filters/pagination pass; independent privacy review remains; evidence in `evidence/QA-023` |
| QA-024 | 9 | READY FOR RETEST | Every major mutation shows preserved, announced, recoverable failure | Implementation `d90a2af`; auth, booking, portal, staff scheduling/search/decisions, education and consultation actions now expose pending/error/retry state; preserved-message regression passes; exhaustive independent forced-error matrix remains; evidence in `evidence/QA-024` |
| QA-025 | 8 | READY FOR RETEST | Per-route title/description/canonical/OG plus sitemap/404 checks | Implementation `ee80e16`; server/client metadata, sitemap and robots regressions pass; evidence in `evidence/QA-025` |
| QA-026 | 14 | NOT STARTED | Route chunks/requests meet recorded performance budget | Queued; evidence folder `evidence/QA-026` |
| QA-027 | 8 | BLOCKED | Approved Privacy/Terms/Accessibility pages linked and crawl-tested | Routes/link/noindex infrastructure implemented; approved legal documents/sign-off unavailable; evidence in `evidence/QA-027` |
| QA-028 | 4 | BLOCKED | Booking attachment is scanned, private, owned, audited and accessible | Implementation `5fa3156`; local acceptance passed; approved production scanner/governance and independent responsive/device acceptance remain unavailable; evidence in `evidence/QA-028` |
| QA-029 | 5 | READY FOR RETEST | Patient reschedule request approve/decline/conflict/audit/notify passes | Implementation `1df4fdc`; web/mobile request and staff review lifecycle covered; physical-device/independent acceptance pending; evidence in `evidence/QA-029` |

## Step log

### Step 0 — Freeze baseline and create progress evidence

- Status: COMPLETE WITH EXTERNAL INFRASTRUCTURE BLOCKER
- Files: `REMEDIATION_PROGRESS.md`, `database/seeders/QaRemediationSeeder.php`, `tests/Feature/QaRemediationSeederTest.php`, `evidence/QA-001` through `evidence/QA-029`.
- Tests: existing backend/web/native baselines and production web build passed; dedicated seeder regression passed (2 tests, 22 assertions); disposable SQLite fresh migration, seed, and full rollback passed.
- Runtime: local SQLite development environment only. PostgreSQL/Redis/provider/device claims remain blocked as recorded above.
- Remaining risk: production-like concurrency, Redis queue/session/scheduler, provider failure modes, physical devices, additional browser engines, and independent QA are not yet evidenced.

### Step 1 — Unblock the CMS editor route

- Status: READY FOR RETEST (not self-declared PASS).
- Revision: `cf396fd`.
- Files: `routes/api.php`, `resources/js/pages/CmsPublicPage.tsx`, `tests/Feature/CmsTest.php`, `tests/Feature/CoreCmsPagesTest.php`.
- Tests: full backend 62 tests/349 assertions; targeted CMS 9 tests/59 assertions; web typecheck, 7 tests, and production build passed.
- Runtime: public Home rendered through `/api/content/pages/home` in the in-app Chromium browser with the expected heading and no console errors. The real backend integration completed list/select, numeric load, draft edit, preview, publish, and public read. Patient, Moderator, and Admin numeric access returned 403; missing numeric ID and missing slug returned safe 404s.
- Remaining risk: the browser session was unauthenticated, so the visual Power Admin click-through was not repeated without entering credentials. Independent authenticated browser retest remains required before PASS.

### Step 2 — Make booking submission use scheduling truth

- Status: IMPLEMENTED; QA-004 and QA-006 READY FOR RETEST; QA-005 BLOCKED ON POSTGRESQL RUNTIME EVIDENCE.
- Revision: `adf7156`.
- Files: `app/Services/AvailabilityService.php`, booking and reschedule services/controllers, scheduling documentation, and related feature tests.
- Tests: full backend 64 tests/357 assertions; targeted scheduling/booking/staff suite 13 tests/61 assertions.
- Runtime: SQLite regression proves generated slot success, repeated-slot rejection, off-cadence rejection, unsupported online method rejection, rule/closure/additional-clinic generation, and staff reschedule parity.
- Remaining risk: PostgreSQL 17 and a true simultaneous create/create plus create/reschedule harness remain unavailable because the local Docker engine and PostgreSQL port are unavailable. The date-scoped `pg_advisory_xact_lock` branch is implemented but not claimed as runtime-proven.

### Step 3 — Establish the secure upload pipeline

- Status: IMPLEMENTED LOCALLY; QA-022 remains BLOCKED on approved production scanner and governance evidence.
- Revision: `c306f15`.
- Files: scanner contract/drivers/configuration, patient document controller, production environment example, security/deployment documentation, and upload regression suite.
- Tests: full backend 71 tests/396 assertions; targeted upload/security/portal suite 17 tests/89 assertions; web typecheck and 7 tests passed.
- Runtime behavior: uploads use a random private quarantine name, validate safe single-extension names and container signatures, scan the quarantined object, move only a definitive clean result to patient-owned private storage, remove rejected/indeterminate objects, avoid orphan database metadata, and audit upload/rejection/scanner failure/download with privacy-minimal metadata.
- Remaining risk: `basic` is explicitly local/test-only. Production defaults to fail-closed `unconfigured`. No approved scanner vendor, hosting region, retention/DPA, monitoring credentials, or staging failure-mode run is available, so the finding cannot be READY or PASS.

### Step 4 — Add secure booking-time attachments

- Status: IMPLEMENTED LOCALLY; QA-028 remains BLOCKED on the Step 3 production-scanner/governance dependency and independent responsive/device acceptance.
- Revision: `5fa3156`.
- Files: shared `SecureDocumentService`, booking request/controller and document/appointment models, attachment/idempotency migration, accessible booking form and styles, security retention notes, and backend/web regression tests.
- Tests: full backend 77 tests/437 assertions; dedicated booking-attachment suite 6 tests/41 assertions; web typecheck, 4 files/8 tests, and production build passed. Fresh SQLite migration/seed, latest-migration rollback and re-migration passed.
- Runtime behavior: no-file booking remains valid; clean PDF/JPEG/PNG files use the shared quarantine/scan/release path and link to both patient and appointment; rejected or unavailable scans roll back the booking and retain no object; the request UUID makes same-request retries return the original reference without a duplicate appointment or document; verified owner and staff download while another patient receives 403.
- Browser/UI evidence: the production booking build rendered in the in-app Chromium browser with the existing desktop layout intact. The web component journey exercises date/slot selection, fully associated labels, optional file help/accept rules, selected-file status, multipart request, idempotency key and announced success. Separate physical-device and independent responsive/browser-matrix acceptance is not available.
- Remaining risk: production still deliberately fails closed until an approved scanner adapter, hosting/retention/privacy approval, monitoring and staging failure-mode evidence exist. The local basic scanner and simulated failure tests are not production malware-scanning evidence.

### Step 5 — Unify patient cancellation and reschedule lifecycle

- Status: IMPLEMENTED; QA-007, QA-008, QA-017 and QA-029 READY FOR RETEST.
- Revision: `1df4fdc`.
- Files: appointment workflow/policy/availability/notification/consultation services, cancellation and new reschedule request controllers/model/migration/routes, patient/staff APIs, shared mobile contract, web portal/staff inbox, native appointment screen, and backend/web/native regression tests.
- Lifecycle: a declined cancellation can be reset to pending with a new reason; one appointment cannot hold contradictory pending cancellation/reschedule requests; patient reschedule requests use currently generated slots but do not reserve one; staff approval re-locks/revalidates availability transactionally; decline and approval are audited/notified; `rescheduled` is now an active visible state that blocks conflicts and remains eligible for reminders, online consultation, check-in, cancellation and later reschedule.
- Contract: patient appointment payloads expose `allowed_actions`; both web and native render only those actions. Requested, pending-confirmation, confirmed and rescheduled states are covered, while checked-in, in-progress, completed, cancelled and no-show suppress patient change actions. A pending change suppresses both actions until staff review.
- Tests: full backend 82 tests/499 assertions; dedicated lifecycle suite 5 tests/61 assertions; web TypeScript, 5 files/9 tests and production build passed; native TypeScript, 5 suites/7 tests and Expo public iOS/Android configuration passed. Latest migration rollback/re-migration passed.
- Runtime: authenticated in-app Chromium loaded the staff inbox with separate, labelled cancellation and reschedule review queues and no rendering failure. Component tests exercised the patient web reschedule form and native server-derived-slot flow.
- Remaining risk: independent patient/staff browser E2E with populated queues, physical iOS/Android interaction/accessibility, additional browser engines and production PostgreSQL concurrent approval evidence remain required before PASS.

### Step 6 — Connect the visual CMS to the public website

- Status: IMPLEMENTED LOCALLY WITH PUBLIC-MEDIA / INDEPENDENT-ACCEPTANCE BLOCKER. QA-003 is READY FOR RETEST; QA-002 remains BLOCKED.
- Revisions: publishing foundation `b8bbdeb`; live visual editor/lifecycle `bec0619`; safe selected-text marks `d905416`.
- Public Edit Mode: remains active across public navigation, loads a protected draft only for the Power Admin, outlines selectable/hidden sections, and opens a labelled inline editor on double-click. Selected ranges support bold, italic, underline and validated links without raw HTML.
- Full controls: allowlisted typography/layout, button style/icon/visibility, image URL/alt/link/dimensions/side/fit/crop-focus/radius, menu rename/add/delete/reorder/hide/submenu, page create/rename/slug/SEO/publish/unpublish/duplicate, section add/remove/duplicate/reorder/hide, and version restore. Stale page/inline saves return 409.
- Settings: published navigation and palette/density/heading style now drive the public renderer; protected drafts do not leak. The mobile menu uses explicit state and accurate ARIA.
- Tests: full backend 86 tests/560 assertions; web TypeScript, 5 files/14 tests and production build passed. JS 440.49 KB (133.84 KB gzip); CSS 281.67 KB (40.88 KB gzip). Latest migration rollback/reapply passed.
- Runtime: authenticated in-app Chromium rendered the full page editor and settings manager, then exercised public About Edit Mode, section outlines and the inline text dialog; Cancel left content unchanged.
- Remaining risk: managed upload/library/replacement still requires the approved QA-022 scanner and public-media hosting/retention/transformation governance. Physical device, screen-reader, responsive matrix, additional browser and independent QA evidence are unavailable, so QA-002 is not claimed READY or PASS.

#### Step 6R — Reviewed true in-place editor rebuild

- Status: **IN PROGRESS**. QA-002 and QA-003 remain **REOPENED**; no earlier PASS or READY claim is carried forward.
- Source of truth: `evidence/QA-002/TRUE_EDITOR_IMPLEMENTATION_PLAN.md`.
- Atomic gap inventory: `evidence/QA-002/TRUE_EDITOR_GAP_MATRIX.md`, containing exactly one current-assessment row for VE-001 through VE-243.
- Delivery order: S1 structured editor/document foundation; S2 inline text/buttons/links; S3 sections/components/hierarchy; S4 navigation/pages; S5 media/images; S6 resilience/accessibility; S7 continuous journeys and independent-QA handoff.
- Current implementation assessment: the actual-page toolbar, atomic document lifecycle, cursor-in-rendered-element text editing, live typography and rendered-button controls are implemented. Linked-text controls, the 18-component/hierarchy model, actual-page navigation/page lifecycle, persistent managed media, direct-rollback UI and uninterrupted release journeys remain incomplete.
- Release rule: VE-242 and VE-243 remain independent-QA gates. This implementation agent will not self-close QA-002 or QA-003.
- S1 foundation revision: `0c1cd19`; evidence: `evidence/QA-002/S1_EDITOR_FOUNDATION_EVIDENCE.md`.
- S1 result: actual-page persistent toolbar and command bridge, whole-document local history, atomic optimistic-lock draft save, immutable exact-current preview snapshot, transactional publish with previous-version retention, direct published rollback and expanded audit events. Full backend (110 tests / 854 assertions), full web (10 files / 31 tests), production build and migration rollback/reapply passed.
- S2 revisions: text `e3a0a54`; rendered buttons `165223d`; linked text `f6e488a`; evidence: `evidence/QA-002/S2_TEXT_BUTTON_EVIDENCE.md`.
- S2 result: exact rendered-element caret editing, plain structured text persistence, complete live typography inspector, rendered Hero/CTA button controls, and exact linked-text create/edit/remove controls for internal, external, email and telephone actions. Unsafe and mismatched action/destination pairs are rejected. Full backend (110 tests / 863 assertions), full web (10 files / 34 tests) and production build pass.
- Next active slice: S3 expanded sections, 18-component catalogue, container hierarchy and responsive presentation. The uninterrupted refresh/preview/publish/logout journeys remain S7 evidence and QA-002/QA-003 remain REOPENED.
- S3 source of truth: `evidence/QA-002/S3_SECTIONS_COMPONENTS_PLAN.md`. S3.1 revision `a894ed9` delivers the actual-page section inspector, responsive presentation model and move/drag/duplicate/hide/delete lifecycle. S3.2 revision `189d4bb` delivers the searchable actual-page Add Section library, intentional insertion location, safe defaults and renderers/server schemas for all 18 required component types. Fresh gate: backend 110 tests/910 assertions, web 10 files/36 tests, production build and diff check pass. Active task S3.3 is bounded nested component editing and the visible Page › Section › Component › Element hierarchy.

### Step 7 — Repair research and publication lifecycle

- Status: IMPLEMENTED LOCALLY; QA-013 and QA-014 READY FOR RETEST.
- Revision: `a49c1a9`.
- Lifecycle: constrained pending review, verification/rejection, publication and reasoned retraction transitions are transactional and audited. A published claim cannot be silently rejected. Retraction removes its target from both public feeds.
- Identity: DOI and PMID are normalized and unique; title hashing safely distinguishes publications without external identifiers. Sourced payloads are allowlisted and cannot set ownership, identity or publication-state fields.
- Seeds/migration: six direct incomplete publication seeds were replaced by sourced PubMed queue claims. The reviewed migration removes obsolete title-only pending rows, merges legacy duplicates, preserves the strongest metadata/state, links published claims and installs uniqueness constraints.
- Tests: dedicated backend 9 tests/89 assertions; full backend 90 tests/631 assertions; web 6 files/16 tests; formatting and TypeScript passed; production build JS 441.97 KB (134.30 KB gzip), CSS 281.91 KB (40.93 KB gzip). Latest migration and rollback/reapply passed on the local SQLite database.
- Local data: after migration and updated seed, six sourced publication claims exist; two previously reviewed claims remain linked/published and four remain pending review. No review decision was fabricated for the four pending claims.
- Remaining risk: PostgreSQL-specific migration/locking/constraint behavior, independent authenticated browser acceptance and owner approval for pending professional content remain required before PASS. The in-app runtime check could not be repeated because local URL navigation was blocked by the browser control policy; automated rendered-component coverage passed.

### Step 8 — Complete public content, routing, legal pages and SEO

- Status: IMPLEMENTED WITH CONTENT / LEGAL APPROVAL BLOCKERS. QA-011 and QA-025 READY FOR RETEST; QA-012 and QA-027 BLOCKED.
- Findings: QA-011, QA-012, QA-025 and QA-027.
- Plan: (1) implement a real Contact journey and true status-preserving 404; (2) complete verified public profile, services, education and publication routes without inventing unapproved claims; (3) add route-specific title, description, canonical and Open Graph metadata plus sitemap/robots coverage; (4) add clearly labelled Privacy, Terms and Accessibility drafts with global links while retaining an explicit owner/legal-approval gate; (5) run direct-route, link, API, metadata, crawl, responsive and accessibility regressions.
- Source constraints: published research remains limited to reviewed records; professional/contact/legal copy requiring owner approval will be marked draft or blocked rather than silently asserted.
- Revision: `ee80e16`.
- Routing/contact: known routes and published dynamic records return 200; arbitrary/unpublished destinations return a true 404 and dedicated recovery page. Contact safely connects booking and secure portal messaging without inventing public phone/email/address details.
- Public journeys: active service cards use the booking catalogue and deep-link to the selected service; education and publication list/detail journeys include medical review, disclaimers, sort, pagination, DOI and authoritative-source links; career/achievement output is published-only.
- Navigation/legal: Contact plus Academic/Education destinations are in approved navigation/footer. Privacy, Terms and Accessibility routes are live but explicitly approval-pending, noindex and excluded from sitemap.
- SEO: server and client emit route/CMS/detail title, description, robots, canonical and Open Graph metadata; sitemap contains only indexable published records; robots points to the sitemap.
- Tests: dedicated public routing/content suite 6 tests/62 assertions; full backend 96 tests/693 assertions; web 7 files/21 tests; formatting, TypeScript and production build passed. JS 457.63 KB (137.61 KB gzip), CSS 284.49 KB (41.36 KB gzip). Latest migration rollback/reapply passed on SQLite.
- Runtime: direct HTTP route sweep passed all expected 200 routes plus a real 404, and server HTML inspection confirmed Contact title/canonical and missing-page noindex/no-canonical behavior. The in-app browser control remained unavailable for localhost UI interaction, so independent visual acceptance is still required.
- Remaining risk: approved current professional/contact/education content, approved legal documents/sign-off, independent browser/crawler/responsive/a11y acceptance and production-domain verification remain external blockers. No approval was fabricated.

### Step 9 — Repair responsive navigation, accessibility and error recovery

- Status: IMPLEMENTED LOCALLY; QA-018, QA-019, QA-020 and QA-024 READY FOR RETEST.
- Findings: QA-018, QA-019, QA-020 and QA-024.
- Plan: (1) add skip navigation, programmatic main focus and route-change menu/focus behavior; (2) complete mobile disclosure keyboard/Escape/focus handling and 44px targets; (3) audit labels, names, status/error announcements and field relationships across public/auth/booking/portal/staff/CMS; (4) replace weak semantic colours and add consistent focus-visible, reduced-motion, forced-colours and reflow rules; (5) standardize major mutation pending/success/error preservation and retry; (6) add automated accessibility/viewport/error regressions while recording physical screen-reader/device/browser blockers honestly.
- Revision: `d90a2af`.
- Navigation/focus: the public shell provides a visible-on-focus skip link, programmatic main focus after route changes, mobile disclosure state, close-on-route, Escape close and focus return. CMS submenus expose controls/expanded state and the same Escape behavior.
- Forms/announcements: auth, booking, portal, staff calendar/dashboard, education and consultation controls have programmatic labels or names. Loading, success and error states use appropriate live status/alert semantics.
- Recovery: major patient and staff mutations expose pending state, preserve entered values after failure, provide a specific retryable message and re-enable the action. This includes portal messages/documents/profile, reminder settings, schedule exceptions, appointment decisions/search, education drafts/publish and patient/staff consultation transitions.
- Presentation: the low-contrast rose token was replaced with a 5.28:1-on-white semantic value; shared focus-visible, 44 px coarse/mobile targets, reduced-motion, forced-colour, wrapping and horizontal-reflow safeguards were added.
- Tests: focused navigation/portal failure regressions plus semantic contrast checks pass; full web 8 files/24 tests, TypeScript and production build pass. Build output is JS 466.77 KB (139.39 KB gzip) and CSS 286.66 KB (41.99 KB gzip). Full backend remains 96 tests/693 assertions. `git diff --check` passes.
- Remaining risk: independent axe/browser/320–1440 px matrix, keyboard walkthrough, Windows High Contrast, VoiceOver/TalkBack/NVDA and physical touch-device acceptance are unavailable locally. The exhaustive forced 422/403/404/409/429/500/network matrix remains an independent retest gate, so no finding is marked PASS.

### Step 10 — Build the operational calendar

- Status: IMPLEMENTED LOCALLY; QA-009 READY FOR RETEST.
- Finding: QA-009.
- Plan: (1) inventory the existing calendar and scheduling APIs against the required Day, Week, Month and Agenda views; (2) add appointment create/detail/edit/reschedule with shared server-side scheduling truth; (3) provide keyboard-accessible movement with a non-drag equivalent and conflict rollback; (4) add patient, service/type and status filters; (5) expose recurring availability rules and exceptions; (6) keep Africa/Lagos display and UTC storage explicit; (7) verify responsive and accessible behavior at 320, 390, 768, 1024 and 1440 px; (8) record automated evidence and external device/screen-reader blockers honestly before committing and pushing the step.
- Revision: `7212b73`.
- Views and filtering: staff can switch among distinct Day, Week, Month and Agenda presentations, navigate dates and combine patient, status, service and consultation-method filters. Display timezone is explicitly Africa/Lagos.
- Appointment operations: authorized staff can search an existing patient, create and confirm an appointment from server-derived slots, inspect details, edit operational notes/location, apply valid status transitions and reschedule. Staff-only reschedule options exclude the current appointment while retaining the shared scheduling truth.
- Scheduling operations: the page exposes recurring availability rules, active/paused state and schedule exceptions. Appointment movement supports pointer drag and labelled non-drag previous/next-day controls; the UI refreshes only after server acceptance and announces conflicts without retaining an invalid optimistic move.
- Tests: focused calendar UI 3 tests; full web 9 files/27 tests; full backend 97 tests/712 assertions; TypeScript and production build passed. Build output is JS 482.73 KB (143.34 KB gzip) and CSS 294.23 KB (43.13 KB gzip). `git diff --check` passed.
- Remaining risk: independent 320, 390, 768, 1024 and 1440 px browser checks, physical pointer/touch interaction, screen-reader traversal and production PostgreSQL concurrency evidence are unavailable locally. QA-009 is therefore not self-declared PASS.

### Step 11 — Activate real online consultations

- Status: BLOCKED ON APPROVED PRIVACY/PROVIDER DECISION.
- Finding: QA-010.
- Plan: (1) record the approved video provider, hosting region, data-processing terms, retention and incident controls; (2) implement the provider adapter without exposing secrets or room locators; (3) connect patient consent/wait/join/leave and staff admit/start/end to short-lived owned authorization; (4) add outage, permission, interruption and rejoin recovery; (5) record privacy-minimal attendance/audit events; (6) integrate web and native clients after the shared contract passes; (7) verify ownership, expiry/replay, browser and physical iOS/Android media behavior.
- Current position: the provider-independent consultation state machine, consent gate, ownership isolation, short-lived signed join authorization and attendance auditing already have backend coverage. A live provider integration cannot be selected or represented as production-ready without the external provider/privacy approvals recorded in the prerequisite.
- Next action: preserve this blocker and proceed to Step 12, whose administration, audit and session-revocation work does not depend on a video-provider choice.

### Step 12 — Add administration, full audit and session revocation

- Status: IMPLEMENTED LOCALLY; QA-015, QA-021 and QA-023 READY FOR RETEST.
- Findings: QA-015, QA-021 and QA-023.
- Plan: (1) inventory current user, token, database-session and audit behavior; (2) add minimum-necessary Admin patient management and Power Admin account/role management with search and pagination; (3) require the acting Power Admin password for role/deactivation changes and prevent self-demotion, self-deactivation and removal of the last active Power Admin; (4) make deactivation revoke the target's browser sessions and API/mobile tokens; (5) make password reset revoke every database-backed browser session and API/mobile token; (6) expose privacy-minimal owned session/device listing and individual revocation; (7) centralize a documented audit-event catalogue and record safe field-level before/after changes without secrets or clinical/message bodies; (8) add role, isolation, inactive/unverified, edge-case, reset/revocation, redaction, filtering and pagination regressions; (9) add accessible account-administration and device/session UI; (10) run full backend/web/type/build verification and record external Redis/independent-review blockers before commit and push.
- Authorization boundary: Admin can manage patient identity/contact/active state only. Moderator receives no account-management authority. Power Admin can manage all account roles, but sensitive changes require re-authentication and invariant checks.
- Data boundary: administrative list/detail payloads and audit changes contain only identity, role, active/verification/claim state and timestamps; appointment notes, documents, message bodies, credentials and reset/session/token material are excluded.
- Revision: `db49100`.
- Account administration: verified Admin users can search, paginate, invite and edit patient accounts only. Verified Power Admin users can manage every role. Staff invitations use a claim link and do not bypass email verification. Role/active changes require the actor's current password; self-demotion, self-deactivation and removal of the last active Power Admin are rejected.
- Access revocation: account deactivation and password reset remove every database-backed browser session and personal-access token. Every authenticated user can list privacy-minimal browser sessions and app/API devices and revoke only their own non-current access.
- Audit: `docs/AUDIT_EVENT_CATALOGUE.md` defines privacy boundaries. New identity events use centralized recursive redaction and safe field-level before/after changes. The Power Admin viewer supports action prefix, actor, date range and pagination, with a 90-day server bound.
- UI: Accounts and Security are explicit staff navigation items; the patient portal links to Security. New private web routes return the SPA with noindex metadata. Mutation success/error states are announced and entries are retained after failure.
- Tests: focused security/authorization 19 tests/136 assertions; full backend 102 tests/761 assertions after the private-route additions; full web 10 files/31 tests; TypeScript and production build passed. Build output is JS 497.52 KB (146.40 KB gzip) and CSS 296.95 KB (43.50 KB gzip). `git diff --check` passed.
- Remaining risk: independent two-browser/two-device acceptance, external privacy/privilege review, non-database session-store behavior, physical-device accessibility and additional browser engines remain unavailable. Browser automation was unable to navigate localhost because of its URL policy; route, rendered-component and HTTP regressions passed. No finding is self-declared PASS.

### Step 13 — Complete the native patient application

- Status: IMPLEMENTED LOCALLY; QA-016 READY FOR RETEST.
- Finding: QA-016.
- Plan: (1) inventory the Expo application and v1 API against profile update, document upload/download, message compose/reply, notification read, consultation consent/wait/join/leave, reminder preferences and device/session management; (2) implement missing stable v1 mutations with patient isolation, scoped tokens and idempotency for retryable writes; (3) complete native loading, empty, validation, authentication-expiry, offline/interruption and retry states; (4) retain server-provided `allowed_actions` as appointment truth; (5) connect approved-independent consultation state while keeping real media disabled until Step 11 approval; (6) keep push registration/delivery disabled until provider/privacy configuration exists; (7) add API contract and native component tests plus TypeScript/Expo configuration validation; (8) record physical iOS/Android, secure-storage, file, deep-link, push, media and accessibility blockers honestly; (9) commit and push native implementation and evidence independently.
- Scope boundary: Android and iOS share the Expo patient application. No staff/admin mobile role expansion is authorized in this step, and no placeholder video or push provider will be represented as live.
- Revision: `998c871`.
- API and security: the scoped verified-patient v1 API now supports retry-safe profile update, security-scanned document upload/owned download, thread compose/reply, notification read, reminder preferences, device listing/revocation and consultation consent/wait/join/leave. A request identifier cannot be replayed across operations. Foreign documents, threads, notifications and consultations remain denied; document downloads are audited; the current device must use sign out instead of self-revocation.
- Native workflows: the shared Expo 57 application now provides editable profile/emergency details, reminder settings, signed-in device management, document picker/upload/open/share, message compose/reply with preserved drafts, notification read controls, and provider-independent consultation consent/wait/connection state. Server-provided appointment `allowed_actions` remains authoritative.
- Truthful capability boundary: push registration and live video remain disabled. The consultation screen does not request camera or microphone permission and surfaces the unconfigured-provider response without representing media as available.
- Platform configuration: the shared build retains Android package `com.drfunmilola.patient`, iOS bundle identifier `com.drfunmilola.patient`, deep-link scheme `drfunmilola`, and an empty Android permission list. Expo SDK 57-compatible document picker, file-system and sharing packages were installed using the exact versioned Expo documentation.
- Tests: full backend 107 tests/813 assertions; focused mobile API operations 5 tests/52 assertions; full web 10 files/31 tests; web TypeScript and production build passed (JS 497.52 KB / 146.40 KB gzip; CSS 296.95 KB / 43.50 KB gzip). Native TypeScript, 7 suites/13 tests and Expo public iOS/Android configuration validation passed. `git diff --check` passed.
- Remaining risk: no physical iPhone/iPad/Android device, App Store/Play signing pipeline, device secure-storage inspection, OS file-provider/share-sheet matrix, deep-link cold/warm-start matrix, VoiceOver/TalkBack run, or independent security/accessibility review is available locally. Push and live media also require the provider/privacy decisions already recorded as blocked. QA-016 is therefore READY FOR RETEST, not self-declared PASS.
