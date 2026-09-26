# S6.3 — Failure Matrix and Audit Evidence

Recorded: 26 September 2026
Status: implementation evidence complete; independent acceptance and release recommendation remain pending
Planning revision: `aedf594`
Implementation/test revision: `05bf35a`

## Scope proved

S6.3 closes the automated resilience evidence gate for the true in-place editor. It does not self-approve the final continuous journeys, a physical-device or screen-reader result, QA-002, QA-003 or a release recommendation.

## Complete forced-failure matrix

The actual editor regression in `resources/js/pages/CmsEditorResilience.test.tsx` exercises this complete cross-product:

| Operation | Forced outcomes | Assertions made in every case |
|---|---|---|
| Save Draft | network/no response, 401, 419, 409, 422, 429, 500 | Exact working text and recovery document retained; operation-specific safe error displayed; no false save success; one correct request only; server draft unchanged; server secret text excluded. |
| Preview | network/no response, 401, 419, 409, 422, 429, 500 | Exact working text and recovery document retained; no success; no preview window opened; one correct request only; server draft unchanged; server secret text excluded. |
| Publish | network/no response, 401, 419, 409, 422, 429, 500 | Exact working text and recovery document retained; no success; no preview side effect; one correct request only; server/public state not unintentionally changed; server secret text excluded. |

This is 21 deterministic failure journeys. Existing S6.1 cases additionally re-prove safe manual retry, session restoration by the same account, single-flight saves, editing during an in-flight request, paused failed autosave, unload protection and explicit conflict review/recovery.

## Reload and concurrency continuity

Fresh backend and web runs include the existing structured-document lifecycle and recovery tests:

- simple, responsive, media-backed and nested component values survive atomic save and complete API reload, then remain exact through preview, publication and rollback;
- stale concurrent writes receive conflict handling and are never automatically merged or resubmitted;
- choosing to inspect the latest server draft first preserves a recoverable local copy;
- restoring a copy based on an older lock version warns that a later save would replace the newer whole-page draft and requires an explicit user choice;
- a recovery snapshot cannot silently supersede a confirmed server document.

## Privacy-minimal audit proof

`tests/Feature/CmsTest.php` now performs a complete CMS lifecycle and inspects its stored audit events. The regression proves:

- the acting Power Admin is recorded as the actor;
- the CMS page is the typed subject for visual-draft save, preview, publish and rollback;
- preview schema version, publication lock version, rollback source version and resulting lock version are present where applicable;
- a unique marker embedded in page content never appears in audit metadata;
- metadata does not contain body, content, password, token or secret-bearing keys.

## Fresh verification result

- Backend: 125 tests, 1,227 assertions — passed.
- Web: 12 files, 89 tests — passed.
- Focused failure cross-product: 21 cases — passed.
- Strict TypeScript check — passed.
- Production asset build — passed: JS 595.41 KB / 174.35 KB gzip; CSS 329.35 KB / 48.94 KB gzip.
- Changed-file PHP formatting — passed.
- `git diff --check` — passed.
- Database migration cycle — not applicable because S6.3 added no migration.
- Known residual build note: the existing non-blocking JavaScript bundle-size warning remains.

## Handoff boundary

S6 implementation evidence is complete. S7 must run and record the uninterrupted VE-232 text, VE-233 image, VE-234 section, VE-235 rollback and VE-236 role/API journeys, reconcile all VE evidence rows, and run full backend, web and native gates. Named screen-reader, physical-device, browser zoom/reflow and independent QA evidence must be supplied by the appropriate independent reviewer. VE-242 and VE-243 and QA-002/QA-003 remain pending and are not self-approved here.
