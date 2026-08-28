# QA-008 evidence

Status: READY FOR RETEST
Implementation revision: `1df4fdc`

- Staff and approved patient-request reschedules now persist appointment status `rescheduled`, not `confirmed`.
- `rescheduled` is an active operational state: it blocks overlapping slots, can transition to checked-in/cancelled/no-show or another reschedule, receives reminders, can receive an online consultation room, and retains patient change actions when no review is pending.
- Audit metadata records the prior/new time and staff review decision; the patient receives the new-time notification.
- Conflict revalidation occurs inside the write transaction. A conflict appearing after the patient request leaves the request pending and the appointment unchanged.
- Web and native payloads display the rescheduled state consistently.
- Regression includes visible status, exact new UTC time, conflict blocking, reminder handling, consultation eligibility, audit entries and continued allowed actions.
- Full backend: 82 tests/499 assertions; dedicated lifecycle suite: 5 tests/61 assertions.

Production PostgreSQL concurrency and independent UI retest remain required before PASS.
