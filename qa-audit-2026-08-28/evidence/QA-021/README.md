# QA-021 evidence

Status: READY FOR RETEST (not self-declared PASS).

Implementation revision: `db49100`

- Password reset updates the credential and remember token, claims an invited account when applicable, deletes all personal-access tokens and deletes all database-backed browser sessions for the user.
- Account deactivation applies the same session/token revocation boundary.
- Authenticated users can list masked owned browser sessions and app/API devices.
- Session references are one-way hashes; IP addresses are masked; raw session IDs and payloads are never returned or audited.
- A user can revoke an owned non-current browser session or owned token and cannot address another user's access.
- The current browser must use the explicit sign-out flow.

Backend reset, ownership and revocation regressions pass. The rendered Security page covers browser/app access and announced recovery states.

Independent real multi-browser/mobile acceptance and any future non-database session-store adapter remain required.
