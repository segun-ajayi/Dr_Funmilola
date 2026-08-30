# True In-Place Visual Editor — Implementation Plan

Prepared: 30 August 2026  
Owners: implementation agent; independent QA for final acceptance  
Findings: QA-002 and QA-003  
Atomic source: `TRUE_IN_PLACE_VISUAL_EDITOR_ACCEPTANCE_CHECKLIST.md`  
Gap source: `evidence/QA-002/TRUE_EDITOR_GAP_MATRIX.md`

## Outcome

Power Admin edits the actual rendered public website, at the exact visible element, without being redirected to the existing `/staff/cms` form manager or a substitute canvas. Drafts remain private. Preview, publication and rollback reproduce the same structured page across the editor, preview and logged-out renderer.

The existing dashboard editor remains available as an operational fallback while this work is built, but it is not counted as acceptance evidence for the true in-place contract.

## Architecture decisions

1. Preserve the Laravel, React, Vite and Expo architecture. The web implementation extends the current CMS rather than replacing the application.
2. Use one versioned structured document contract. Page and Section remain database records; component type, element props, allow-listed styles and responsive overrides are structured JSON. Arbitrary whole-page HTML is never stored.
3. Give the actual public renderer an authenticated editing state and command bridge. The persistent toolbar and contextual inspectors operate on the page already being viewed.
4. Keep session-local history for Undo/Redo, while manual draft saves use an atomic batch endpoint guarded by `lock_version` to prevent silent overwrites.
5. Generate exact previews from the current structured draft, not an unrelated dashboard rendering. Publication snapshots one complete validated document in a transaction and retains the previous public version.
6. Treat media as durable records, not raw URL fields. Uploads must pass authorization, type/size validation and the configured malware scanner before they can be selected or published.
7. Reuse the same renderer for edit, preview and public modes so structure and presentation cannot drift between experiences.

## Delivery slices and commit gates

### S1 — Editor and document foundation

- Expand the editor context into persistent View/Edit state plus a page command bridge.
- Place Select, Add Section, Undo, Redo, Preview, Save Draft, Publish and Exit on the actual page.
- Add atomic structured-draft save, current-draft preview, publish, previous-version retention and direct rollback contracts.
- Add selection identity, dirty/saving/saved/failed states, conflict protection, authorization and audit events.
- Add backend feature tests and frontend interaction tests.
- Commit, push and record evidence before starting S2.

### S2 — True text, button and link editing

- Replace the modal textarea interaction with `contenteditable` at the exact rendered element; persist plain structured text and safe mark/style data only.
- Add live contextual typography for family, size, weight, bold, italic, underline, color, alignment, line height, letter spacing and decoration.
- Add exact rendered button and link selection with complete safe action, destination, target, icon, style and visibility controls.
- Cover keyboard behavior, accessible names/states, unsafe URL rejection, save/refresh/preview/publish continuity.
- Commit, push and record evidence before starting S3.

### S3 — Sections, component library and hierarchy

- Implement responsive section styles for desktop, tablet and mobile: backgrounds, overlays, spacing, size, layout, columns, alignment, gap, borders, radius, shadow and visibility.
- Implement direct move up/down, drag reorder, duplicate, hide and confirmed delete on the actual page.
- Add all 18 required section types: Hero, Rich Text, Image, Text + Image, Cards, Services, CTA, Publications, Career Timeline, Achievements, FAQ, Gallery, Statistics, Contact, Appointment Widget, Video, Divider and Spacer.
- Implement Page > Section > Component > Element selection and a visible breadcrumb.
- Commit, push and record evidence before starting S4.

### S4 — Visual navigation and new pages

- Select the rendered header navigation and edit names, internal/external destinations, visibility, deletion, order, additions and one submenu level in context.
- Keep navigation drafts private and apply only the published navigation to logged-out visitors.
- Add blank/template page creation, safe slug generation/collision handling, and navigate directly to the new actual public URL in Edit Mode.
- Commit, push and record evidence before starting S5.

### S5 — Persistent media and in-place images

- Add a searchable persistent media table, metadata, reusable selection, reference safety and explicit alt/decorative state.
- Add authorized image upload with validation, malware scanning, failure recovery and publication guards.
- Edit the exact rendered image: replacement, alt, caption, crop/focus, width, height, alignment, object fit, radius, link, overlay and opacity.
- Commit, push and record evidence before starting S6.

### S6 — Resilience, accessibility and responsive editor QA

- Add local recovery/autosave where appropriate, unload/exit protection, retry, session-expiry handling and truthful save announcements.
- Verify keyboard focus, names, roles, states, errors and live announcements.
- Verify overlays and controls at desktop, tablet and mobile editor widths.
- Exercise validation, network, server and concurrency failures without silent loss or false success.
- Commit, push and record evidence before starting S7.

### S7 — Continuous journeys and release handoff

- Run VE-232 text, VE-233 image, VE-234 section, VE-235 rollback and VE-236 role/API journeys without interruption.
- Run the full backend, web and native regression suites plus production builds.
- Update every VE-001–VE-243 evidence row with revision, test, environment, route and observed result.
- Leave VE-242 and VE-243 for independent QA; do not self-mark QA-002 or QA-003 PASS.
- Commit and push the final evidence handoff.

## Acceptance discipline

- Each slice starts from the checked-in plan and ends with tests, evidence, a commit and a push.
- Existing tests or behavior can be linked only after fresh verification against the stricter wording.
- A dashboard control is not evidence for an interaction that must occur on the rendered public page.
- A component is not complete until its edit survives full refresh, exact preview, publication and logged-out rendering where the row requires it.
- The application remains **DO NOT RELEASE** for QA-002/QA-003 until independent QA completes the release gates.

## Immediate next action

Implement S1 only. Do not begin surface-area controls until the structured atomic save/preview/publish/rollback and actual-page toolbar foundation is green and pushed.

