# S3 — Sections, Components and Hierarchy Build Plan

Recorded: 30 August 2026
Status: active source of truth
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
