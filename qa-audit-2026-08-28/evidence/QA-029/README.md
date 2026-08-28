# QA-029 evidence

Status: READY FOR RETEST
Implementation revision: `1df4fdc`

- Verified owners can choose a generated available slot and submit a reschedule request on web or the versioned native API; another patient receives 403.
- The appointment time/status stays unchanged while the request is pending. Staff receives a notification and sees a separate reschedule queue with current/requested times.
- Staff may decline or approve only a pending request. Decline preserves the appointment and notifies the patient; the patient may submit another eligible time.
- Approval uses the same scheduling truth and date lock as direct staff reschedule. It rechecks availability, changes the appointment to active `rescheduled`, audits request/review/time change and notifies the patient.
- A conflict introduced between request and approval returns validation failure, rolls the review back and leaves the request pending.
- Native mutations are idempotent by patient/request UUID. Replay creates neither a second request nor a second side effect.
- The web portal has labelled date/reason controls, generated time buttons and announced success/error states. The native screen has an accessible date field, generated time buttons and server-controlled actions.
- Full backend: 82 tests/499 assertions; dedicated lifecycle: 5 tests/61 assertions; web 5 files/9 tests; native 5 suites/7 tests.

Authenticated populated-queue browser E2E, physical iOS/Android accessibility and production PostgreSQL concurrency remain required before PASS.
