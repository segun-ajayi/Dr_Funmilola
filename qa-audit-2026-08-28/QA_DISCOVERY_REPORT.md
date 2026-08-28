# QA Discovery Report

Audit date: 28 August 2026  
Repository revision: 2424cb1f68232f3b10d2e6f43be4536e7091d5df on main  
Declared source of truth: docs/MASTER_PLAN.md  
Audit rule: read, inspect, test and document only; no application fixes.

## Executive discovery

This is a single Laravel application serving a React/TypeScript web client and a versioned JSON API, plus a separate Expo/React Native patient app. The codebase is compact and broadly structured around role-scoped controllers, policies, services and database-backed workflows. The public website and staff/patient workspaces compile successfully, and all current automated suites pass.

The repository plan labels every major phase Complete, but discovery found that several accepted phases are shells or narrower implementations than their own acceptance language: visual CMS, full calendar, live online consultation, end-to-end browser testing, complete public content and first-class native patient workflows.

## Technology stack

| Layer | Discovered implementation |
| --- | --- |
| Backend | PHP 8.5.5, Laravel 13, Eloquent, validation, policies, notifications, queues and scheduler |
| Authentication | Laravel Sanctum cookie sessions for web; expiring scoped personal access tokens for mobile |
| Authorization | active middleware, verified middleware, role middleware and model policies |
| Web frontend | React 19.2.8, TypeScript 7, Vite 7, React Router, TanStack Query, Bootstrap CSS and Lucide icons |
| Native frontend | Expo SDK 57 / React Native, TypeScript, Expo Router, TanStack Query and SecureStore |
| Local/test database | SQLite |
| Intended production data services | PostgreSQL 17+ and Redis, represented by deployment artifacts but not connected during audit |
| File storage | Laravel local private disk; document metadata in database; authorized controller download |
| Messaging/notifications | Database notifications; mail driver configurable; local audit used array/log transport only |
| Tests | PHPUnit/Laravel feature tests; Vitest/Testing Library for web; Jest for native |
| Deployment | Docker/production examples, queue/scheduler/readiness/backup runbooks; no live infrastructure in scope |

## Application modules

- Public practice website: Home, About, Services, Research, Academic, Education and Booking.
- Identity: registration, email verification, login/logout, forgot/reset password, mobile token creation/revocation.
- Patient portal: profile, appointments, cancellation requests, documents, messages, notifications, preferences and consultation shell.
- Staff operations: dashboard, patient search/context, appointment states/reschedule, inbox, cancellation review, calendar feed, availability rules/exceptions and consultation states.
- Power Admin: CMS pages/sections/preview/publish/version APIs, settings, research verification queue, education management and audit-log viewer.
- Native patient app: secure login/session, dashboard, appointment list/cancellation and read-only care data.
- Operations: queued reminders, readiness, pruning, security headers, rate limits and deployment/backup documentation.

## User roles and verified access behavior

| Role | Expected boundary | Runtime result |
| --- | --- | --- |
| Public | Public content, availability and booking only | Public routes/API accessible |
| Patient | Self-owned portal/mobile data | /api/me 200; staff/CMS 403; foreign appointment ID 403 |
| Moderator | Operational staff functions, not structural CMS | staff 200; CMS 403 |
| Admin | Operational staff functions, not structural CMS | staff 200; CMS 403 |
| Power Admin | Staff plus CMS/research/education/audit | staff/CMS list 200; CMS item load hit QA-001 |

No cross-patient appointment access was reproduced. Existing tests also cover profile, documents, messages and consultation ownership.

## Routes

The route inventory contains 104 routes. Major web routes are:

- Public: /, /about, /services, /research, /academic, /education, /book and /p/{slug}.
- Identity: /sign-in, /register, /forgot-password and /reset-password.
- Patient: /portal, /portal/reminders and /portal/consultations.
- Staff: /staff, /staff/inbox, /staff/calendar and /staff/consultations.
- Power Admin: /staff/cms, /staff/research-review, /staff/education and /staff/audit.

Major API groups are:

