# S2 — Actual-Page Text and Button Editing Evidence

Recorded: 30 August 2026  
Text implementation revision: `e3a0a54`  
Button implementation revision: `165223d`  
Status: text and rendered-button portions implemented and automated locally; linked-text controls and release journeys remain pending

## Delivered

- Double-clicking a rendered eyebrow, heading, paragraph, CTA text or button label places the browser caret inside that exact element. Typing updates the rendered draft immediately without opening a substitute form or modal.
- Paste is reduced to plain text and every edited field remains a structured section property; arbitrary page HTML is not persisted.
- A contextual inspector identifies the exact selection and changes font family, size, weight, bold, italic, underline, colour, alignment, line height, letter spacing and decoration live on that rendered element.
- Selecting a rendered Hero or CTA button edits that exact button. The same inspector controls its label, action type, safe destination, target behaviour, preset, icon, icon position, alignment, size, typography, colours, border, radius, padding, margin and visibility.
- Button actions support internal routes, external HTTP(S) destinations, email and telephone links. Safe rendering uses the router only for same-tab internal paths; new-tab destinations receive `noopener noreferrer`.
- All text and button presentation values use structured allow-listed maps. The server rejects unknown fields, unsupported action/style/icon/target values, unsafe URLs and CSS-like injection values.
- Local history, unsaved-state announcements and the whole-document atomic Save Draft bridge include these changes.

## Atomic checklist evidence contributed

These rows have implementation and automated evidence from the current S2 work, but are not declared final PASS until the required refresh/preview/publish/logout journeys, runtime matrix and independent acceptance are complete:

| IDs | Current S2 evidence |
| --- | --- |
| VE-023–VE-026, VE-028, VE-030–VE-045 | Exact rendered `contentEditable` cursor, live structured text changes and the complete contextual typography inspector in `CmsPublicPage.tsx`; actual-page regression in `AdminNavigation.test.tsx` |
| VE-046–VE-047 | Atomic structured persistence, named native controls, focus handling and keyboard-operable inputs are covered locally; the uninterrupted full-refresh journey and assistive-technology matrix remain pending |
| VE-066–VE-080 | Exact rendered-button selection plus complete action, icon, appearance, spacing and visibility controls in `CmsPublicPage.tsx` |
| VE-081 | Live rendering and atomic draft payload are covered locally; full refresh, preview, publish and logged-out confirmation remain S7 evidence |
| VE-082, VE-225 | `CmsService` validates safe route/HTTP(S)/email/telephone destinations and strictly allow-lists action, target, icon and nested presentation properties |

## Fresh automated evidence

- `php artisan test` — 110 tests, 860 assertions, PASS.
- `npm test -- --run` — 10 files, 32 tests, PASS.
- `npm run build` — production Vite build, PASS. The existing bundle-size advisory remains non-blocking for this slice.
- `git diff --check` — PASS before both implementation commits.

Dedicated regressions include:

- `persistent public Edit Mode uses a caret in the rendered element and atomically saves live typography`
- `rendered buttons expose complete live action and appearance controls`
- `test_advanced_button_image_and_typography_options_are_allowlisted`
- `test_visual_document_save_is_atomic_versioned_and_conflict_protected`

## Remaining S2 and release risk

Linked-text selection, link creation/edit/removal and its internal/external/email/telephone inspector (VE-083–VE-092) are the next active S2 task. Card and footer component coverage arrives with the expanded component model in S3. No implementation-only result self-closes QA-002 or QA-003: VE-232–VE-243, cross-browser/device/assistive-technology coverage and VE-242/VE-243 independent-QA gates remain pending.
