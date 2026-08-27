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
| 2 | Verified public content, services, about, career, achievements, publications, education, contact | Complete — approval queue remains operational |
| 3 | Availability, booking, conflict protection, operational calendar, notifications | Complete |
| 4 | Secure patient portal, profile, documents, appointments and messages | Complete |
| 5 | Provider-neutral online consultation and waiting room | Complete |
| 6 | Safe component-based visual CMS, drafts, preview, approval and publishing | Complete |
| 7 | Academic portfolio and Research & Verification Queue | Complete |
| 8 | Security hardening, audit coverage, rate limits, private storage and backup runbooks | Complete |
| 9 | Full unit, feature, frontend, end-to-end and authorization testing | Complete |
| 10 | PostgreSQL/Redis production deployment, queues, scheduler, monitoring and backups | Complete — deployment artifacts ready |
| 11 | Shared mobile API contract and mobile authentication hardening | Complete |
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

## Current task: Advanced scheduling and notifications

Status: Complete — 28 automated tests with 126 assertions, production frontend build, live staff calendar review and 390×844 responsive browser check passed on 26 August 2026.

Acceptance criteria:

- Staff can create and remove bounded full-day or timed availability exceptions for leave, closures and additional clinics.
- Public slot generation excludes closures and leave, and can include approved additional-clinic windows without bypassing appointment conflict checks.
- Staff receive accessible day, week and month calendar views from the same bounded calendar API.
- Patients can manage in-app and email reminder preferences; future mobile push preference is represented without enabling an unavailable delivery channel.
- Confirmed appointments generate idempotent 24-hour and 2-hour reminder records through a scheduled, queue-ready command.
- Appointment confirmation, rescheduling, cancellation and reminder activity creates patient notifications and privacy-conscious delivery audit records.
- Online appointments retain provider-neutral meeting metadata; no vendor is hard-coded before the consultation integration phase.
- New scheduling and notification endpoints preserve staff roles, patient isolation, Lagos display time and UTC storage.
- Automated tests, production build and responsive browser checks pass before commit and push.

Implementation note: email reminders are queued through Laravel's mail channel and require the documented production scheduler and queue workers. Mobile push remains intentionally disabled until device-token lifecycle and the native applications exist.

## Next major task

Build the provider-neutral online consultation foundation: secure staff-managed meeting configuration, patient waiting room, time-bounded join authorization, consultation consent and attendance states, without embedding clinical notes or committing the product to a single video vendor.

## Current task: Online consultation foundation

Status: Complete — 33 automated tests with 151 assertions, production frontend build and guarded-route browser review passed on 27 August 2026.

Acceptance criteria:

- Only confirmed online appointments can receive one consultation session, created or managed by authorized staff.
- Consultation provider behavior is defined behind a server-side interface; stored session data does not hard-code a commercial vendor.
- Patients can view only their own consultation and enter a waiting room during a bounded time window around the appointment.
- Staff can admit a waiting patient, start and end the consultation through validated server-side state transitions.
- Patient consent is explicit, timestamped, versioned and required before join authorization is issued.
- Join authorization is short-lived, signed and rechecks active account, appointment ownership/staff role, appointment method and consultation state.
- Attendance events record joined/left timestamps and participant role without storing audio, video or clinical notes.
- The room UI provides accessible waiting, device-readiness and leave controls while clearly identifying that live media depends on the configured provider.
- Automated authorization, isolation, consent, expiry and transition tests plus production build and responsive browser checks pass.

Implementation note: the default provider intentionally supplies no live media. Selecting and configuring a real video provider requires a separate privacy, hosting-region and data-processing review; all authorization and workflow code is ready behind `VideoProviderInterface`.

## Next major task

Build the safe component-based visual CMS: structured pages and sections, drafts, preview tokens, approval/publishing, version history and rollback, sanitized rich text, media references, navigation and theme tokens. Restrict structural and publishing controls to Power Admin and never permit executable code, unrestricted HTML or SQL.

## Current task: Safe visual CMS

Status: Complete — 38 automated tests with 177 assertions, production frontend build and guarded editor-route browser review passed on 27 August 2026.

Acceptance criteria:

- Power Admin can create pages from an allowlisted template and add, edit, reorder, hide or remove allowlisted section types.
- Section content is validated structured JSON with plain text, safe URLs and bounded presentation tokens; arbitrary HTML, JavaScript, CSS, PHP and SQL are never accepted.
- Draft changes never alter the public page until an explicit Power Admin publish action.
- Time-bounded preview tokens expose the draft only through a dedicated preview endpoint and do not create a public record.
- Every meaningful draft/publish/rollback action creates an immutable version snapshot and audit record.
- Power Admin can inspect version history and restore an earlier snapshot into a new draft without erasing history.
- Navigation entries and theme tokens use strict allowlists and safe bounds; protected application routes cannot be overridden.
- Public CMS APIs return published data only, and non-Power-Admin roles cannot access editor endpoints.
- The editor remains usable on mobile and labels all controls in non-technical language.
- Automated validation, authorization, preview-expiry, publish and rollback tests plus production build and browser checks pass.

Implementation note: CMS media currently uses validated safe URL references. Managed image upload, focal-point editing and responsive renditions will be added during the media/deployment hardening work; executable content remains prohibited.

## Next major task

Complete the verified public content and academic portfolio: structured biography/career/achievements/education/contact resources, searchable publication details, public education articles with medical review metadata, and a Power Admin Research & Verification Queue that alone can promote sourced claims into approved public records.

## Current task: Verified content and academic portfolio

Status: Complete — authoritative source review documented; 43 automated tests with 195 assertions, production frontend build and 390×844 public academic browser check passed on 27 August 2026.

Acceptance criteria:

- Career, achievement, profile and academic records carry source, verification and publication states; unapproved records never appear publicly.
- Publications support verified DOI/PMID metadata, search, category filters, sorting, pagination and public detail responses.
- The Research & Verification Queue supports Pending Review, Verified, Rejected and Published states with Power Admin-only decisions and reviewer audit data.
- Promoting a claim creates or updates the correct structured public record through explicit, validated mapping rather than copying arbitrary text into the site.
- Public education articles require author, medical reviewer, review date, updated date, category, tags and medical disclaimer before publication.
- Contact and practice-location records expose only approved professional details; exact private clinic details remain restricted to confirmed patients.
- Source seeding uses authoritative ORCID, PubMed, Crossref, institutional or journal records and remains pending until Power Admin approval.
- Public interfaces provide clear pending/empty states and never imply that unverified qualifications or achievements are established facts.
- Automated authorization, workflow, public-visibility, search and validation tests plus production build and responsive browser checks pass.

Implementation note: authoritative facts are intentionally seeded as Pending Review. “Complete” means the verification, publishing, public portfolio and education systems are operational; it does not misrepresent unapproved biographical claims as public facts.

## Next major task

Complete security and operational hardening: audit-query coverage, security headers, stricter rate limits, upload malware-scanning hooks, session/device management, private-data retention controls, health/readiness checks, backup and incident-response runbooks, and automated security regression tests.

## Current task: Security and operational hardening

Status: Complete — 48 automated tests with 224 assertions, production frontend build, dry-run retention check and browser security-route guard review passed on 27 August 2026.

Acceptance criteria:

- All web/API responses receive restrictive security headers appropriate to the same-origin React/Laravel architecture.
- Patient uploads pass through a replaceable file-scanning interface before private storage; suspicious executable content is rejected and audited.
- Mobile access tokens are named, scoped, expiring, listable and individually revocable by their owner; staff can revoke compromised tokens under audited authority.
- Power Admin receives bounded, filterable audit-log access with no password, token, document body or message body exposure.
- Readiness checks validate database, cache and private-storage dependencies without exposing configuration or secrets.
- Retention commands default to dry-run, identify only explicitly supported expired operational records and require an explicit execute flag.
- Authentication, messaging, upload, preview, join and staff mutation endpoints have proportionate rate limits.
- Backup, restore verification, queue/scheduler supervision, security incident response and breach escalation are documented.
- Automated tests cover headers, scanner rejection, token isolation/revocation, audit authorization, readiness and retention safety.

Implementation note: the default upload scanner is an in-process signature boundary, not a replacement for enterprise malware analysis. Production should bind `FileScannerInterface` to ClamAV or an approved managed scanner and monitor scan failures.

## Next major task

Complete the comprehensive quality phase: frontend component tests, expanded API authorization matrices, end-to-end browser journeys for public booking and authenticated role workspaces, accessibility checks, mobile viewport coverage, failure-state tests and a traceable release-quality checklist.

