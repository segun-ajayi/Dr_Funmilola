# Findings Remediation Plan

Prepared: 28 August 2026  
Purpose: give an implementation agent a dependency-ordered, evidence-driven path to close every audit finding without silently skipping any item.  
Canonical finding inventory: QA_FINDINGS.csv and QA_FINDINGS.md in this folder.  
Starting release position: DO NOT RELEASE.

## Non-negotiable completion rule

A finding is not closed because code was added, a page exists, or a unit test passes. It is closed only when all of the following are recorded:

1. The implementation satisfies the linked requirement rows in REQUIREMENTS_TRACEABILITY_MATRIX.md.
2. The original reproduction no longer produces the defect.
3. Positive, negative, authorization, accessibility and failure-path tests appropriate to the change pass.
4. Runtime evidence is captured in a production-like staging environment.
5. No previously passing role, privacy or patient-isolation behavior regresses.
6. The finding's row in the closure ledger contains the revision, test names and evidence location.

The audit reports are immutable evidence. Do not edit QA_FINDINGS.md, QA_FINDINGS.csv or the original matrix to make a defect appear closed. Record remediation evidence in this plan or a new REMEDIATION_PROGRESS.md, then have independent QA update statuses after retest.

## Agent operating procedure

For every implementation step below, use this exact cycle:

1. Read the full finding in QA_FINDINGS.md and all linked matrix rows.
2. Reproduce the current behavior in an isolated environment.
3. Identify the root cause and dependent workflows before editing.
4. Write or update a failing automated regression test first where practical.
5. Implement the smallest complete domain fix; do not patch only the visible symptom.
6. Run the targeted test, then the relevant domain suite.
7. Perform the specified browser/API/device retest.
8. Run authorization and Patient A/Patient B isolation checks if sensitive data is involved.
9. Record changed files, migrations, tests, runtime evidence and residual risk.
10. Mark the ledger PASS only after every acceptance condition is satisfied.
11. Commit the step independently so it can be reviewed or reverted.
12. Do not begin a dependent step until its prerequisite gate is green.

## Environment and data rules

- Use development or staging only, never production or real patient data.
- Use clearly named Patient A, Patient B, Moderator, Admin and Power Admin test accounts.
- Use PostgreSQL and Redis before closing scheduling, session, queue or concurrency findings.
- Use provider sandboxes for mail, SMS, push, malware scanning and video.
- Keep APP_DEBUG=false during runtime acceptance checks.
- Preserve secrets outside source control and redact them from evidence.
- Back up disposable test data before migration rehearsals; never delete unknown data.
- Run schema migration, rollback and upgrade-path checks for every data-model change.

## Dependency map

Work in this order:

1. Baseline and progress controls.
2. Unblock CMS routing: QA-001.
3. Correct booking and scheduling integrity: QA-004, QA-005, QA-006.
4. Establish secure file handling: QA-022.
5. Complete booking attachments: QA-028.
6. Correct appointment-change workflows: QA-007, QA-008, QA-017, QA-029.
7. Build the required visual CMS: QA-002, QA-003.
8. Correct research/content data lifecycle: QA-013, QA-014.
9. Complete public content, routes, legal destinations and SEO: QA-011, QA-012, QA-025, QA-027.
10. Repair responsive navigation, accessibility and error recovery: QA-018, QA-019, QA-020, QA-024.
11. Build the full operational calendar: QA-009.
12. Activate a real consultation provider: QA-010.
13. Add administration, session revocation and complete auditing: QA-015, QA-021, QA-023.
14. Complete the native patient application: QA-016, consuming the already-corrected allowed-action, reschedule and consultation contracts.
15. Optimize route/data loading: QA-026.
16. Run full regression, independent retest and release gate.

## Step 0 — Freeze the baseline and create progress evidence

Actions:

