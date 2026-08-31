# S3 — Sections, Components and Hierarchy Build Plan

Recorded: 30 August 2026
Status: active source of truth; S3.1 and S3.2 complete, S3.3 active
Acceptance scope: VE-093 through VE-157, with persistence foundations shared with VE-191 through VE-225

## Objective

Extend the actual-page editor from element editing to complete section and component composition without introducing a substitute canvas. Every operation must change the page already being viewed, remain a private structured draft until explicit publication, and use the existing atomic document/history bridge.

## Guardrails

- Preserve the public renderer as the only editor renderer; Edit Mode may add outlines, controls and constrained responsive preview framing, but it must not use a separate approximation.
- Keep content and presentation structured and allow-listed. Never persist arbitrary HTML, script, event handlers, raw CSS, unapproved component properties or unsafe URLs.
- Preserve existing page and section records during schema expansion. New fields are optional and have safe renderer defaults.
- Treat managed media upload/library/scanning as S5. S3 may expose structured background/media references only where they already meet the current safe URL contract; it must not claim VE-049, VE-050 or VE-065 closure.
- Each bounded S3 task is tested, committed, pushed and recorded here before the next task starts.

## Data contract

### Section presentation

The section presentation allow-list will cover:

- background colour, gradient preset, safe background image reference, pattern, breast-contour treatment and overlay preset/opacity;
- padding, margin, width, minimum height, maximum height, layout, columns, content alignment and gap;
- border style/width/colour, radius, shadow and visibility through the existing section flag;
- a `responsive` structured map with independent `desktop`, `tablet` and `mobile` values using the same finite tokens.

The renderer will resolve base values followed by the active responsive viewport values. Editor viewport buttons will frame the same page renderer at desktop, tablet or mobile widths so matching changes are visible immediately.

### Component catalogue

The supported `type` allow-list will contain exactly these required editor components:

1. Hero
2. Rich Text
3. Image
4. Text + Image
5. Cards
6. Services
7. CTA
8. Publications
9. Career Timeline
10. Achievements
11. FAQ
12. Gallery
13. Statistics
14. Contact
15. Appointment Widget
16. Video
17. Divider
18. Spacer

Each type receives a finite content schema, safe defaults, a public renderer and an actual-page editing state. Nested repeaters use bounded arrays with stable component keys and per-item allow-lists.

## Delivery tasks

### S3.1 — Section inspector, responsive model and lifecycle

- Select a whole rendered section independently of its text/button children.
- Add a section toolbar and contextual inspector for every required base/responsive presentation value.
- Add desktop/tablet/mobile preview controls using the same renderer.
- Implement immediate move up, move down, HTML drag-and-drop reorder, duplicate, hide/show and confirmed delete.
- Ensure all operations use the existing document history and atomic Save Draft payload.
- Add server allow-list tests and actual-page interaction tests.
- Commit, push and record revision/test evidence.

### S3.2 — Actual-page Add Section library and 18 renderers

- Replace the one-click Rich Text insertion with a searchable/labelled component library opened by Add Section on the actual page.
- Add safe default factories and renderer branches for all 18 types.
- Insert at the intended position, select the new section immediately and keep the operation undoable.
- Add backend round-trip coverage for every component type and frontend coverage that every catalogue choice appears and inserts the correct renderer.
- Commit, push and record revision/test evidence.

### S3.3 — Nested repeaters and hierarchy

- Add bounded nested editing for Cards, Services, Publications, Career Timeline, Achievements, FAQ, Gallery and Statistics.
- Give nested components stable selection, add/duplicate/reorder/hide/delete controls where the schema supports them.
- Expose and maintain a visible Page › Section › Component › Element breadcrumb.
- Allow safe parent/child selection changes without accidental deletion or navigation.
- Extend keyboard names, focus states and live announcements.
- Commit, push and record revision/test evidence.

### S3.4 — Persistence and evidence gate

- Run full backend and web suites, production build, formatting/diff checks and any schema rollback/reapply required by implementation.
- Exercise draft save and reload, exact preview snapshot, publish/logged-out renderer and previous-version rollback for representative simple, responsive and nested components.
- Record atomic evidence by VE ID without self-declaring the independent-QA gates.
- Commit and push the evidence update before beginning S4.

## Test gates

Every S3 task must pass:

- direct role/API denial for non-Power-Admin mutation paths;
- unknown component, nested property, responsive property and CSS-like value rejection;
- immediate actual-page render plus Undo/Redo and atomic save assertions;
- accessible names/state for every new control;
- full `php artisan test`, full `npm test -- --run`, `npm run build` and `git diff --check`.

## Completion rule

S3 implementation is complete only when all 18 components can be inserted on the actual page and representative simple/nested sections can be selected, styled responsively, reordered, duplicated, hidden, deleted, saved, reloaded, previewed and published through the shared document contract. VE-148, VE-127 and all release-level rows remain pending until their uninterrupted S3.4/S7 journeys are recorded; VE-242 and VE-243 remain independent-QA gates.

## Implementation ledger

### S3.1 — Complete

- Revision: `a894ed9`.
- Delivered: actual-page whole-section selection and toolbar; desktop/tablet/mobile preview framing; independent responsive background, gradient, safe image reference, pattern, contour, overlay, spacing, width/height, layout/columns, alignment/gap, border/radius/shadow controls; move up/down; dedicated-handle HTML drag reorder; duplicate; hide/show; confirmed delete with Undo.
- Data safety: finite base/responsive server allow-lists, bounded viewport keys, strict safe background URL validation and unknown/CSS-like property rejection.
- Automated evidence contributed: VE-093–VE-127, VE-149–VE-150 and VE-156 foundations. VE-127 remains release-journey pending rather than self-declared PASS.
- Fresh gate: backend 110 tests / 867 assertions; web 10 files / 35 tests; production build PASS; `git diff --check` PASS. An initial web run under simultaneous backend/build load timed out in an unrelated calendar test; the required isolated full rerun passed all 35 tests.
- Next task: S3.2 actual-page Add Section component library and safe renderers/default factories for all 18 required component types.

### S3.2 — Complete

- Revision: `189d4bb`.
- Delivered: searchable actual-page Add Section library; explicit start, end or after-section insertion location; immediate selection and Undo availability; safe default factories and public/editor renderer branches for Hero, Rich Text, Image, Text + Image, Cards, Services, CTA, Publications, Career Timeline, Achievements, FAQ, Gallery, Statistics, Contact, Appointment Widget, Video, Divider and Spacer.
- Data safety: the server type allow-list contains exactly the 18 required components; every type has a finite top-level schema; the eight repeatable components use bounded structured item arrays with optional UUID keys, per-item allow-lists, safe URL checks and visibility booleans. Unknown component types, nested fields, HTML, CSS-like properties and unsafe URLs are rejected. Default Contact content does not invent a public telephone number, email address or exact clinic address.
- Automated evidence contributed: VE-128–VE-148 foundations, including catalogue visibility, insertion location, immediate actual-page rendering, stable ordering and atomic-save coverage. Nested-item manipulation and hierarchy remain S3.3; managed media selection remains S5; VE-148 remains release-journey pending rather than self-declared PASS.
- Fresh gate: backend 110 tests / 910 assertions; web 10 files / 36 tests; production build PASS; `git diff --check` PASS. The component interaction regression inserts and renders all 18 types in one actual-page draft and verifies the ordered atomic payload.
- Next task: S3.3 bounded nested repeater editing and visible Page › Section › Component › Element hierarchy.
