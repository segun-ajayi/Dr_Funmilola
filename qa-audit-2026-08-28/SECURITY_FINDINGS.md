# Security, Privacy and Authorization Findings

Audit date: 28 August 2026  
Approach: practical application-level review, safe role/API manipulation, test review and code inspection. No denial-of-service, malicious payload, production account, real patient data or destructive test was used.

## Security disposition

No Critical security issue and no cross-patient data exposure was reproduced within the tested scope. This is not a statement that the application is secure or compliant. Two unresolved release-significant risks remain: the upload scanner is not production-grade (QA-022, High) and active browser sessions are not revoked on password reset (QA-021, Medium, Potential Security Finding). Audit-record coverage is incomplete (QA-023).

## Positive controls verified

- Patient appointment ownership: Patient A received 403 for Patient B's appointment ID; self list returned only owned records.
- Role boundaries: patient could not access staff/CMS; moderator and admin could not access CMS; Power Admin could access CMS list.
- Consultation ownership and signed expiry are covered by passing feature tests; room locator is hidden from ordinary payloads.
- Documents use private local storage, hide storage_path, and authorize owner/staff download.
- Upload validation restricts declared files to PDF/JPEG/PNG and 10 MB.
- Cookie-auth mutations use Laravel/Sanctum CSRF protection; mobile uses scoped, expiring tokens.
- Sensitive endpoints have rate limits, including authentication, booking, upload, preview, consultation join and audit queries.
- Runtime headers included CSP, X-Frame-Options DENY, nosniff, strict-origin referrer policy, permissions policy, COOP and CORP. HSTS is correctly conditional on HTTPS.
- .env is ignored, no tracked secret was found, and production example uses APP_DEBUG=false and secure cookie guidance.
- Forgot-password response is generic and reset passwords use strong validation.

## Findings

### QA-021 — Potential Security Finding: password reset does not revoke browser sessions — Medium

ResetPasswordController changes the password/remember token and deletes Sanctum personal tokens, but does not remove other database session records. A stolen authenticated browser session may therefore survive the user's credential reset. This was established by implementation evidence; session theft/exploitation was not attempted.

Release action: invalidate other sessions on password reset and verify with two simultaneous browser sessions plus mobile tokens.

### QA-022 — Production-grade malware scanning is not configured — High

BasicFileScanner reads only the first 8192 bytes and searches for four strings. This is useful as a narrow boundary but is not malware analysis. The repository plan itself states that production should bind ClamAV or an approved managed scanner. Because uploads can hold sensitive medical documents and be downloaded by staff, this is a release gate.

Release action: quarantine, scan, fail closed, record monitored outcomes and test clean/rejected/provider-timeout behavior.

### QA-023 — Audit logs omit important actions and consistent old/new values — Medium

Identity, appointment status/reschedule/cancellation, availability, CMS versions/settings, research, education and several consultation/token events are logged. Gaps include successful document upload/download, message mutations, consultation consent/join/leave and all role changes. Metadata often records a decision but not the previous value. Patient profile audit captures fields changed rather than old/new values.

Release action: publish an auditable-event catalogue and assert actor/action/resource/time/safe before-after metadata for every listed mutation.

## Security-related functional integrity

These are primarily functional/data findings but affect safe operations:

- QA-004 (High): a forged booking can bypass availability and service method restrictions.
- QA-005 (High): conflict prevention has a concurrent race with no database invariant.
- QA-013 (High): research rejection after publication does not retract the public record.
- QA-015 (High): user/role management is absent, so controlled role lifecycle and corresponding audits do not exist.

## File upload assessment

| Check | Result | Evidence/limitation |
| --- | --- | --- |
| MIME/extension allowlist | PASS | Laravel file plus mimes validation for pdf,jpg,jpeg,png |
| Size limit | PASS | max:10240 (10 MB) |
| Storage path exposure | PASS | storage_path hidden from API |
| Owner/staff download | PASS | explicit authorization plus feature coverage |
| Unsupported extension | PASS in automated coverage | UploadSecurityTest/controller validation |
| Renamed extension/content mismatch | PARTIAL | Framework MIME validation plus basic scanner; full adversarial runtime matrix not executed |
| Oversized upload | PARTIAL | Server rule present; no large runtime file sent |
| Direct storage URL | PASS by design review/tests | Files delivered through authorized controller; no public path in payload |
| Malware analysis | FAIL | QA-022 |
| Upload/download audit | FAIL | QA-023 |

## Session and token management

- Web sessions: cookie-based and HttpOnly; SameSite=Lax locally. Production example enables secure cookies under TLS.
- Mobile tokens: 30-day expiry, device name, scoped abilities, explicit current-token revoke and device-list/revoke on the web API.
- Password reset: mobile/API tokens revoked, browser sessions not revoked (QA-021).
- Native storage: access token is stored through Expo SecureStore; no token was found in app logs/source constants.
- Account enumeration: registration returns an explicit existing-account message. This is a low-level privacy tradeoff; forgot-password remains generic. Consider aligning messaging if the threat model treats patient membership as sensitive.

## Headers and browser policy

Observed local response headers with APP_DEBUG=false:

- Content-Security-Policy: self for scripts/connections; Google Fonts allowlist; frame-ancestors none; base-uri/form-action self.
- X-Frame-Options: DENY.
- X-Content-Type-Options: nosniff.
- Referrer-Policy: strict-origin-when-cross-origin.
- Permissions-Policy: camera/microphone self, geolocation disabled.
- Cross-Origin-Opener-Policy and Cross-Origin-Resource-Policy: same-origin.

Style CSP includes unsafe-inline. That is a hardening opportunity rather than a standalone finding in this audit because no exploitable injection path was reproduced.

## Not tested / external assurance needed

- Independent penetration testing, SAST/dependency CVE analysis and production TLS scan.
- PostgreSQL row-lock/concurrency behavior under real simultaneous bookings.
- Real malware service, mail/SMS/push/video providers and their data-processing/hosting controls.
- Production backup encryption, restore drill, monitoring, log retention and incident response.
- Legal/privacy compliance, lawful basis, retention and patient-rights assessment.
- Physical-device secure storage, screenshots, rooted/jailbroken device behavior and mobile traffic interception.

## Security release gates

1. Resolve QA-022 and verify fail-closed upload quarantine/scanning.
2. Resolve QA-004 and QA-005 before accepting real appointments.
3. Resolve QA-021 and retest multi-session revocation.
4. Complete QA-023 audit coverage for privacy-relevant actions.
5. Conduct production environment, dependency and independent penetration testing after deployment hardening.