1. Record the starting revision and environment versions.
2. Run the existing backend, web and native suites and production builds.
3. Record the current baseline: 58 backend tests/302 assertions, 7 web tests and 6 native tests.
4. Create isolated seeded accounts for every role and Patient A/Patient B.
5. Configure production-like PostgreSQL, Redis, private storage, queue workers and scheduler in staging.
6. Create a remediation evidence folder organized as QA-001 through QA-029.
7. Copy the closure ledger below into REMEDIATION_PROGRESS.md if concurrent agents will work on different steps.

Gate 0:

- Baseline is reproducible.
- Test data is isolated.
- Every QA-001 through QA-029 row has an owner or is explicitly queued.
- No finding is marked closed at baseline.

## Step 1 — Unblock the CMS editor route

Finding: QA-001  
Prerequisite: Step 0.

Implementation work:

1. Separate the public page-by-slug endpoint from the protected page-by-ID endpoint, or apply unambiguous route constraints.
2. Confirm route order cannot shadow protected actions.
3. Preserve public slug fetching and preview behavior.
4. Add an authenticated route integration test using a numeric page ID.
5. Replace the critical frontend-only mock with at least one real-backend editor journey.

Required tests:

- Public GET by slug succeeds only for published snapshots.
- Power Admin GET by numeric ID succeeds and includes editable sections.
- Patient, Moderator and Admin receive 403 for protected page data.
- Missing public slug and missing protected ID return different, safe 404 responses.
- Browser: select page, edit one draft field, preview, publish and verify public output.

Exit gate:

- QA-001 independently retests PASS.
- REQ-074, REQ-075, REQ-076, REQ-080, REQ-082 and REQ-138 are no longer blocked by routing.

## Step 2 — Make booking submission use scheduling truth

Findings: QA-004, QA-005, QA-006  
Prerequisite: Step 0.

Implementation work:

1. Define one authoritative server-side function that determines whether a submitted start time is a valid current slot for the selected service and method.
2. Enforce service online_available, availability-rule method, closures, leave, additional clinics, duration, buffer and future-time rules at POST time.
3. Define the intended meaning of slot_minutes; use it consistently or remove/rename it through a safe migration and contract update.
4. Make booking and staff reschedule call the same availability decision.
5. Close the check-then-insert race using an appropriate PostgreSQL transaction/locking or exclusion-constraint design.
6. Map stale/conflicting slot failures to a safe 409 or 422 with recovery guidance.

Required tests:

- Each generated slot can be booked once.
- Arbitrary, Saturday/off-rule, closed, leave, past and stale times fail.
- In-person/online method matrices fail when either service or rule disallows the method.
- Exact boundary, partial overlap, duration and buffer cases.
- Additional-clinic slots succeed only within the exception.
- True simultaneous create/create and create/reschedule tests against PostgreSQL commit exactly one overlap.
- Cancelled/no-show semantics are explicitly tested.

Exit gate:

- QA-004 and QA-006 reproduce as fixed.
- QA-005 concurrent test passes repeatedly in CI on PostgreSQL.
- Availability GET and booking/reschedule POST cannot disagree.

## Step 3 — Establish the secure upload pipeline

Finding: QA-022  
Prerequisite: Step 0. Must finish before Step 4.

Implementation work:

1. Bind FileScannerInterface to an approved production scanner or managed service.
2. Store incoming files in quarantine with no patient/staff download access.
3. Validate size, MIME, extension and safe filename before scanning.
4. Fail closed on malware, scanner timeout, unavailable scanner or indeterminate result.
5. Release only clean files to the private document store.
6. Record safe audit events for accepted, rejected, timed-out and downloaded files.
7. Add monitoring/alerting and retention for quarantined/rejected objects.

Required tests:

- Clean PDF/JPEG/PNG succeeds.
- Unsupported, renamed, double-extension, oversized and malformed files fail.
- Approved benign malware-test signature is rejected.
- Scanner timeout/unavailability fails closed without orphan metadata/file.
- Direct storage access and Patient A → Patient B download fail.
- Staff access is policy-scoped and audited.

