# Mobile API v1

Base path: `/api/v1`. Responses use `{ data, message? }`; lists add `meta` and `links`; failures use `{ error: { code, message, fields? } }`.

Public capability discovery: `GET /capabilities`.

Authentication: `POST /auth/token` with email, password and device name. Only active, claimed, verified Patient accounts receive a 30-day scoped Sanctum token. Send it as `Authorization: Bearer <token>`. `DELETE /auth/token` revokes the current token.

Patient resources: `GET /me`, `/appointments`, `/documents`, `/message-threads`, `/notifications`, `/consultations`. Pagination accepts `per_page=1..50`.

Offline-safe mutation: cancellation requests require a UUID `client_request_id`. Repeating the same identifier for the same user returns the recorded response and `X-Idempotent-Replay: true` without repeating the side effect.

Push registration currently returns `409 conflict`; the contract exists but cannot store a device token until a provider and lifecycle policy are approved.

Version 1 is additive. Breaking field or semantics changes require `/api/v2`; existing web endpoints are not the mobile stability contract.
