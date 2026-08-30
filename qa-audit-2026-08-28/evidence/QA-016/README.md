# QA-016 evidence — native patient application

Status: READY FOR RETEST, not PASS.
Implementation revision: `998c871`.

## Implemented patient journeys

- Profile and emergency-contact editing with communication preference and retry-safe submission.
- Reminder preferences with push held off until a provider is approved and configured.
- Owned mobile-device listing and non-current device revocation; the current device must use sign out.
- PDF/JPEG/PNG selection, multipart upload, fail-closed security scanning, private ownership, audited download, and native open/save sharing.
- Private message compose/reply with drafts retained after failure.
- Owned notification mark-read.
- Consultation consent, waiting-room, admit-state connection preparation and leave using the provider-independent state machine.
- Existing server-provided appointment `allowed_actions` remains the only source of cancellation/reschedule actions.

## Security and contract evidence

- `/api/v1` remains protected by active, verified, patient-role, `mobile:v1` Sanctum middleware.
- Retryable writes require UUID request identifiers and replay the original result without duplicate side effects.
- Reusing one request identifier for a different operation returns a stable conflict instead of replaying unrelated data.
- Document, thread, notification and consultation operations enforce the authenticated patient's ownership.
- Document storage paths and consultation room locators do not appear in mobile JSON responses.
- Download events are audited without storing clinical file contents in the audit record.
- Push and live-video capability flags remain `false`; the native app requests neither camera nor microphone permission.

## Automated results

- Full backend: 107 tests, 813 assertions.
- Focused mobile operations: 5 tests, 52 assertions covering retry safety, cross-operation replay rejection, file scanning/download isolation, message isolation, notification ownership, preferences, devices and consultations.
- Native: 7 suites, 13 tests. Coverage includes profile submission, multipart boundary preservation, document selection/upload, message failure recovery, notification read, consultation consent, appointment allowed actions, secure token storage and accessible shared controls.
- Expo validation: TypeScript and `expo config --type public` pass for SDK 57 with Android package and iOS bundle identifier `com.drfunmilola.patient`.
- Web regression: 10 files, 31 tests; TypeScript and production build pass.
- Production web output: JS 497.52 KB (146.40 KB gzip), CSS 296.95 KB (43.50 KB gzip).
- `git diff --check` passes.

## Expo references used

- [Expo SDK 57 DocumentPicker](https://docs.expo.dev/versions/v57.0.0/sdk/document-picker/)
- [Expo SDK 57 FileSystem legacy API](https://docs.expo.dev/versions/v57.0.0/sdk/filesystem-legacy/)
- [Expo SDK 57 Sharing](https://docs.expo.dev/versions/v57.0.0/sdk/sharing/)
- [Expo SDK 57 Router and deep linking](https://docs.expo.dev/versions/v57.0.0/sdk/router/)

## Independent acceptance still required

No physical iOS or Android device is attached to this environment. Release acceptance must still cover real-device secure storage, document providers, interrupted uploads/downloads, share sheets, deep-link cold/warm starts, token expiry/revocation, 320–tablet layouts, keyboard/external-switch operation, VoiceOver and TalkBack. App Store/Play signing, an approved push provider, and the approved privacy-preserving live-video provider are also unavailable. These are explicit external gates; local mocks and the unconfigured provider are not production evidence.