Exit gate:

- QA-022 independently retests PASS in the configured staging scanner environment.
- Scanner provider, hosting region, retention and incident response are approved.

## Step 4 — Add booking-time attachment safely

Finding: QA-028  
Prerequisites: Steps 2 and 3.

Implementation work:

1. Define whether a public booking attachment belongs to a temporary booking identity or a claimed patient account.
2. Add the permitted upload to the booking UI and request contract without collecting unnecessary health data.
3. Route it through the quarantine/scanner pipeline from Step 3.
4. Attach the clean file transactionally to the created appointment/patient.
5. Define abandonment, duplicate submission and retention behavior.
6. Provide accessible label, progress, success, validation and retry states.

Required tests:

- Booking with no optional file, clean file and rejected file.
- Appointment creation rolls back or clearly recovers when scan/storage fails.
- Duplicate booking submission does not duplicate files.
- The resulting document is visible only to the correct patient and authorized staff.
- Full 320/390/768/desktop browser journey.

Exit gate:

- QA-028 PASS and REQ-039 PASS with security/privacy evidence.

## Step 5 — Correct cancellation, reschedule and allowed actions

Findings: QA-007, QA-008, QA-017, QA-029  
Prerequisite: Step 2.

Implementation work:

1. Model cancellation requests as repeatable events or explicitly reset a declined request to a new pending state.
2. Preserve new reason, reviewer, decision, timestamps, notification and audit history.
3. Define Rescheduled as an event or durable state and align the state machine, reporting, filters and display.
4. Add a patient reschedule-request workflow with eligible server-generated slots and staff approval/decline.
5. Return server-derived allowed_actions with each appointment.
6. Render both web and native controls from allowed_actions instead of duplicated status lists.

Required tests:

- Cancellation pending → approved and pending → declined.
- Declined → new request with new reason; UI shows each state accurately.
- Reschedule from each valid/invalid state, including conflict/stale slot.
- Reschedule request approve/decline and patient notification.
- Web/native action visibility matches API for every appointment status.
- Patient A cannot act on Patient B's appointment.

Exit gate:

- QA-007, QA-008, QA-017 and QA-029 all PASS together.
- No action-state mismatch exists across API, portal, staff calendar or native app.

## Step 6 — Deliver the full visual CMS

Findings: QA-002, QA-003  
Prerequisite: Step 1.

Implementation work:

1. Define versioned, validated schemas for editable components and presentation fields.
2. Provide persistent Edit Mode while navigating public pages.
3. Add element selection and double-click inline paragraph editing.
4. Implement font family, size, weight, bold, italic, underline, alignment, color, line height, spacing and link editing.
5. Add managed media upload/library/replace, required alt/decorative choice, crop, dimensions, alignment, border radius and link.
6. Add button text, URL/internal route, style, icon and visibility.
7. Add menu rename/add/delete/reorder/submenu/destination/hide/show.
8. Add page create/rename/slug/SEO/publish/unpublish/duplicate.
9. Add section add/remove/duplicate/reorder/hide/background/spacing.
10. Expose version history and rollback.
11. Make published navigation/theme settings drive the public renderer while drafts remain private.
12. Sanitize/validate every structured content path and preserve safe links/media.

Required tests:

- One E2E test for every numbered capability above.
- Draft is not public; preview is token-protected/expiring; publish changes public output; rollback restores it.
- Concurrent Power Admin edit/version conflict handling.
- Patient/Moderator/Admin direct API and frontend route denial.
- XSS, unsafe link, invalid media and schema-version migration tests.
- Keyboard, screen-reader and responsive editor tests.

Exit gate:

- QA-002 and QA-003 PASS.
- Every REQ-065 through REQ-085 row is independently retested, not batch-assumed.

## Step 7 — Repair research and publication lifecycle

