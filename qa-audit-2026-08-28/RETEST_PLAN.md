# Retest Plan

Prepared: 28 August 2026  
Purpose: verify remediation of QA-001 through QA-029 without weakening currently passing identity, authorization, privacy and workflow behavior.

## Retest entry criteria

Retest should begin only when:

1. Each targeted finding has a linked implementation change and developer test evidence.
2. Database migrations and rollback notes are available for scheduling, research or account changes.
3. A clean isolated staging environment uses PostgreSQL and Redis with production-like queue/session/storage settings.
4. Approved test accounts exist for Public, Patient A, Patient B, Moderator, Admin and Power Admin.
5. A non-production mail/SMS/push/video sandbox and approved malware-scanner test environment are configured where applicable.
6. No real patient data or real notification recipients are present.
7. Content owners provide an approved verification pack for any newly published biography/publication/contact copy.

## Retest order

### Phase 1 — Build and regression baseline

- Run backend, web and native typechecks/tests.
- Run production builds and record asset sizes.
- Run migrations from a fresh database and from the previous schema; run rollback in a disposable database.
- Confirm no secrets/debug configuration or real external destinations are enabled.

Exit: all existing tests remain green and no unplanned schema/data loss occurs.

### Phase 2 — P0 release blockers

#### QA-001 CMS route/editor

- As Power Admin, list pages and GET each numeric page ID successfully.
- As public visitor, fetch published page by slug.
- Confirm moderator/admin/patient receive 403 on protected endpoints.
- Run real browser select → edit → save draft → preview → publish → public verify.
- Repeat with two pages and non-numeric slugs to prove no collision.

#### QA-004/QA-005 booking integrity

- Build a slot truth table covering weekday rules, closures, leave, additional clinics, service duration, buffer and in-person/online rules.
- For every generated slot, submit succeeds once; for adjacent/arbitrary/stale/closed/off-rule/unsupported-method times, submit returns 422.
- Run simultaneous overlapping requests against PostgreSQL; assert exactly one commits.
- Repeat concurrency with create/create and create/reschedule at interval boundaries.

#### QA-013 research state integrity

- Verify allowed transitions pending_review → verified/rejected and verified → published.
- Reject/retract/unpublish behavior removes or clearly versions public visibility transactionally.
- Prove published cannot be silently changed to rejected without the defined retraction path.
- Verify null DOI cannot overwrite another record and DOI/PMID/title duplicates are handled intentionally.
- Assert /api/public and /api/academic/publications use the same defined publication lifecycle.

#### QA-018 mobile navigation

- Test open/close, Escape where applicable, focus order, aria-expanded/controls and close-on-route-change.
- Test keyboard and touch at 320, 375, 390, 414 and 768 px; confirm no horizontal overflow.

#### QA-022 upload scanning

- Use approved benign scanner test fixtures for clean, known-test-signature, renamed, oversized, malformed and scanner-timeout cases.
- Confirm quarantine before scan, fail-closed behavior, no public URL, authorized release/download and complete audit records.
- Never use malicious payloads outside the approved scanner test environment.

Exit: every P0 case passes with request/response, database and browser evidence.

### Phase 3 — Remaining High findings

#### QA-002/QA-003 visual CMS

Test the supplied requirement list explicitly:

- Edit Mode remains active while navigating public pages.
- Select and double-click paragraph; edit text, font family/size/weight, bold/italic/underline, alignment, color, line height, spacing and links.
- Replace/upload/select image; alt text, crop, dimensions, alignment, border radius and link.
- Edit button text/URL/internal route/style/icon/visibility.
- Menu rename/add/delete/reorder/submenu/destination/hide.
- Page create/rename/slug/SEO/publish/unpublish/duplicate.
- Section add/remove/duplicate/reorder/hide/background/spacing.
- Draft → preview → publish, then version history/restore.
- Published navigation/theme visibly changes the public site; draft does not leak.
- Repeat at desktop, tablet and mobile; test keyboard and screen-reader semantics.

#### QA-009 calendar

- Day/week/month/agenda presentation, timezone and date navigation.
- Create/edit/reschedule/drag-drop with server conflict rejection and rollback of failed drag.
- Patient, appointment-type and status filters; clear status text.
- Recurring rule and exception management.
- Keyboard access and responsive use at 320, 390, 768, 1024 and 1440 px.

#### QA-010 consultation

- Provider readiness and privacy configuration.
- Related patient and permitted staff can consent, wait, start, join and leave.
- Patient B, expired/shared/replayed direct URL and wrong appointment are denied.
- Provider unavailable/network interrupted/rejoin behavior is understandable.
- Camera/microphone permissions and no media before consent on physical iOS/Android/browser devices.
- Attendance and audit records are complete without exposing room secrets.

