# QA-017 evidence

Status: READY FOR RETEST
Implementation revision: `1df4fdc`

- The patient appointment API and versioned native API now return server-derived `allowed_actions`.
- Both clients use only `request_cancellation` and `request_reschedule`; neither infers eligibility from a local status blacklist.
- Requested, pending-confirmation, confirmed and rescheduled appointments expose both actions when no review is pending.
- Checked-in, in-progress, completed, cancelled and no-show expose no patient change actions.
- Any pending cancellation or reschedule request suppresses both actions until staff review.
- The backend all-state matrix passes, including native idempotent replay. A rendered native test proves a checked-in card has no cancellation action while a server-authorized confirmed card exposes and loads reschedule choices.
- Web TypeScript and 5 files/9 tests passed. Native TypeScript, 5 suites/7 tests and public Expo iOS/Android configuration passed.

Physical iOS/Android, TalkBack/VoiceOver and independent web/native state-matrix acceptance remain required before PASS.