Findings: QA-013, QA-014  
Prerequisite: Step 0. Complete before public content expansion in Step 8.

Implementation work:

1. Define a constrained state machine: pending_review, verified, rejected, published and any explicit retracted/unpublished state.
2. Prevent generic decide from changing a published claim without the dedicated retraction workflow.
3. Make publish/retract transactional across ResearchClaim and target record.
4. Use stable source/identifier identity; add safe uniqueness rules for DOI/PMID and explicit null-identifier handling.
5. Reconcile /api/public and /api/academic publication filters to one lifecycle.
6. Replace direct title-only Publication seeds with fully sourced ResearchClaim seeds.
7. Write a reviewed migration to merge/remove incomplete duplicates without losing approved records.

Required tests:

- Every allowed and forbidden state transition.
- Publish then retract/reject removes public visibility as defined.
- Null DOI cannot overwrite another publication.
- Duplicate DOI, PMID and normalized title behavior.
- Fresh seed creates queue records only and retains source URL/title/payload.
- Existing database upgrade produces no duplicate public records.

Exit gate:

- QA-013 and QA-014 PASS with database before/after evidence.
- Rejected or retracted material cannot appear in any public feed.

## Step 8 — Complete public content, routing, legal pages and SEO

Findings: QA-011, QA-012, QA-025, QA-027  
Prerequisites: Steps 6 and 7 for CMS-driven content.

Implementation work:

1. Add complete Contact and dedicated 404 routes/pages.
2. Add verified professional profile, career, achievements and dated fellowship content.
3. Restore complete service cards/details from one authoritative source.
4. Add education list-to-detail routes.
5. Add publication sort, pagination, detail, author, DOI and external-source links.
6. Add Academic/Education/Contact destinations to approved navigation.
7. Create approved Privacy, Terms and Accessibility pages and real footer links.
8. Add per-route/CMS title, description, canonical and Open Graph metadata.
9. Generate a sitemap from published pages and ensure 404/contact behavior uses correct status/canonical rules.
10. Obtain content-owner, clinical and legal approval before publication.

Required tests:

- Automated route/link crawl with zero unexpected soft 404s/dead links.
- Complete list/search/filter/sort/paginate/detail/DOI/external-link journeys.
- Only verified/published content appears.
- Per-route metadata and sitemap assertions.
- Spelling, grammar, source and duplicate checks.

Exit gate:

- QA-011, QA-012, QA-025 and QA-027 PASS.
- Every public requirement REQ-003 through REQ-017 has fresh runtime evidence.

## Step 9 — Repair responsive navigation, accessibility and error recovery

Findings: QA-018, QA-019, QA-020, QA-024  
Prerequisite: Steps 1–8 should have stable interfaces before final accessibility work.

Implementation work:

1. Replace the CSS-only Bootstrap collapse dependency with a controlled React disclosure.
2. Implement aria-expanded/aria-controls, keyboard behavior, close-on-navigation and appropriate focus behavior.
3. Give every input/select/textarea a visible associated label and field error relationship.
4. Add a skip-to-main link and consistent focus-visible tokens; never suppress focus without replacement.
5. Give icon-only controls explicit accessible names.
6. Fix small-text colors to WCAG AA; preserve 3:1 non-text/focus contrast.
7. Standardize mutation pending/success/error handling with form preservation, retry, focus and aria-live announcements.
8. Add reduced-motion, zoom/reflow and forced-colors support where needed.

Required tests:

- Mobile menu at 320, 375, 390, 414 and 768 px with keyboard and touch.
- Automated axe checks on public, auth, booking, portal, staff and CMS critical routes.
- Manual keyboard order/focus and 200%/400% reflow.
- NVDA/JAWS or equivalent on Windows, VoiceOver on iOS/Safari and TalkBack on Android.
- Force 422/403/404/409/429/500/network errors for each major mutation; verify understandable recovery.
- Computed contrast evidence for all semantic tokens.

