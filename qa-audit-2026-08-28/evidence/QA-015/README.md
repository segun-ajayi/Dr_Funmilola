# QA-015 evidence

Status: READY FOR RETEST (not self-declared PASS).

Implementation revision: `db49100`

- Verified Admin: patient-only search, pagination, invitation, identity/contact edits and active-state management.
- Verified Power Admin: all-role management with current-password confirmation for role/active changes.
- Moderator, patient, inactive and unverified authorization boundaries are server-enforced.
- Self-demotion, self-deactivation and removal of the last active Power Admin are rejected.
- Deactivation revokes the target's database browser sessions and personal-access tokens.
- Account lists and audits exclude credentials, reset/session/token material, clinical notes, documents and message bodies.
- Accessible Accounts UI is linked from the eligible staff navigation and preserves entered values after errors.

Automated evidence is in `AdministrationSecurityTest` and `AccountAdministrationPage.test.tsx`. Full verification: backend 102 tests/761 assertions; web 10 files/31 tests; TypeScript and production build passed.

Independent privilege/privacy review and authenticated browser/device acceptance remain required.