## Current task: Comprehensive release quality

Status: Complete — 51 backend tests with 255 assertions, 3 frontend component tests, production build and a 390×844 public booking browser journey passed on 27 August 2026.

Acceptance criteria:

- Vitest and Testing Library execute deterministically with DOM matchers and no live network dependency.
- Component tests cover published CMS rendering, protected/empty states and key accessible names/landmarks.
- A parameterized backend authorization matrix proves Patient, Moderator, Admin and Power Admin boundaries across patient, staff and CMS APIs.
- Failure tests cover invalid transitions, expired signatures/tokens, conflicts, unsafe uploads, unpublished content and unavailable dependencies.
- Browser journeys cover public navigation and booking selection plus protected-role route guards at desktop and 390×844 mobile widths without submitting medical actions.
- Production build, PHP suite, frontend suite and source/diff checks form one documented release gate.
- Known provider/deployment limitations remain explicit and are not hidden by test success.

Implementation note: authenticated visual journeys remain a controlled staging gate because browser credential entry and medical-action submission require human confirmation. Equivalent role boundaries and workflows are covered by the automated authorization/feature suite.

## Next major task

Complete production delivery foundations for PostgreSQL 17+, Redis, queue/scheduler workers, hardened environment configuration, container or server deployment, migrations, TLS/readiness, monitoring, log rotation, encrypted backups and rollback. Produce reproducible deployment artifacts without embedding secrets or claiming a live production launch without infrastructure credentials.

## Current task: Production delivery foundations

Status: Complete — Docker Compose configuration validation, 51 backend tests with 255 assertions, 3 frontend tests and production asset build passed on 27 August 2026.

Acceptance criteria:

- Reproducible production images build PHP-FPM application assets with required PostgreSQL, Redis, intl, zip and OPcache support.
- Deployment manifest separates Nginx, PHP application, queue worker, scheduler, PostgreSQL 17 and Redis responsibilities with health checks and persistent volumes.
- Production environment template uses placeholders only, disables debug, requires secure cookies/TLS-aware configuration and selects PostgreSQL/Redis/database-independent drivers correctly.
- Deploy procedure performs backup, maintenance mode, migrations, cache warmup, worker restart, readiness validation and safe return to traffic in an explicit order.
- Rollback distinguishes application rollback from schema/data restoration and never recommends destructive migration reversal without backup review.
- Monitoring covers readiness, HTTP errors, queue failures/age, scheduler heartbeat, database/storage capacity, authentication anomalies and backup freshness.
- Container and configuration syntax are validated where local tooling exists; lack of infrastructure credentials is recorded as a deployment boundary, not misreported as a live launch.

Deployment boundary: the repository now contains validated production artifacts and runbooks, but no live infrastructure, domain, TLS certificate, secret manager, mail provider or approved video provider was supplied. Consequently no production environment has been launched or claimed.

## Next major task

Freeze and harden the versioned mobile API contract: mobile-specific authentication/device lifecycle, stable JSON envelopes/resources, capability discovery, pagination/error conventions, offline-safe mutation identifiers, device notification registration boundaries and generated/shared TypeScript contract types. Document the native architecture decision before scaffolding Android and iOS.

## Current task: Versioned mobile API contract

Status: Complete — 56 backend tests with 294 assertions, 3 frontend tests, strict TypeScript contract check and production build passed on 27 August 2026.

Acceptance criteria:

- Native clients use `/api/v1` while existing web endpoints remain backward compatible.
- Mobile sign-in returns a named, scoped, expiring Sanctum bearer token only for active, claimed, verified Patient accounts.
- Capability discovery identifies API version, practice timezone, enabled features, upload limits and unavailable integrations without exposing secrets.
- Patient profile, appointments, documents, messages, notifications and consultations use stable JSON envelopes and bounded pagination.
- Mobile mutations accept a UUID client request identifier and replay the original response without duplicating side effects.
- Device push-token registration has an explicit authenticated contract but refuses activation while push is unavailable.
- Errors follow a stable code/message/field-errors shape for authentication, validation, authorization and conflicts.
- Shared TypeScript types and an architecture decision record are source-controlled and consumed by the native app scaffold.
- Automated tests prove token scopes/expiry, patient isolation, envelope shape and idempotent replay.