Exit gate:

- QA-018, QA-019, QA-020 and QA-024 PASS.
- No serious automated accessibility violations and manual critical journeys pass.

## Step 10 — Build the operational calendar

Finding: QA-009  
Prerequisites: Steps 2 and 5.

Implementation work:

1. Implement distinct accessible Day, Week, Month and Agenda views.
2. Add appointment creation, detail/edit and reschedule actions.
3. Add keyboard-accessible drag/drop with an equivalent non-drag method.
4. Revalidate every create/move against the scheduling truth from Step 2.
5. Add patient, appointment-type and status filters and clear status text.
6. Expose recurring availability rules and exceptions in the staff UI.
7. Keep Africa/Lagos display explicit and server UTC storage consistent.
8. Make desktop, tablet and mobile layouts usable without horizontal traps.

Required tests:

- All four views and date navigation.
- Create/edit/reschedule/drag plus failure rollback for stale/conflicting slot.
- Filters alone and combined.
- Rule/exception CRUD and authorization.
- Keyboard and screen-reader calendar interaction.
- 320, 390, 768, 1024 and 1440 px responsive checks.

Exit gate:

- QA-009 PASS and REQ-053, REQ-055, REQ-059, REQ-095 through REQ-099 and REQ-137 pass.

## Step 11 — Activate real online consultations

Finding: QA-010  
Prerequisites: approved privacy/provider decision; Step 5 appointment lifecycle.

Implementation work:

1. Select and approve a provider, hosting region, data-processing terms, retention and incident controls.
2. Implement VideoProviderInterface adapter without exposing provider secrets/room locators.
3. Complete patient consent, waiting room, join, leave/rejoin and staff start/end flows.
4. Preserve appointment ownership, short-lived authorization and replay/expiry protections.
5. Implement provider outage, network interruption and device-permission recovery.
6. Add complete attendance/audit events without storing unnecessary media data.
7. Integrate web and native clients only after the shared contract passes.

Required tests:

- Related patient and permitted staff success.
- Patient B, wrong appointment, expired/shared/replayed link and inactive user denial.
- Consent required before media; no camera/mic access early.
- Provider unavailable, token failure, disconnect/rejoin and session end.
- Browser/iOS/Android physical-device audio/video acceptance.

Exit gate:

- QA-010 PASS as fully functional, not mocked/placeholder.
- Security/privacy/provider approvals are recorded.

## Step 12 — Add administration, full audit and session revocation

Findings: QA-015, QA-021, QA-023  
Prerequisite: stable domain actions from earlier steps.

Implementation work:

1. Define minimum-necessary Admin patient operations and Power Admin account/role operations.
2. Add policy-protected create/edit/deactivate/invite/role-change flows.
3. Require re-authentication for sensitive changes and protect last-Power-Admin/self-demotion edge cases.
4. On password reset, revoke other browser sessions and all mobile/API tokens transactionally.
5. Provide audited device/session listing and revocation.
6. Define an audit-event catalogue covering appointment, cancellation, reschedule, patient, document upload/download, messages, consultation actions, CMS, publishing, roles, settings and security events.
7. Store actor, action, resource, timestamp and safe old/new values where required; never log secrets or message/clinical bodies unnecessarily.

Required tests:

- Full role matrix for every new operation.
- Inactive/unverified accounts and privilege-escalation attempts.
- Patient A/B isolation after admin changes.
- Two browser sessions plus two mobile tokens: reset revokes every other session/token.
- Audit assertion for every critical mutation, including before/after and redaction.
- Audit query authorization, filtering, pagination and retention.

Exit gate:

- QA-015, QA-021 and QA-023 PASS.
- Independent reviewer confirms no new privilege or privacy regression.

## Step 13 — Complete the native patient application

Finding: QA-016  
Prerequisites: Steps 3–5, 9, 11 and 12 provide stable server contracts.

Implementation work:

