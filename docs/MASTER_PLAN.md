# Product Delivery Plan — Source of Truth

Last updated: 26 August 2026

This document is the authoritative delivery plan for the Dr. Funmilola Wuraola breast oncology practice platform. Update it whenever a major task changes scope, status, architecture, risk, or acceptance criteria.

## Product surfaces

1. Responsive public specialist-practice website.
2. Patient web portal and staff administration application.
3. Android application.
4. iOS application.
5. Shared Laravel REST API, background jobs, notifications, storage and audit layer.

Mobile applications will be first-class clients of the same versioned API. Business rules must live on the server, never only in the web interface. A dedicated mobile implementation framework will be chosen before mobile Phase 1 using a documented architecture decision record; React Native is the current default candidate because the team can share TypeScript domain types and validation while retaining native platform capability.

## Definition of done for every major task

- Scope and acceptance criteria are documented here before implementation.
- Security, privacy, accessibility and mobile-client impact are reviewed.
- Automated tests and the production frontend build pass.
- Relevant documentation is updated.
- Changes are committed as one stable, descriptive commit.
- The commit is pushed to `https://github.com/segun-ajayi/Dr_Funmilola.git`.
- Known limitations are recorded; critical errors are never carried forward.

## Delivery roadmap

| Phase | Scope | Status |
|---|---|---|
| 1 | Laravel 13 foundation, React/TS shell, design system, roles, core schema | Complete |
| 2 | Verified public content, services, about, career, achievements, publications, education, contact | Started |
| 3 | Availability, booking, conflict protection, operational calendar, notifications | In progress |
| 4 | Secure patient portal, profile, documents, appointments and messages | Complete |
| 5 | Provider-neutral online consultation and waiting room | Planned |
| 6 | Safe component-based visual CMS, drafts, preview, approval and publishing | Planned |
| 7 | Academic portfolio and Research & Verification Queue | Started |
| 8 | Security hardening, audit coverage, rate limits, private storage and backup runbooks | Planned |
| 9 | Full unit, feature, frontend, end-to-end and authorization testing | Planned |
| 10 | PostgreSQL/Redis production deployment, queues, scheduler, monitoring and backups | Planned |
| 11 | Shared mobile API contract and mobile authentication hardening | Planned |
| 12 | Android and iOS patient apps, accessibility QA and store delivery | Planned |

## Current task: Foundation vertical slice

Status: Complete — automated tests and production build passed; desktop and 390×844 mobile browser checks completed on 26 August 2026.

Acceptance criteria:

- Laravel and React run as one same-origin application.
- Central design tokens create a premium breast-health identity without unsupported clinical imagery.
- Public service data and approved publications come from the database.
- Appointment requests persist with a private reference and server-side overlap protection.
- Seed research stays unpublished in a verification queue.
- Exactly four primary roles exist: patient, admin, moderator and power admin.
- Tests cover successful booking and double-booking rejection.

## Architectural decisions

- Modular monolith; no microservices.
- PostgreSQL 17+ and Redis in production; SQLite is permitted for isolated local tests.
- All clinical and administrative authorization is enforced in Laravel policies and API endpoints.
- Appointment timestamps are stored timezone-aware; the practice timezone is `Africa/Lagos`.
- Patient uploads will use private storage and authorized download routes.
- Public content follows draft → preview → approval → publish.
- Research records are seed material and cannot become public without explicit verification.

## Previous task handoff

Complete authenticated patient and staff access: secure login/registration, email verification, password reset, session revocation, policy coverage, patient isolation tests and mobile-ready token strategy.

## Current task: Identity and access vertical slice

Status: Complete — automated identity and isolation tests plus a production frontend build passed on 26 August 2026.

Acceptance criteria:

- Patients can register, sign in, sign out, request a password-reset link and set a new password.
- Authenticated users can fetch their own profile and appointments only.
- Disabled accounts cannot sign in or use protected APIs.
- Exactly the four approved roles are enforced, and public registration can create only patients.
- Staff-only routes reject patients; patient records are isolated from other patients.
- Browser clients use secure same-origin sessions with CSRF protection.
- Android and iOS clients can later use scoped, revocable Sanctum tokens without duplicating identity logic.
- Sign-in, sign-out and password-reset events create privacy-conscious audit records.
- Authentication endpoints are rate-limited and do not disclose whether an email address is registered.
- Automated identity, authorization and patient-isolation tests pass.

