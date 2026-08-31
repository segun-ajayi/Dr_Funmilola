# S4 — Visual Navigation and New Pages Build Plan

Recorded: 31 August 2026
Status: active source of truth; S4.1 ready to implement
Acceptance scope: VE-158 through VE-178, with draft/publication continuity shared with VE-179 through VE-200 and authorization evidence shared with VE-236

## Objective

Make the rendered website header itself selectable and editable in Power Admin Edit Mode, then provide a new-page workflow that opens the created page at its real website address for immediate visual editing. Navigation and page drafts must remain private until their own explicit publication actions succeed.

## Existing foundation and gaps

- Published navigation already drives the public header, and the protected settings API already separates draft and published values.
- The dashboard settings form can rename, add, reorder, hide and create one submenu level, but it does not satisfy the actual-page contract and currently permits internal paths only.
- Page create, preview and publication APIs exist, but creation is dashboard-bound, requires a manually supplied slug, provides no explicit blank/template starting choice and does not navigate to the new rendered page.
- The current protected-slug list and `alpha_dash` validation do not exactly match the public route grammar and do not reserve every application-owned top-level route.

## Guardrails

- Edit the real header rendered by the public `Layout`; do not introduce a substitute navigation canvas.
- Only a verified Power Admin may read or mutate navigation drafts or create pages. Public, Patient, Moderator and Admin requests must remain mutation-free.
- Keep draft and published navigation separate. A normal navigation save must never alter the logged-out header; publication must be explicit.
- Allow one submenu level only, with bounded root/child counts and stable UUID item keys.
- Accept only allow-listed navigation properties, safe internal website paths and absolute `https://` or `http://` external URLs. Reject scripts, data URLs, credentials, protocol-relative URLs, raw markup, unknown properties and deeper nesting.
- Preserve existing settings that lack the new stable keys/type metadata by normalizing them safely when first opened or saved.
- New page slugs use the exact public grammar: lowercase letters/numbers separated by single hyphens. They must be unique and must not collide with application, authentication, staff, portal, legal, booking, academic, education, preview, API or infrastructure routes.
- Existing CMS pages and published snapshots must remain intact. Template insertion creates ordinary allow-listed CMS sections and uses the shared atomic page document lifecycle.
- Each bounded S4 task is tested, committed, pushed and recorded here before the next task starts.

## Data contracts

### Navigation item

Each root or child item will contain:

- `key`: stable UUID;
- `label`: required plain text, maximum 40 characters;
- `type`: `internal` or `external`;
- `path`: a safe internal path or absolute HTTP(S) URL matching `type`;
- `target`: `_self` or `_blank`, with internal navigation defaulting to `_self`;
- `is_visible`: boolean;
- `children`: root items only, maximum six; child items cannot contain children.

The root navigation remains bounded to eight items. Server validation, not the browser, is authoritative.

### Page creation

The creation request will contain:

- `title`: required plain page name;
- `slug`: optional exact-format slug; when omitted it is safely generated from the title;
- `start_mode`: `blank` or `template`;
- `template`: `standard`, `landing` or `resource` when `start_mode` is `template`.

A blank page starts with no sections. A template starts with a small, safe set of ordinary CMS sections appropriate to the selected template. The response will include the canonical public path so the client can navigate without duplicating routing rules.

## Delivery tasks

### S4.1 — Rendered-header navigation selection and editing

- In Power Admin Edit Mode, outline the real public header navigation and expose a labelled control to select it from its rendered location.
- Load the protected navigation draft for the editor while leaving the published query as the logged-out/public source.
- Render draft items in their actual header positions, including normally hidden items with a clear editor-only hidden state.
- Add an in-context inspector for label, internal/external destination, target and visibility.
- Add root and child items, create/edit one submenu level, move earlier/later, support a dedicated drag handle for root reorder, hide/show and confirmed delete.
- Prevent navigation while an item is being selected or edited, preserve keyboard access, expose accessible names/states and announce changes.
- Provide explicit `Save navigation draft` and `Publish navigation` actions. Failed saves retain local edits; stale or invalid payloads do not change published navigation.
- Add backend schema/security tests and actual-page interaction tests covering VE-158 through VE-167.

### S4.2 — Actual-site new-page workflow

- Add `New page` to the persistent actual-page Edit Mode toolbar.
- Open a focused dialog for page name, automatically generated/editable slug, blank/template choice and template selection.
- Show clear validation for invalid, reserved and duplicate slugs without replacing an existing page.
- Create the private page, invalidate the protected page list and navigate directly to its canonical actual website URL while Edit Mode stays active.
- Ensure an unpublished page can load its protected draft at that URL even though the logged-out public endpoint correctly returns 404.
- Seed template pages through the same allow-listed section structure used by the public renderer; do not invent owner-specific professional or contact claims.
- Allow the new page to use the existing exact preview, Save Draft and Publish controls, then link it from the rendered navigation inspector.
- Add API and frontend journey coverage for VE-170 through VE-178.

### S4.3 — Privacy, publication and continuity evidence

- Exercise rename, internal link, external link, visibility, reorder, addition and one-level submenu through the actual rendered header.
- Save the navigation draft, refresh, and prove logged-out visitors still receive the previous published navigation.
- Publish the draft and prove the logged-out header displays functional internal/external items in the intended order with hidden items absent.
- Create one blank page and one template page; prove exact paths, collision safety, private pre-publication behavior, preview, publication and navigation linking.
- Run the full backend and web suites, production build and `git diff --check`; run schema rollback/reapply only if implementation changes the database.
- Record evidence by VE ID without self-declaring VE-236, VE-242, VE-243, QA-002 or QA-003 complete.

## Test gates

Every S4 task must pass:

- UI-route and direct-API denial for Public, Patient, Moderator and Admin mutation attempts;
- unknown navigation fields, duplicate keys, invalid types/targets, unsafe/mismatched URLs, excessive root/child counts and deeper nesting rejection;
- invalid grammar, empty generated value, reserved slug and case/format collision rejection;
- draft isolation before publish and exact public navigation after publish;
- actual-page keyboard selection, focus visibility, accessible control names, announcements and confirmed destructive actions;
- full `php artisan test`, full `npm test -- --run`, `npm run build` and `git diff --check` at the phase evidence gate.

## Completion rule

S4 implementation is complete only when the rendered header can be selected and its full supported hierarchy edited in place, private navigation survives save/reload without leaking, publication changes the logged-out header atomically, and blank/template pages can be safely created and opened at their actual URLs for preview and publication. The implementation evidence can advance VE-158 through VE-178, but VE-236 and the release/independent-QA rows remain outside this agent's self-approval authority.

## Implementation ledger

### S4.1 — Active

- Planned delivery: rendered-header selection, safe structured navigation schema, root/submenu lifecycle, private save and explicit publication.
- First gate: focused backend allow-list/isolation tests and frontend actual-header interaction coverage before a full phase run.