1. Add v1 profile update.
2. Add scanned document upload and authorized download.
3. Add message compose/reply.
4. Add notification mark-read.
5. Add consultation consent/wait/join/leave using the approved provider.
6. Add reminder preferences and device/session management.
7. Add configured push registration/delivery only after privacy approval.
8. Preserve idempotency for every retryable mutation.
9. Implement offline, interruption, session-expiry and retry behavior.
10. Render appointment actions from the server allowed_actions contract.

Required tests:

- API contract and authorization tests for every new v1 mutation.
- Native screen/component tests for loading, empty, success, validation, auth expiry and network error.
- End-to-end Patient A/B isolation.
- Physical iOS/Android secure storage, deep link, file, push, consultation and accessibility tests.
- Store privacy/data-safety checklist and screenshots reviewed.

Exit gate:

- QA-016 PASS and REQ-140 through REQ-145 independently pass.
- QA-017 and QA-029 remain green in native regression.

## Step 14 — Optimize route and data loading

Finding: QA-026  
Prerequisite: interfaces stable after functional work.

Implementation work:

1. Lazy-load public, patient, staff and Power Admin route groups.
2. Scope /api/public and /api/me queries to actual consumers.
3. Debounce academic search and preserve query state.
4. Define route-level JS/CSS/request and interaction performance budgets.
5. Inspect backend query counts and pagination for the completed content/calendar screens.

Required tests:

- Chunk map proves unrelated staff/CMS/native-web code is absent from public initial route.
- Public/auth/staff routes make only expected requests.
- Bundle and key interaction metrics improve without loading/error regressions.
- Search does not send a request per keystroke.

Exit gate:

- QA-026 PASS against the agreed measurable budget.

## Step 15 — Full regression and independent release retest

Prerequisite: Steps 1–14 complete.

Actions:

1. Run all backend, web, native, E2E, accessibility and production build gates.
2. Run the role matrix for Patient, Moderator, Admin and Power Admin.
3. Run Patient A/B isolation across appointments, profile, documents, messages, notifications and consultation.
4. Repeat the nine-width public sweep and authenticated responsive matrix.
5. Run Chrome, Edge, Firefox and Safari/WebKit.
6. Run physical Android/iOS acceptance.
7. Run PostgreSQL concurrency, Redis queue/scheduler, provider failure, backup restore and rollback drills.
8. Re-audit public content and all external links against approved primary sources.
9. Independently retest QA-001 through QA-029 using the original reproduction steps.
10. Re-score all 150 matrix rows; do not infer PASS from a related finding.
11. Obtain product, clinical/content, privacy/security and operational sign-off.

Final exit gate:

- Zero open Critical or High findings.
- Every QA-001 through QA-029 row is PASS or has documented, explicitly accepted residual risk for a non-release-blocking Medium/Low item.
- Every previously Failed, Missing or Blocked requirement has fresh evidence.
- No cross-patient access or privilege escalation.
- Backup restore and rollback succeed.
- Independent QA, not the implementation agent, issues the new release recommendation.

## Finding closure ledger

Status values: NOT STARTED, IN PROGRESS, BLOCKED, READY FOR RETEST, PASS, REOPENED. Do not use PASS without evidence.