Architecture decision: one React Native/Expo TypeScript application will produce independently signed Android and iOS binaries. Tokens use native secure storage, sensitive server data remains memory-only initially, and the shared `@dr-funmilola/mobile-contract` package defines v1 client types.

## Next major task

Build the Android and iOS patient application from the accepted React Native architecture: secure sign-in/token storage, accessible native navigation, dashboard, appointments and cancellation requests, profile, documents, messages, notifications, reminder preferences and consultation waiting room. Add platform configuration, tests, privacy/store-readiness documentation and reproducible Android/iOS build profiles.

## Current task: Android and iOS patient application

Status: Complete — 56 backend tests with 294 assertions, 3 web frontend tests, 6 native-app tests, strict TypeScript checks, 21/21 Expo Doctor checks, production web build, Android/iOS Expo exports and an accessible browser smoke test passed on 27 August 2026.

Acceptance criteria:

- One Expo/React Native TypeScript codebase produces independently configured Android and iOS applications using the identifiers and deep-link scheme recorded in source control.
- Patient authentication uses the versioned mobile API, stores only the bearer token in native secure storage and clears all sensitive in-memory state on sign-out or authorization failure.
- Accessible native navigation provides dashboard, appointments, profile, documents, messages, notifications, consultation waiting room and settings journeys with useful loading, empty, offline and failure states.
- Appointment cancellation uses a UUID mutation identifier so safe retries cannot duplicate side effects; unsupported video and push features remain honestly disabled through server capabilities.
- Reminder preferences and device/session management use supported server contracts, while unavailable native push registration is represented without pretending it is active.
- Shared API types come from `@dr-funmilola/mobile-contract`; validation and API errors are rendered consistently without logging tokens, document contents or medical message bodies.
- Automated mobile tests cover authentication/session handling, API envelope errors, accessible screens and idempotent cancellation behavior; strict TypeScript checking passes.
- EAS development, preview and production profiles, platform permissions, icons/splash assets, privacy notes, store metadata checklist and build/release instructions are documented without embedded secrets.
- Android and iOS configuration/export validation passes where local tooling permits; signing, store submission and live-provider activation remain explicit external release gates.

Implementation note: the shared Expo SDK 57 application now provides secure Patient sign-in, protected native navigation, dashboard, appointments and idempotent cancellation requests, profile/settings, documents, messages, notifications and consultation status. Native bearer tokens use device-only SecureStore protection; health data remains in memory and is cleared with the session. The browser preview deliberately uses memory-only token storage because native SecureStore is unavailable there.

Release boundary: Android and iOS JavaScript/native configuration exports are validated. Store-signed binaries and submission require externally controlled Expo, Apple Developer and Google Play accounts, signing credentials, approved privacy/support URLs, production TLS configuration and store-review approval; none are embedded or falsely claimed as completed.

## Next major task

Run the controlled external release: provision production infrastructure and protected EAS environments, complete legal/privacy and clinical-copy approval, perform physical-device accessibility and security acceptance, create store records/signing credentials, produce signed internal builds, resolve review feedback and submit approved Android and iOS releases.

## Current task: Admin navigation and Power Admin editing repair

Status: Complete — strict TypeScript checking, 6 frontend tests and production asset build passed on 27 August 2026.

Acceptance criteria:

- Every staff sidebar item is a real route link with a clear active state; no placeholder anchors remain.
- Shared staff navigation exposes dashboard, calendar, inbox and consultations to authorised staff.
- Power Admin additionally receives website pages, research/publication review, education management and audit-log navigation.
- The public website shows an authenticated Power Admin edit-mode control that opens contextual editing and publishing tools without exposing them to other roles.
- Existing staff/CMS pages use consistent navigation and provide a reliable route back to the dashboard.
- Missing management screens required by the navigation use the existing protected APIs and show loading, empty, success and failure states.
- Role visibility and route behavior are covered by automated frontend/backend checks plus authenticated browser verification.

Implementation note: the original Today, Appointments and Patients entries were inert placeholder anchors. They are now working destinations, and Power Admin receives direct navigation to website pages, publication review, education management and the sanitised audit log. A role-aware Edit site control appears on public pages only for an authenticated Power Admin and opens contextual publishing shortcuts.

Verification boundary: role-aware rendering and destinations are covered by deterministic component tests. The refreshed authenticated browser session remains the final human visual check because entering privileged credentials through browser automation requires explicit action-time confirmation.
