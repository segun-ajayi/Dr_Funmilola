# Automated Test Coverage Gaps

Audit date: 28 August 2026

## Current automated baseline

| Suite | Result | What it demonstrates |
| --- | --- | --- |
| Laravel/PHPUnit | 58 passed, 302 assertions, 123.04 s | Strong API-level identity, authorization, isolation and domain-service coverage |
| Web Vitest | 3 files, 7 tests passed, 23.53 s | A small set of component/mocked API behaviors |
| Native Jest | 4 suites, 6 tests passed, 42.247 s | Contract/helper/session and mutation-ID basics |
| Type/build | Web and native typechecks pass; web production build passes | Static types and compilation, not workflow correctness |

All existing tests were green. No test failure ticket is required. The concern is coverage depth: the repository plan describes complete frontend/end-to-end/authorization testing, while only seven web and six native tests exist and no browser E2E framework/spec was found.

## High-priority missing tests

| Priority | Missing test | Regression it would have caught | Finding |
| --- | --- | --- | --- |
| P0 | Authenticated GET /api/cms/pages/{numericId} route integration | Public slug route shadowing protected page load | QA-001 |
| P0 | Real CMS editor page-select/draft/preview/publish E2E | Editor cannot load; mocked frontend API hides defect | QA-001 |
| P0 | Booking POST must equal a generated availability slot | Off-rule/closed/arbitrary time accepted | QA-004 |
| P0 | Booking POST enforces service online eligibility and rule method | Unsupported online Breast Surgery accepted | QA-004 |
| P0 | True concurrent overlapping booking against PostgreSQL | Check-then-insert race | QA-005 |
| P0 | Mobile menu at 320/390/768 with keyboard and aria assertions | Dead Bootstrap data-attribute toggle | QA-018 |
| P0 | Upload quarantine/approved scanner clean/reject/timeout | Basic scanner released as production-ready | QA-022 |
| P1 | Decline cancellation then patient resubmits with new reason | Permanent declined row and false UI state | QA-007 |
| P1 | Reschedule lifecycle/status/display | Reschedule saved as Confirmed; Rescheduled unused | QA-008 |
| P1 | Research publish then reject/unpublish/retract | Rejected content remains public | QA-013 |
| P1 | Null DOI and duplicate title publication behavior | Unsafe updateOrCreate and duplicate rows | QA-013, QA-014 |
| P1 | Password reset invalidates all web sessions and mobile tokens | Browser session survives reset | QA-021 |
| P1 | Role/account/patient management authorization and audit | Required operations are absent | QA-015 |

## Coverage by requested risk area

### 1. Authorization

Existing: broad route/role matrix and policies for patient/staff/Power Admin.  
Missing: frontend direct-route guards as rendered behavior; account/role management (not implemented); full mutation-by-role matrix for every staff operation; CMS numeric route integration; separate verified/unverified staff expectations.

### 2. Patient isolation

Existing: appointment list/show, profile, documents, messaging and consultation ownership tests. Runtime foreign appointment ID returned 403.  
Missing: complete Patient A/B browser journeys, manipulated document download in a real session, notification IDs, native v1 cross-patient IDs, indirect payload leakage assertions and staff minimum-necessary response snapshots.

### 3. Appointment conflicts

Existing: sequential exact/overlap checks.  
Missing: genuine concurrency; PostgreSQL behavior; duration/buffer boundary matrix; reschedule versus simultaneous create; cancelled/no-show/rescheduled record interactions; timezone/DST assumptions; off-rule submission.

### 4. Booking workflow

Existing: validation and simple component flow.  
Missing: public browser start-to-confirmation E2E; service method enforcement; closures/additional clinic parity between GET/POST; stale slot; booking attachment; duplicate submit/idempotency; network retry; accessible field errors.

### 5. Role restrictions

Existing: patient/moderator/admin/Power Admin API access checks.  
Missing: rendered navigation plus direct frontend URL bypass, staff field-level/minimum-necessary data assertions, inactive/unverified role matrix across all endpoints and privilege changes (feature absent).

### 6. Power Admin restrictions/CMS

Existing: backend page create/update/preview/publish/version/settings/research tests, many with direct controller routes or mocks.  
Missing: real route ordering; end-to-end editor; full required controls; inline edit; managed media; menu/theme public effect; unpublish/duplicate; version history/restore UI; accessibility and mobile editor tests.

### 7. CMS publishing

Existing: snapshot/version/publish service behavior.  
Missing: stale draft/publish conflicts, two-admin concurrency, rollback public effect, settings renderer integration, invalid structured content, XSS payload rendering, broken internal links, cache invalidation and soft-deleted asset behavior.

### 8. File privacy

Existing: allowlist/size rules, basic scanner rejection and authorization coverage.  
Missing: successful upload/download audit, renamed double-extension, polyglot/container cases, oversized runtime, direct storage route, Content-Disposition filename safety, scanner timeout/unavailability, quarantine release and retention deletion.

### 9. Consultation access

Existing: ownership, consent, waiting room, signed authorization/expiry and status transitions.  
Missing: real provider, direct shared URL in a second account/browser, replay, clock boundary, staff role differences, provider failure/retry, simultaneous join/leave, audit records, physical camera/mic permission and native deep link.

## Other material gaps

### Calendar

No rendered calendar behavior test exists for day/week/month/agenda, creation/editing, drag/drop, filters, timezone or mobile responsiveness. The current component cannot satisfy those tests because the behaviors are missing (QA-009).

### Public content and routing

Missing route sweep for 404/contact, link integrity, legal destinations, services list, education/publication detail, DOI/external link, pagination/sort, per-route metadata, sitemap and published CMS/settings effect.

### Accessibility

No axe or equivalent check, keyboard journey, focus assertion, accessible-name assertion, contrast regression, zoom/reflow or screen-reader acceptance. Sign-in labels and mobile navigation would be caught immediately.

### Error handling

No component tests for network/422/403/404/500 on portal, staff or CMS mutations. No assertion that input is preserved, a recovery action exists or error focus/live announcement occurs.

### Native

No screen render tests for appointments/documents/messages/notifications/consultations, no mutation flow beyond ID helper, and no offline/refresh/session-expiry/deep-link/physical accessibility tests. Required actions are absent (QA-016).

### Performance

No bundle budget, route-chunk assertion, unnecessary-request test, query-count/N+1 threshold or browser performance measurement.

## Recommended test architecture

1. Keep current Laravel feature tests as the server contract layer.
2. Add PostgreSQL integration jobs for concurrency, constraints and production query behavior.
3. Add browser E2E coverage for public booking, patient portal, moderator/admin workspace and Power Admin CMS using isolated seeded accounts.
4. Replace broad API mocks in critical frontend tests with contract fixtures plus at least one real-backend journey.
5. Add axe rules to component/E2E tests and retain manual keyboard/screen-reader acceptance.
6. Add React Native screen tests, API mutation contract tests and a physical-device smoke matrix.
7. Gate release on high-risk scenario coverage, not raw test count.

## Minimum regression pack before retest sign-off

- CMS route/editor journey.
- Availability-submission parity and concurrent overlap.
- Cancellation decline/resubmit and reschedule state.
- Mobile navigation.
- Role matrix and Patient A/B isolation.
- Real scanner/quarantine and private download.
- Consultation provider/access/replay/expiry.
- Research publish/reject/retract and unique identifier rules.
- Multi-session password reset.
- Automated accessibility scan plus manual critical workflows.