| Finding | Step | Initial status | Minimum closure evidence | Revision / evidence link |
| --- | ---: | --- | --- | --- |
| QA-001 | 1 | NOT STARTED | Numeric protected page 200, public slug works, role denials and real editor E2E | — |
| QA-002 | 6 | NOT STARTED | Every visual CMS control in REQ-065–084 passes E2E/accessibility | — |
| QA-003 | 6 | NOT STARTED | Published nav/theme visibly changes public renderer; draft does not | — |
| QA-004 | 2 | NOT STARTED | Off-rule/closed/method-invalid POSTs fail; generated slot succeeds | — |
| QA-005 | 2 | NOT STARTED | Repeated PostgreSQL concurrent test commits exactly one overlap | — |
| QA-006 | 2 | NOT STARTED | slot_minutes/buffer semantics documented and boundary-tested | — |
| QA-007 | 5 | NOT STARTED | Declined cancellation can be resubmitted and displays accurately | — |
| QA-008 | 5 | NOT STARTED | Reschedule lifecycle consistent in DB/API/UI/audit/notifications | — |
| QA-009 | 10 | NOT STARTED | Day/week/month/agenda plus operations/filters/responsive/a11y pass | — |
| QA-010 | 11 | NOT STARTED | Approved live provider passes ownership/expiry/media/failure/device tests | — |
| QA-011 | 8 | NOT STARTED | Contact route and true 404 pass direct/link/status tests | — |
| QA-012 | 8 | NOT STARTED | Complete verified public profile/service/education/publication journeys | — |
| QA-013 | 7 | NOT STARTED | Transactional state machine and retract/public-feed consistency tests | — |
| QA-014 | 7 | NOT STARTED | Fresh seed uses sourced queue only; duplicate migration verified | — |
| QA-015 | 12 | NOT STARTED | Patient/account/role admin operations pass role/audit edge cases | — |
| QA-016 | 13 | NOT STARTED | All required native mutations pass API, E2E, device and a11y tests | — |
| QA-017 | 5 | NOT STARTED | Web/native actions exactly match server allowed_actions for all states | — |
| QA-018 | 9 | NOT STARTED | Mobile menu passes touch/keyboard/ARIA at all collapsed widths | — |
| QA-019 | 9 | NOT STARTED | Labels, skip link, focus, names and announcements pass manual/axe checks | — |
| QA-020 | 9 | NOT STARTED | Semantic text/control/focus tokens meet WCAG contrast thresholds | — |
| QA-021 | 12 | NOT STARTED | Password reset revokes other web sessions and mobile/API tokens | — |
| QA-022 | 3 | NOT STARTED | Approved scanner/quarantine passes clean/reject/timeout/privacy tests | — |
| QA-023 | 12 | NOT STARTED | Audit catalogue actions produce safe actor/resource/before-after records | — |
| QA-024 | 9 | NOT STARTED | Every major mutation shows preserved, announced, recoverable failure | — |
| QA-025 | 8 | NOT STARTED | Per-route title/description/canonical/OG plus sitemap/404 checks | — |
| QA-026 | 14 | NOT STARTED | Route chunks/requests meet recorded performance budget | — |
| QA-027 | 8 | NOT STARTED | Approved Privacy/Terms/Accessibility pages linked and crawl-tested | — |
| QA-028 | 4 | NOT STARTED | Booking attachment is scanned, private, owned, audited and accessible | — |
| QA-029 | 5 | NOT STARTED | Patient reschedule request approve/decline/conflict/audit/notify passes | — |

## Machine-checkable omission gate

Before final sign-off, compare the canonical IDs in QA_FINDINGS.csv with the closure ledger. The sets must be identical and every row must have evidence. A simple check can extract QA-### identifiers from both files, sort them uniquely and fail if there is any difference. The expected set is exactly QA-001 through QA-029 with no gaps.

Manual final checklist:

- [ ] QA-001 through QA-029 each appear exactly once in the closure ledger.
- [ ] Every ledger status is PASS or an explicitly approved non-blocking residual risk.
- [ ] Every PASS row contains a revision and evidence link.
- [ ] All 150 requirements were re-evaluated individually.
- [ ] All automated suites and production builds pass.
- [ ] Cross-role and Patient A/B isolation pass.
- [ ] PostgreSQL concurrency passes.
- [ ] Scanner, video, mail/SMS/push and queue failure modes pass where enabled.
- [ ] Accessibility and responsive device/browser matrices pass.
- [ ] Content, privacy/security, clinical and operational approvals are recorded.
- [ ] Independent QA issues the release recommendation.
