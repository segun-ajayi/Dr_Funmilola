# S6 — Editor Resilience, Accessibility and Viewport Build Plan

Recorded: 2 September 2026
Status: active implementation source of truth; S6.1 in progress
Acceptance scope: VE-199 through VE-205 and VE-227 through VE-231, with shared validation evidence for VE-225 and VE-226 and final uninterrupted release journeys reserved for VE-232 through VE-236 and VE-242 through VE-243

## Objective

Make Power Admin Edit Mode safe during unreliable networks, expired sessions, concurrent edits and accidental navigation, while keeping every editing control operable and understandable with keyboard, screen reader, touch and narrow viewports. A failed operation must never display success, discard the working document or silently overwrite another editor.

## Existing foundation and gaps

- The editor already distinguishes clean, unsaved, saving, saved and failed draft states, keeps the in-memory document after a failed save, supports manual Save Draft, and warns before browser unload or exiting Edit Mode with unsaved work.
- Server saves already use an optimistic `lock_version` and return HTTP 409 when another editor has changed the page. The client currently treats that response like a generic failure and provides no explicit conflict recovery path.
- Preview and publication use the current structured document and preserve the private/public boundary, but network, validation, throttling and expired-session responses do not yet have truthful, actionable messages.
- There is no opt-in autosave, durable local recovery snapshot, retry action or session-expiry workflow. A full refresh before a successful save can therefore lose edits after the browser confirmation is accepted.
- The editor has labelled controls, visible focus foundations, Escape support in major media dialogs and live status text. It still needs consistent modal focus containment, focus return, failure announcements, toolbar overflow behavior and touch/narrow-screen inspector treatment.
- The backend already records CMS draft, publication and rollback audit events. S6 must prove that actor, page, action, version and publication/rollback metadata are useful while secrets and page bodies remain excluded.

## Guardrails

- Only an active, verified Power Admin may enter or mutate Edit Mode. Recovery, retry and autosave must not weaken that boundary or persist authentication material.
- Autosave is opt-in, never publishes, never runs while another mutation is active, and never claims success until the server confirms the exact document version.
- Store at most one bounded recovery snapshot per page and signed-in editor in browser-local storage. Store structured CMS draft content and timestamps only; never store tokens, cookies, passwords, scanner details or unrelated patient/clinical data.
- Keep local work after network, 401/419 session expiry, 409 conflict, 422 validation, 429 throttling and 5xx failure. Do not redirect away, reload automatically, overwrite the server version or clear recovery data on failure.
- A 409 conflict must be explicit. The editor may reload the server copy only after a deliberate user action; it must never automatically resubmit stale content or merge ambiguously.
- Clear the recovery snapshot only after the same document is successfully saved, deliberately discarded or safely replaced by a user-confirmed server reload. Published state remains an explicit separate action.
- Use a single save request at a time. Changes made while a save is in flight remain unsaved and schedule no overlapping autosave.
- Every save, retry, recovery, conflict, session-expiry, preview and publication status must be announced truthfully. Failure messages must be safe, concise and must not expose server internals.
- Dialogs and overlays require an accessible name, modal semantics, initial focus, contained Tab/Shift+Tab behavior, Escape close where safe, and focus return to the opener.
- All editing actions must have visible focus, programmatic name/role/state and a non-pointer path. Touch targets and overlays must remain usable without horizontal page loss at 320, 390, 768, 1024 and 1440 CSS pixels.
- Preserve structured-document validation, exact preview, publication atomicity, history/Undo/Redo, media references and responsive presentation from S1 through S5.
- Each bounded S6 task is tested, committed, pushed and recorded here before the next task starts.

## State contract

The editor will expose these truthful draft states:

- `clean`: the loaded server version and current document match;
- `unsaved`: local changes exist and are protected by the recovery snapshot;
- `saving`: one manual, retry or enabled-autosave request is active;
- `saved`: the server confirmed the exact current document and recovery is cleared;
- `failed`: the request failed but the document and recovery snapshot remain;
- `conflict`: the server rejected a stale lock version and local work remains available;
- `session-expired`: authentication must be restored before retry and local work remains available.

If the user changes the document while `saving`, the confirmed response updates the server lock version but the editor returns to `unsaved`; it must not misreport those newer changes as saved.

## Delivery tasks

### S6.1 — Save resilience, recovery and concurrency

