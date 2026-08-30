# QA-009 evidence

Status: READY FOR RETEST (not self-declared PASS).

Implementation revision: `7212b73`

Implemented evidence:

- Distinct Day, Week, Month and Agenda views with date navigation and explicit Africa/Lagos display.
- Combined patient, status, service and consultation-method filters.
- Staff appointment creation from an existing-patient search and server-derived available slots.
- Appointment detail, operational edit, valid status transitions and staff-only reschedule options.
- Pointer drag movement plus labelled non-drag previous/next-day controls.
- Server-side create/move revalidation; conflicts are announced and the invalid move is not retained.
- Recurring availability rule management, active/paused state and schedule exceptions on the staff page.
- Recoverable loading, success and error announcements.

Automated verification:

- Backend: 97 tests, 712 assertions passed; the six staff-operations tests cover authorization, creation, detail/edit, range metadata, reschedule options, conflict rejection and safe audit records.
- Web: 9 files, 27 tests passed; three calendar tests cover the four accessible views/filters/status action, move-conflict recovery and staff patient-search/create flow.
- TypeScript and production build passed. Output: JS 482.73 KB (143.34 KB gzip), CSS 294.23 KB (43.13 KB gzip).
- `git diff --check` passed before commit.

Remaining independent acceptance:

- Browser viewport checks at 320, 390, 768, 1024 and 1440 px.
- Physical pointer/touch drag behavior and keyboard/screen-reader traversal.
- Production PostgreSQL concurrent create/move conflict evidence.

The authoritative status remains in `../../REMEDIATION_PROGRESS.md`.
