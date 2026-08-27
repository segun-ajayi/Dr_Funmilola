# Mobile privacy and store-readiness checklist

## Privacy inventory

The authenticated app can display identity/contact details, appointments, consultation status, document metadata, practice messages and notifications. This can include health-related information and must be treated as sensitive personal data.

Current safeguards:

- TLS is mandatory outside local development.
- Tokens are scoped, expire server-side, are stored in native secure storage and are deleted on sign-out.
- Sensitive query data is memory-only and cleared with the session.
- No advertising, tracking SDK, analytics SDK, contact access, location access, camera access, microphone access or photo-library access is included.
- No native push token is collected while the server capability is disabled.
- Documents are not downloaded to shared device storage.
- The authenticated account and practice API remain the source of truth; no medical decision is made locally.

## Required before external beta

- Publish an approved privacy-policy URL and support URL under the practice's production domain.
- Complete a data-protection review covering lawful basis, retention, patient rights, processors, incident response and cross-border transfers.
- Confirm TLS, mail, backup, audit, retention and monitoring gates in `DEPLOYMENT.md`.
- Test on representative Android and iOS devices with TalkBack/VoiceOver, large text, offline/reconnect and expired/revoked sessions.
- Replace staging API URL with the production TLS endpoint through protected EAS environment configuration.
- Review all visible clinical wording and emergency guidance with the practice owner.

## Store listing and submission

- Finalise application name, subtitle/short description, long description, category, keywords and age/content rating.
- Capture current phone and tablet screenshots without real patient data.
- Provide privacy-policy, support and marketing URLs.
- Complete Apple App Privacy and Google Play Data Safety declarations from the privacy inventory above; obtain legal approval rather than guessing answers.
- Supply review notes and a non-production review account approved for store reviewers.
- Create Google Play application `com.drfunmilola.patient` and Apple bundle ID `com.drfunmilola.patient`.
- Configure app signing, team/account ownership, tax/commercial agreements where applicable and internal-test access.
- Obtain final approval for the DF icon and store artwork.
- Run the full repository and mobile quality gates against the exact release commit.

## Provider activation gates

Push notifications require an approved provider, token registration/removal, consent, delivery auditing, revocation and safe notification-body rules. Live video requires the separate approved telemedicine-provider security and compliance review. Neither capability may be described as available until the corresponding server capability is enabled and release tests pass.
