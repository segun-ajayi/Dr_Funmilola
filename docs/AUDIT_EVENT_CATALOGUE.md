# Audit event catalogue

This catalogue is the implementation contract for security and accountability events. Audit records must contain an actor where known, an action, a resource type and ID, a timestamp, and privacy-minimal metadata. They must never contain passwords, reset or access tokens, secrets, session payloads, document contents, message bodies, consultation media, or clinical narrative.

## Identity and access

| Event | Trigger | Permitted metadata |
| --- | --- | --- |
| `identity.registered` | Public patient registration | Source and request context only |
| `identity.account_invited` | Admin/Power Admin creates an invitation | Safe post-change identity/role/active/claim state |
| `identity.account_updated` | Authorized identity, role or active-state change | Changed field names and safe before/after values |
| `identity.signed_in`, `identity.signed_out` | Web authentication boundary | Request context only |
| `identity.password_reset` | Successful password reset and access revocation | Request context only |
| `identity.mobile_v1_token_created` | Mobile sign-in | Device name only |
| `identity.mobile_token_revoked` | Owned mobile/API token revocation | Device name only |
| `identity.web_session_revoked` | Owned browser-session revocation | Truncated one-way session reference only |

Deactivation and password reset revoke all database-backed browser sessions and personal-access tokens. Redis-backed session revocation requires production-runtime validation before closure.

## Patient care operations

Appointment creation/status/reschedule, cancellation/reschedule request decisions, availability rule/exception mutations, patient profile changes, document upload/rejection/scan/download, secure messaging actions, consultation state/attendance, and reminder changes use their existing namespaced events. Metadata is limited to resource IDs, state transitions, schedule values where operationally required, and field names; message, document and clinical bodies are excluded.

## Publishing and website operations

CMS page/section/settings preview, publish, unpublish, duplicate, restore and verification/research/education lifecycle events retain actor and resource identity. Safe structural state or source identifiers may be recorded; unpublished page bodies, credentials and provider secrets are excluded.

## Query and retention rules

- Audit queries are Power Admin-only, paginated, newest-first and limited to a maximum 90-day requested range.
- Filters support action prefix, actor, start date and end date.
- Response metadata is recursively redacted for password, token, secret, body, content and payload keys.
- The security retention command remains dry-run by default; production retention periods require approved governance configuration.