#### QA-015 administration

- Admin permitted patient operations and Power Admin account/role operations.
- Moderator/Admin cannot change protected roles/system settings.
- Inactive/unverified/last-Power-Admin and self-demotion edge cases.
- Re-authentication for sensitive changes and complete before/after audit.

#### QA-016 native scope

- Profile update, document upload/download, message compose/reply, notification mark-read, consultation actions, reminder preferences and device/session management.
- Online/offline/interrupted/retry/idempotency/session-expiry behavior.
- iOS/Android physical device, VoiceOver/TalkBack, font scaling and secure storage acceptance.

Exit: all High findings pass and no role/isolation regression occurs.

### Phase 4 — Medium functional/content/security

- QA-006: demonstrate documented slot_minutes and buffer semantics with boundary cases.
- QA-007: decline, resubmit with new reason, staff review and accurate UI state/audit/notification.
- QA-008: verify chosen rescheduled event/state model through API, calendar, portal, notifications and reports.
- QA-011/QA-012/QA-027: route/link sweep for Contact, 404, career, achievements, services, education/publication detail and legal pages.
- QA-014: fresh seed creates sourced queue claims only; migration cleans or reconciles title-only duplicates safely.
- QA-017: native appointment allowed actions exactly match server contract.
- QA-019/QA-020: automated accessibility scan plus keyboard, contrast, screen-reader and error announcement checks.
- QA-021: two web sessions plus two mobile tokens; reset revokes every other session/token and audits it.
- QA-023: audit catalogue verification for every critical mutation with safe before/after metadata.
- QA-024: force 422/403/404/409/429/500/network interruption for each mutation; assert preserved form, clear message, recovery and focus/announcement.
- QA-025: per-route title/description/canonical/OG and sitemap; 404 is not indexed as Home.
- QA-028: booking attachment is scanned, privately owned and retained/deleted by policy.
- QA-029: patient reschedule request, eligible-slot enforcement, staff decision, notifications and audit.

### Phase 5 — Low/performance and final release regression

- QA-026: compare initial requests/chunks and enforce an agreed bundle/request budget.
- Full public/patient/staff/Power Admin smoke suite.
- Patient A/B direct-ID isolation suite across web and v1.
- Cross-browser and responsive matrix.
- Backup/restore, queue/scheduler, monitoring and provider smoke in staging.

## Browser and device matrix

| Surface | Required coverage |
| --- | --- |
| Web engines | Current Chrome, Edge, Firefox and Safari/WebKit |
| Public widths | 320, 375, 390, 414, 768, 1024, 1280, 1440, 1920 px |
| Authenticated widths | 320, 390, 768, 1024 and 1440 px minimum |
| Zoom/accessibility | 200% and 400% reflow where applicable; keyboard-only; forced colors/high contrast; reduced motion |
| Native iOS | Small/large current iPhone plus iPad; portrait/landscape; VoiceOver and Dynamic Type |
| Native Android | Small/large phone plus tablet; portrait/landscape; TalkBack and font/display scaling |
| Networks | Normal, slow, intermittent, offline/reconnect and provider timeout |

## Test data

- Use names such as QA Patient A 20260828 and QA Patient B 20260828.
- Use reserved non-deliverable email/phone values and local provider sandboxes.
- Tag every appointment, document and message with the test run ID.
- Use only benign documents and approved scanner test fixtures.
- Document cleanup/retention in the isolated environment; never delete production or unknown records.

## Evidence required per retest

- Finding ID and build revision.
- Environment/config boundary (with secrets redacted).
- Exact account role and test record IDs.
- Steps, expected and actual result.
- Request/response status and safe body excerpt.
- Relevant database/audit state before and after.
- Browser/device/version/viewport.
- Screenshot/video for visual/accessibility behavior where useful.
- Automated test name and CI link.
- PASS, FAIL, BLOCKED or NOT TESTABLE decision with reason.

## Exit criteria for production recommendation

- Zero open Critical or High findings.
- All P0/P1 tests pass on production-like PostgreSQL/Redis staging.
- No cross-patient/role access failure.
- Booking concurrency and availability parity pass.
- Approved malware scanner and consultation provider pass failure-mode tests.
- Required CMS/calendar/native workflows pass end to end.
- Accessibility critical journeys pass automated and manual acceptance.
- Content/clinical/legal approval is documented.
- Backup restore and rollback drill succeeds.
- Residual Medium/Low findings have explicit owner, acceptance and release-risk approval.