- Centralize safe classification for offline/network, 401/419, 409, 422, 429 and 5xx responses and map each to a truthful editor state and actionable message.
- Add a visible Retry Save action after recoverable failure. Keep the exact document, history, selection and inspector state while retrying.
- Add an opt-in Autosave control, persist only that local preference, debounce/interval saves safely, and announce whether autosave is on, pending or unsuccessful.
- Write a bounded per-user/per-page recovery snapshot whenever the document becomes unsaved. On a later editor load, offer Restore or Discard when the snapshot is newer/different from the server draft.
- Preserve unload and Exit Edit Mode protection. Make discard consequences explicit and never clear local recovery merely because Edit Mode closes.
- Handle edits made during a save without false success and reject stale concurrent writes without automatic overwrite. Provide deliberate server-reload and keep-local/retry guidance.
- Add actual-page regressions for manual save, enabled autosave, failed retry, refresh recovery, session expiry, validation, throttling, server failure and two-editor conflict.

### S6.2 — Keyboard, assistive technology and responsive overlays

- Complete accessible names, roles, pressed/expanded/selected/busy/invalid states and live success/error announcements for the website toolbar, hierarchy, inspectors, component chooser, media chooser and page dialog.
- Contain focus within open modal dialogs, place initial focus predictably, close with Escape where safe and restore focus to the exact invoking control.
- Ensure the exact rendered selection path, section/component actions, reorder controls, Undo/Redo, preview, save, publish, retry, autosave and exit are fully keyboard operable with visible focus.
- Prevent toolbar controls, breadcrumbs, inspectors, dialogs and action groups from clipping or forcing unusable horizontal page scrolling at 320, 390, 768, 1024 and 1440 CSS pixels.
- Keep primary actions reachable with coarse pointer/touch targets, safe-area spacing and overlay scrolling; respect reduced motion, forced colours and zoom/reflow.
- Add semantic and interaction regressions plus viewport-specific structural assertions. Record physical device and screen-reader checks as independent acceptance rather than fabricating evidence.

### S6.3 — Failure matrix, audit proof and phase evidence

- Run the complete forced error matrix for save, preview and publish: offline/no response, 401, 419, 409, 422, 429 and 500. Prove there is no false success, data loss, silent redirect or unintended publication.
- Prove successful save plus complete reload restores exact text, styles, media, structure and responsive values, and that recovery snapshots never supersede a newer confirmed draft without user choice.
- Prove concurrent editors cannot silently overwrite one another and that deliberate recovery preserves a copy of local work.
- Verify CMS audit events include actor, page, action, version and publication/rollback identifiers where applicable, while excluding full page bodies, credentials, tokens and secret-bearing request data.
- Run full backend and web suites, PHP formatting, TypeScript, production build and `git diff --check`. Run migration rollback/reapply only if S6 changes the database.
- Record evidence by VE ID without self-declaring VE-232 through VE-236, VE-242, VE-243, QA-002 or QA-003 complete.

## Test gates

Every S6 task must prove:

- manual and enabled-autosave requests are single-flight and success is shown only for the exact server-confirmed document;
- failed saves retain the exact unsaved document and provide retry without duplicate publication or lost Undo/Redo state;
- refresh recovery is page- and editor-specific, bounded, contains no authentication material and can be restored or explicitly discarded;
- 401/419, 409, 422, 429, network and 5xx responses have distinct truthful behavior with no silent overwrite, redirect or data loss;
- unload and Edit Mode exit warn only when local work still needs protection;
- every modal has labelled semantics, initial focus, focus containment, Escape behavior and focus return;
- toolbar, inspector and dialog controls remain named, keyboard/touch reachable, visibly focused and announced at the required viewports;
- audit events are useful for accountability and privacy-minimal;
- full `php artisan test`, full `npm test -- --run`, `npm run typecheck`, `npm run build` and `git diff --check` pass at the phase evidence gate.

## Completion rule

S6 implementation is complete only when a Power Admin can keep editing through offline, expired-session, validation, throttling, server and concurrency failures without losing or silently overwriting work; can intentionally recover or discard a local snapshot; can use all editor overlays by keyboard and touch from 320 px through desktop; and receives accurate accessible status throughout save, preview and publication. Automated implementation evidence may advance VE-199 through VE-205 and VE-227 through VE-231, but final uninterrupted journeys, independent device/screen-reader acceptance and QA finding closure remain outside this agent's self-approval authority.

## Implementation ledger

### S6.1 — In progress

- Source-of-truth plan recorded before implementation.
- Next task: implement the state classifier, retry, opt-in autosave, bounded local recovery and explicit conflict/session-expiry behavior with actual-page regression coverage.

### S6.2 — Pending

- Begins after S6.1 is implemented, tested, committed, pushed and recorded here.

### S6.3 — Pending

- Begins after S6.2 is implemented, tested, committed, pushed and recorded here.
