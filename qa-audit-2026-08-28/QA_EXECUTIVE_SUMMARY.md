# QA Executive Summary

Project: Dr. Funmilola Olanike Wuraola Breast Oncology Practice Platform  
Independent audit date: 28 August 2026  
Audited revision: 2424cb1f68232f3b10d2e6f43be4536e7091d5df  
Release recommendation: **DO NOT RELEASE**

## Outcome

The platform has a credible technical foundation and a polished, appropriate public visual identity. All current automated suites pass, core role boundaries worked in runtime testing, and no Critical security finding or cross-patient data access was reproduced in the tested scope.

It is not ready for production. Eleven High findings remain, including a blocked CMS editor, server-side acceptance of unavailable appointments, a concurrency double-booking risk, an incomplete calendar, no live consultation provider, absent account/patient administration, a read-mostly native app, broken mobile navigation, inconsistent research-publishing state and no production-grade upload malware scanner.

The repository plan labels major phases Complete, but the traceability audit found 21 failed, 24 missing and 5 blocked requirements. Passing tests are substantially narrower than the full product acceptance criteria.

## Counts

### Requirements

| Metric | Count |
| --- | ---: |
| Total Requirements | 150 |
| Passed | 57 |
| Partial | 39 |
| Failed | 21 |
| Missing | 24 |
| Blocked | 5 |
| Not Testable | 4 |
| Not Applicable | 0 |

### Findings

| Metric | Count |
| --- | ---: |
| Total Findings | 29 |
| Critical | 0 |
| High | 11 |
| Medium | 16 |
| Low | 2 |
| Info | 0 |

## Release recommendation

**DO NOT RELEASE**

The build should remain in development. A staging candidate should not be promoted until the P0 and High findings pass the targeted retest plan on production-like PostgreSQL/Redis infrastructure with an approved malware scanner and, if online consultation remains in scope, an approved live provider.

Reasons:

- Booking truth can be bypassed through the public API and concurrent requests are not transactionally safe.
- The Power Admin CMS is both functionally blocked and far below the required visual editing scope.
- The calendar, consultation, administration and native patient workflows are materially incomplete.
- The public mobile menu prevents ordinary phone navigation.
- Content publication state can diverge after rejection.
- Sensitive uploads are not scanned by a production-grade service.

## Highest-risk findings

| Finding | Severity | Release impact |
| --- | --- | --- |
| QA-001 — Protected CMS page load is shadowed | High | Draft/preview/publish editor journey blocked |
| QA-002 — Visual CMS substantially missing | High | Core Power Admin requirement not delivered |
| QA-004 — Booking accepts unavailable/method-invalid requests | High | Invalid clinical schedule requests can be created |
| QA-005 — Conflict check is concurrency-unsafe | High | Simultaneous requests can double-book |
| QA-009 — Full calendar absent | High | Staff cannot perform required schedule operations |
| QA-010 — Live consultation unconfigured | High | Online service cannot be delivered |
| QA-013 — Research state/public state divergence | High | Rejected/stale information can remain public |
| QA-015 — Patient/account/role administration absent | High | Required operations and role lifecycle unavailable |
| QA-016 — Native app read-mostly | High | Android/iOS scope not fulfilled |
| QA-018 — Mobile navigation dead | High | Public navigation inaccessible below 992 px |
| QA-022 — Production malware scanner absent | High | Sensitive upload pipeline is not release-ready |

## What passed

- Backend: 58 tests / 302 assertions passed in 123.04 s.
- Web: 3 files / 7 tests passed; strict typecheck and production build passed.
- Native: strict typecheck and 4 suites / 6 tests passed.
- Web build output: JS 405.45 KB (124.93 KB gzip), CSS 268.23 KB (38.27 KB gzip).
- Public homepage had no horizontal overflow at 320, 375, 390, 414, 768, 1024, 1280, 1440 and 1920 px.
- Patient, Moderator, Admin and Power Admin runtime API boundaries matched intended role access in the sampled matrix.
- Patient A could not retrieve another patient's appointment; 403 was returned.
- Sequential overlapping booking was rejected with 422.
- Security headers, CSRF boundary, rate limits, private document authorization, strong password rules and mobile token scoping are present.
- Public visual language is coherent, sophisticated, medically appropriate and patient-centered.

## Requirements Missing Entirely

The complete matrix contains the canonical list. The most consequential omissions are:

- Career timeline, achievements and Contact page.
- Booking-time document attachment and patient reschedule request.
- Admin patient management and Power Admin account/role management.
- Visual element selection, double-click/inline rich text and typography controls.
- Managed image, button and menu editing.
- Full page lifecycle and section presentation management.
- Agenda view and calendar create/edit/drag/reschedule operations.
- Native profile/document/message/notification/consultation mutations.
- Native reminder/device management and push notifications.

## Prioritized remediation backlog

### P0 — Fix immediately

| Finding | Title | Severity | Requirement | Complexity | Suggested affected area |
| --- | --- | --- | --- | --- | --- |
| QA-001 | CMS route shadowing blocks page load | High | REQ-074–076, 080, 082, 138 | Small | API routing, CMS editor E2E |
| QA-004 | Booking accepts unavailable/method-invalid requests | High | REQ-036, 041, 094 | Medium | Booking validation, availability service |
| QA-005 | Overlap prevention is concurrency-unsafe | High | REQ-044 | Large | PostgreSQL scheduling transaction/constraint |
| QA-013 | Research/publication state divergence | High | REQ-009, 011, 105, 110 | Large | Verification state machine, publication schema |
| QA-018 | Mobile navigation toggle is dead | High | REQ-002, 134, 135, 139 | Small | Public header React disclosure |
| QA-022 | Production malware scanner absent | High | REQ-121, 122 | Large | Upload quarantine/scanner/storage pipeline |

