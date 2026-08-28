# Remediation Progress

Started: 28 August 2026  
Audit baseline revision: `2424cb1f68232f3b10d2e6f43be4536e7091d5df`  
Implementation owner: Codex implementation agent  
Authoritative inputs: the original 13 audit reports in this directory. Those reports are preserved unchanged.

## Operating rules

- This file is the source of truth for remediation status.
- Status values are `NOT STARTED`, `IN PROGRESS`, `BLOCKED`, `READY FOR RETEST`, `PASS`, and `REOPENED`.
- `PASS` requires the original reproduction case, automated regression coverage, runtime evidence where applicable, and a linked revision.
- External-provider, physical-device, browser-matrix, PostgreSQL, Redis, and independent-QA evidence is never inferred from local mocks or SQLite tests.
- Every remediation step is committed and pushed independently.

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
| QA-002 | 6 | NOT STARTED | Every visual CMS control in REQ-065–084 passes E2E/accessibility | Queued; evidence folder `evidence/QA-002` |
| QA-003 | 6 | NOT STARTED | Published nav/theme visibly changes public renderer; draft does not | Queued; evidence folder `evidence/QA-003` |
| QA-004 | 2 | NOT STARTED | Off-rule/closed/method-invalid POSTs fail; generated slot succeeds | Queued; evidence folder `evidence/QA-004` |
| QA-005 | 2 | NOT STARTED | Repeated PostgreSQL concurrent test commits exactly one overlap | Queued; PostgreSQL runtime currently blocked; evidence folder `evidence/QA-005` |
| QA-006 | 2 | NOT STARTED | slot_minutes/buffer semantics documented and boundary-tested | Queued; evidence folder `evidence/QA-006` |
| QA-007 | 5 | NOT STARTED | Declined cancellation can be resubmitted and displays accurately | Queued; evidence folder `evidence/QA-007` |
| QA-008 | 5 | NOT STARTED | Reschedule lifecycle consistent in DB/API/UI/audit/notifications | Queued; evidence folder `evidence/QA-008` |
| QA-009 | 10 | NOT STARTED | Day/week/month/agenda plus operations/filters/responsive/a11y pass | Queued; evidence folder `evidence/QA-009` |
| QA-010 | 11 | BLOCKED | Approved live provider passes ownership/expiry/media/failure/device tests | Approved provider/privacy decision absent; independent shell work remains queued; evidence folder `evidence/QA-010` |
| QA-011 | 8 | NOT STARTED | Contact route and true 404 pass direct/link/status tests | Queued; evidence folder `evidence/QA-011` |
| QA-012 | 8 | NOT STARTED | Complete verified public profile/service/education/publication journeys | Queued; owner content approval remains required; evidence folder `evidence/QA-012` |
| QA-013 | 7 | NOT STARTED | Transactional state machine and retract/public-feed consistency tests | Queued; evidence folder `evidence/QA-013` |
| QA-014 | 7 | NOT STARTED | Fresh seed uses sourced queue only; duplicate migration verified | Queued; evidence folder `evidence/QA-014` |
| QA-015 | 12 | NOT STARTED | Patient/account/role admin operations pass role/audit edge cases | Queued; evidence folder `evidence/QA-015` |
| QA-016 | 13 | NOT STARTED | All required native mutations pass API, E2E, device and a11y tests | Queued; physical-device/provider portions remain externally blocked; evidence folder `evidence/QA-016` |
| QA-017 | 5 | NOT STARTED | Web/native actions exactly match server allowed_actions for all states | Queued; evidence folder `evidence/QA-017` |
| QA-018 | 9 | NOT STARTED | Mobile menu passes touch/keyboard/ARIA at all collapsed widths | Queued; evidence folder `evidence/QA-018` |
| QA-019 | 9 | NOT STARTED | Labels, skip link, focus, names and announcements pass manual/axe checks | Queued; physical screen-reader acceptance remains external; evidence folder `evidence/QA-019` |
| QA-020 | 9 | NOT STARTED | Semantic text/control/focus tokens meet WCAG contrast thresholds | Queued; evidence folder `evidence/QA-020` |
| QA-021 | 12 | NOT STARTED | Password reset revokes other web sessions and mobile/API tokens | Queued; Redis session retest remains blocked; evidence folder `evidence/QA-021` |
| QA-022 | 3 | BLOCKED | Approved scanner/quarantine passes clean/reject/timeout/privacy tests | Approved scanner unavailable; independent quarantine implementation remains queued; evidence folder `evidence/QA-022` |
| QA-023 | 12 | NOT STARTED | Audit catalogue actions produce safe actor/resource/before-after records | Queued; evidence folder `evidence/QA-023` |
| QA-024 | 9 | NOT STARTED | Every major mutation shows preserved, announced, recoverable failure | Queued; evidence folder `evidence/QA-024` |
| QA-025 | 8 | NOT STARTED | Per-route title/description/canonical/OG plus sitemap/404 checks | Queued; evidence folder `evidence/QA-025` |
| QA-026 | 14 | NOT STARTED | Route chunks/requests meet recorded performance budget | Queued; evidence folder `evidence/QA-026` |
| QA-027 | 8 | NOT STARTED | Approved Privacy/Terms/Accessibility pages linked and crawl-tested | Queued; owner/legal approval remains required; evidence folder `evidence/QA-027` |
| QA-028 | 4 | NOT STARTED | Booking attachment is scanned, private, owned, audited and accessible | Queued; scanner closure dependency blocked; evidence folder `evidence/QA-028` |
| QA-029 | 5 | NOT STARTED | Patient reschedule request approve/decline/conflict/audit/notify passes | Queued; evidence folder `evidence/QA-029` |

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
