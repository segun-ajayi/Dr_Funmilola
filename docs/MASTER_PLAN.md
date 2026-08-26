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
| 3 | Availability, booking, conflict protection, operational calendar, notifications | Started |
| 4 | Secure patient portal, profile, documents, appointments and messages | Planned |
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

## Next major task

Complete authenticated patient and staff access: secure login/registration, email verification, password reset, session revocation, policy coverage, patient isolation tests and mobile-ready token strategy.