### P1 — Fix before any release candidate

| Finding | Title | Severity | Requirement | Complexity | Suggested affected area |
| --- | --- | --- | --- | --- | --- |
| QA-002 | Visual edit mode substantially incomplete | High | REQ-065–084 | Large | CMS schema/editor/media/version UI |
| QA-009 | Full operational calendar absent | High | REQ-053, 055, 059, 095–099, 137 | Large | Staff calendar and scheduling APIs |
| QA-010 | Live consultation unconfigured | High | REQ-013, 057, 102, 103, 143 | Large | Provider adapter, privacy, web/native flows |
| QA-015 | Patient/account/role administration absent | High | REQ-059–064, 124 | Large | Admin APIs/UI/policies/audit |
| QA-016 | Native patient app read-mostly | High | REQ-142–145 | Large | v1 API and Expo screens |
| QA-003 | Published nav/theme settings unused | Medium | REQ-079, 085 | Medium | CMS settings and public renderer |
| QA-007 | Declined cancellation cannot be retried | Medium | REQ-047, 050 | Medium | Cancellation data model/API/UI |
| QA-008 | Reschedule status semantics incorrect | Medium | REQ-050, 089 | Medium | Appointment lifecycle/API/UI |
| QA-021 | Password reset leaves browser sessions active | Medium | REQ-028, 123 | Medium | Session revocation and audit |
| QA-023 | Audit logging incomplete | Medium | REQ-124, 125 | Medium | Cross-domain audit catalogue |

### P2 — Fix in the next iteration before production approval

| Finding | Title | Severity | Requirement | Complexity | Suggested affected area |
| --- | --- | --- | --- | --- | --- |
| QA-006 | slot_minutes ignored | Medium | REQ-055, 092 | Small | Availability domain model |
| QA-011 | Contact/unknown routes render Home | Medium | REQ-008, 015, 017 | Small | Public routing/content |
| QA-012 | Public content journeys incomplete | Medium | REQ-003–011, 107, 108 | Large | Content models/routes/UI |
| QA-014 | Seed publications bypass queue | Medium | REQ-011, 104, 111 | Medium | Seeder/data migration |
| QA-017 | Native cancellation shown in invalid states | Medium | REQ-141 | Small | Mobile allowed-action contract |
| QA-019 | Labels/skip/focus incomplete | Medium | REQ-128–131, 133 | Medium | Shared forms/focus/status components |
| QA-020 | Small-text contrast fails AA | Medium | REQ-132 | Small | Design tokens |
| QA-024 | Async failures lack recovery | Medium | REQ-133, 150 | Medium | Shared mutation/error UX |
| QA-025 | Static/incomplete SEO metadata | Medium | REQ-016 | Medium | Metadata, CMS, sitemap |
| QA-028 | Booking attachment missing | Medium | REQ-039 | Large | Booking/upload/scanning/ownership |
| QA-029 | Patient reschedule request missing | Medium | REQ-048, 141 | Medium | Patient/staff workflow and notifications |

### P3 — Quality improvements

| Finding | Title | Severity | Requirement | Complexity | Suggested affected area |
| --- | --- | --- | --- | --- | --- |
| QA-026 | Eager route/data loading | Low | REQ-148, 149 | Medium | Route splitting/query scope/performance budget |
| QA-027 | Footer policy labels are inert | Low | REQ-014, 015 | Small | Approved policy pages and footer links |

## Audit scope and limitations

- Tests used a clearly marked, isolated temporary SQLite database and local server with debug disabled. The application database and production settings were not modified.
- Browser coverage used the Codex in-app Chromium runtime. Separate Chrome, Edge, Firefox and Safari/WebKit were not available.
- No physical Android/iOS devices, signed builds, live video, push/SMS/mail delivery, production scanner, PostgreSQL/Redis deployment, monitoring or backup target were available.
- No malicious, destructive, denial-of-service or real-patient test was performed.
- Content verification used authoritative sources where found; current employment/credential claims still require owner/institutional approval.
- This audit reduces known risk; it does not prove the absence of all defects or establish legal/security/accessibility compliance.

## Deliverable index

- REQUIREMENTS_TRACEABILITY_MATRIX.md — 150 requirements and evidence/status.
- QA_FINDINGS.md / QA_FINDINGS.csv — 29 consolidated root-cause findings.
- QA_DISCOVERY_REPORT.md — architecture, routes, environment, modules and placeholders.
- SECURITY_FINDINGS.md — security/privacy/authorization and upload review.
- ACCESSIBILITY_FINDINGS.md — semantics, forms, focus, contrast and limitations.
- MOBILE_RESPONSIVE_FINDINGS.md — nine-width web sweep and native scope review.
- UI_UX_REVIEW.md — visual identity and workflow quality.
- TEST_COVERAGE_GAPS.md — current coverage and prioritized missing tests.
- CONTENT_VERIFICATION_FINDINGS.md — claim/source and publication-governance review.
- RETEST_PLAN.md — phased verification and production exit criteria.

No fixes were implemented. The audit stops with these reports as requested.