| API area | Representative routes | Boundary |
| --- | --- | --- |
| Public | /api/public, /api/availability/{service}, /api/appointment-requests | Public, throttled where appropriate |
| Public CMS/content | /api/cms/pages/{slug}, /api/cms/preview/{token}, /api/academic/*, /api/education/* | Public; preview signed/tokenized |
| Identity | /api/auth/register, login, forgot/reset, mobile-token | Public with throttles |
| Patient self-service | /api/me/*, /api/documents/{id}/download, /api/consultations/* | authenticated, active, generally verified; patient sub-group where needed |
| Staff | /api/staff/dashboard, appointments, calendar, availability, inbox, patients, consultations | admin/moderator/power_admin |
| Power Admin | /api/cms/pages, sections, settings, verification-queue, education, audit-logs | verified power_admin |
| Native v1 | /api/v1/capabilities, auth/token, me, appointments, documents, message-threads, notifications, consultations | versioned envelope and scoped patient token |

Discovery defect: the public /api/cms/pages/{slug} route precedes and shadows protected /api/cms/pages/{page}, making the latter unreachable for numeric IDs (QA-001).

## Database modules

- Identity: users, password reset tokens, sessions, personal access tokens.
- Practice: patient profiles, services, appointments, availability rules/exceptions.
- Portal: cancellation requests, documents, message threads/messages, notifications/preferences/deliveries.
- Consultation: consultations, consent/attendance records.
- CMS: pages, sections, versions, preview tokens and settings.
- Academic/content: publications, career entries, achievements, practice contacts, research claims and education articles.
- Governance: audit logs and mobile mutation idempotency records.

Foreign keys were enabled in the isolated database. The sampled appointment, message and CMS-section relationships had zero orphans. Important integrity gaps remain: no database-level appointment overlap invariant; DOI/PMID are nullable and not unique; many role/status fields rely on application validation rather than database checks.

## Environment and external dependencies

The normal local configuration uses SQLite, database sessions/queues, local storage and log mail. The audit server used a separate temporary SQLite file, file sessions, array cache, sync queue and array mail with APP_DEBUG=false. No real patient data, notification recipient, payment, CMS publication or production setting was touched.

External integration boundaries discovered:

- VideoProviderInterface is bound to UnconfiguredVideoProvider; live consultation is not enabled.
- FileScannerInterface is bound to BasicFileScanner; the plan explicitly requires an approved production scanner.
- Push notifications report unavailable and registration returns 409.
- SMTP/SMS, production PostgreSQL/Redis, monitoring, backup target and store-signing accounts were not available.
- Public fonts load from Google Fonts under CSP; no analytics or payment integration was found.

## Queue and scheduler

Laravel queue/scheduler support is present for reminders and operational work. Production artifacts describe workers and scheduling. The local audit forced synchronous/array-safe drivers to avoid external effects. Provider delivery, retry behavior and production worker supervision were not testable without deployment infrastructure.

## Existing automated tests and safe execution results

| Gate | Result | Measurement |
| --- | --- | --- |
| Backend | PASS | 58 tests, 302 assertions, 123.04 s |
| Web frontend | PASS | 3 files, 7 tests, 23.53 s |
| Web typecheck/build | PASS | JS 405.45 KB (124.93 KB gzip); CSS 268.23 KB (38.27 KB gzip) |
| Native typecheck | PASS | Strict TypeScript check completed |
| Native tests | PASS | 4 suites, 6 tests, 42.247 s |

Current automated tests meaningfully cover authentication, role/API authorization, patient isolation, sequential scheduling conflicts, appointment transitions, reminder mechanics, consultation ownership/signed access, CMS backend publishing/version actions, content queue basics, security headers/rate limits and mobile token/idempotency contracts.

They do not constitute the full unit, feature, frontend and end-to-end coverage claimed in the plan. TEST_COVERAGE_GAPS.md contains the prioritized gap analysis.

## Areas not implemented or materially incomplete

- Full visual/inline CMS, managed media, button/menu/theme editor, page lifecycle and version UI.
- Usable CMS page loading due to route collision.
- Full day/week/month/agenda calendar with creation/edit/drag/filter interactions.
- Live video provider and true consultation room.
- Admin patient management and Power Admin user/role management.
- Patient reschedule request and booking-time attachment.
- Complete career, achievements, contact, service, education-detail and publication-detail journeys.
- First-class native mutations for profile, files, messages, notifications, consultations, reminders and devices.
- Native push notifications.
- Complete per-route SEO metadata and real 404/contact routing.

## Placeholder or misleading implementations

| Classification | Area | Observation |
| --- | --- | --- |
| PLACEHOLDER IMPLEMENTATION | Online consultation | State/authorization shell exists; no live media provider |
| PARTIAL IMPLEMENTATION | Edit site | Toggle opens manager links rather than editing the public page |
| PARTIAL IMPLEMENTATION | Calendar | Named views change list ranges, not calendar presentation/interactions |
| PARTIAL IMPLEMENTATION | Native app | Polished screens mostly read data and omit required actions |
| PLACEHOLDER CONTENT | Portrait fallback | Generated initials artwork is labeled pending approved photography; current CMS Home has no real image |
| MISSING DESTINATION | Footer legal labels | Privacy, Terms and Accessibility are text only |

No Lorem Ipsum, links to #, hard-coded appointment/dashboard numbers, fake charts, mock API layer, TODO or FIXME representing active functionality were found in the inspected application source.

## Discovery conclusion

The architecture has good foundations and explicit security boundaries, but implementation completeness is overstated. Passing automated tests demonstrate the behavior they cover, not the wider product requirements. The release decision must therefore be based on the traceability matrix and the 29 consolidated findings rather than the repository's Complete labels.