Implementation note: this task establishes API and policy foundations. Email delivery remains configured through Laravel's mail abstraction; production SMTP/transactional-provider credentials are a deployment concern.

## Previous task handoff: staff operations

Build the staff operations foundation: role-aware Admin and Moderator dashboards, patient lookup with minimum-necessary data, appointment request review, status transitions, rescheduling/cancellation rules, availability management and an operational calendar API.

## Current task: Staff operations foundation

Status: Complete — 17 tests with 77 assertions and the production frontend build passed on 26 August 2026.

Acceptance criteria:

- Admin, Moderator and Power Admin receive a role-aware operational dashboard; Patient accounts are denied.
- Dashboard metrics include today's appointments, pending requests, upcoming online consultations and recent patients.
- Staff can search patients by name, email or phone and receive only minimum operational fields.
- Appointment lists support date, status, consultation method and patient filters.
- Valid status transitions are enforced on the server and invalid transitions are rejected.
- Confirm, cancel and reschedule actions are audited; rescheduling rechecks availability and conflicts.
- Availability rules can be listed and managed by staff with validated times and no arbitrary schedule data.
- A calendar API returns bounded date ranges suitable for day, week, month and agenda clients.
- Moderator UI remains operationally simple and responsive; Admin/Power Admin receive the appropriate expanded context.
- Automated tests cover role access, transition rules, conflict-safe rescheduling, patient-search minimization and audit creation.

Implementation note: the current calendar API is complete for bounded event feeds; interactive day/week/month presentation, drag-and-drop and leave/holiday exceptions remain in the next calendar enhancement task.

## Next major task

Complete the patient-portal core: editable minimum-necessary profile, appointment detail and patient-safe cancellation requests, secure document metadata/private storage, practice messaging threads, in-app notifications and reminder-ready events. Add the corresponding staff inbox and patient-context views without expanding into a hospital EMR.

## Current task: Patient portal core

Status: Complete — 22 automated tests with 98 assertions, production frontend build and 390×844 responsive browser check passed on 26 August 2026.

Acceptance criteria:

- Verified patients can view and update only their minimum-necessary contact and preference profile; sensitive changes are audited.
- Patients can open appointment details and request cancellation without directly forcing a clinical schedule status change.
- Patient documents are stored on a private disk, expose safe metadata, validate type/size, and download only through authorized routes.
- Patients can create practice message threads and reply within their own threads; staff can triage and reply through a bounded inbox.
- Patient-context staff views combine minimum operational profile, appointments, documents and message summaries without creating hospital-EMR features.
- In-app notifications cover cancellation requests and new messages, support read state, and use Laravel events suitable for later queued email/push reminders.
- All new endpoints enforce patient isolation, staff role rules, verified email and active-account checks.
- API responses remain suitable for the future Android and iOS applications; business rules remain server-side.
- Automated tests, the production frontend build and responsive browser checks pass before commit and push.

Privacy boundary: this milestone does not add diagnoses, clinical notes, prescriptions, laboratory results or a longitudinal medical record. Uploaded documents are patient-supplied administrative/care-coordination files only.

## Next major task

Complete the advanced scheduling and notification layer: availability exceptions and leave, operational day/week/month calendar views, reminder scheduling with queued delivery channels, notification preferences, appointment change notices and delivery auditability. Preserve provider-neutral consultation links and the shared mobile API boundary.

## Supporting task: Verified demonstration accounts

Status: Complete — local database seeded; 23 automated tests with 110 assertions and the production frontend build passed on 26 August 2026.

Acceptance criteria:

- Local seeding creates exactly one documented demonstration account for each primary role.
- All four demonstration accounts are active, claimed and email-verified so role-specific screens can be reviewed immediately.
- Seeding is repeatable and does not create duplicate accounts.
- Addresses use the reserved `.test` domain and documentation prohibits production use of the shared demonstration password.
- An automated test verifies role, active state and verification state for every account.
