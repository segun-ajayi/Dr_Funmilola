# QA-007 evidence

Status: READY FOR RETEST
Implementation revision: `1df4fdc`

- A pending cancellation request is reviewable only once. After staff declines it, the patient payload reports `declined` instead of the generic “Cancellation requested” state.
- A declined request restores `request_cancellation` in server-derived `allowed_actions`.
- Resubmission resets the same appointment-scoped record to pending, replaces the reason, clears reviewer/timestamp fields, sends a new staff notification and creates a second privacy-minimal request audit event.
- Pending cancellation or reschedule requests suppress both patient change actions, preventing contradictory requests.
- The web portal renders pending/declined labels and preserves a recoverable, labelled request form with success/error feedback.
- Regression: the dedicated lifecycle suite passes decline, display, resubmit, updated reason, reviewer reset, audit count and one-record assertions.
- Full backend: 82 tests/499 assertions. Web: TypeScript and 5 files/9 tests passed.

Independent authenticated patient/staff browser retest with a populated request queue remains required before PASS.
