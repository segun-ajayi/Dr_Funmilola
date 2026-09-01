# S5 — Persistent Media and In-Place Images Build Plan

Recorded: 1 September 2026
Status: active source of truth; S5.1 complete, S5.2 active
Acceptance scope: VE-048 through VE-065 and VE-206 through VE-214, with shared validation evidence for VE-225 and VE-226 and final uninterrupted image acceptance reserved for VE-233 and VE-236

## Objective

Replace raw image-URL editing with a persistent, searchable and reusable media library, then make the exact image rendered on the real website selectable and editable in Power Admin Edit Mode. Every upload must fail closed unless its type, size, image container and malware scan pass. Draft image choices and presentation remain private until the page is explicitly published.

## Existing foundation and gaps

- The public/editor renderer already supports Image, Text + Image and Gallery components plus background-image presentation, but these currently store raw URL strings and expose no qualifying exact-image inspector.
- Existing image presentation has coarse width, alignment, object-fit, radius and top/centre/bottom crop-position values. It lacks explicit rendered-image selection, height, opacity, overlay and durable asset references.
- The application already has a quarantine-first scanner contract and fail-closed document upload implementation. It can be reused at the scanner boundary, but CMS media requires its own model, image-only validation, storage namespace, audit events, safe delivery and reference rules.
- No CMS media table, browsing/search API, reusable selection dialog, metadata workflow or reference-safe deletion/archive behavior exists.
- Existing published content that contains allow-listed legacy image URLs must keep rendering during migration; all new media-library choices will persist stable asset identifiers rather than transient upload paths.

## Guardrails

- Only an active, verified Power Admin may browse, search, upload, edit, archive or otherwise mutate CMS media. Public, Patient, Moderator and Admin direct requests must be denied without file or metadata mutation.
- Accept raster images only: JPEG, PNG and WebP, with server-authoritative MIME/container inspection, safe single-extension names, a 10 MB maximum and bounded pixel dimensions. SVG, HTML, scripts, polyglots, executables and indeterminate scans fail closed.
- Store uploads in quarantine first. Release a file only after the configured `FileScannerInterface` returns a definitive safe result; remove quarantine/released files if any later database operation fails.
- Use opaque UUID public identifiers. Do not expose filesystem paths, scanner details or an enumerable library endpoint to logged-out visitors.
- A stable media delivery URL may serve a released asset only to a verified Power Admin or when the asset is referenced by a current published page snapshot. Private drafts must not make an otherwise unreferenced asset public.
- Persist a durable reference (`media_id`) in component content/presentation. Resolve delivery URLs at render time; never make a temporary upload URL the source of truth.
- Alternative text is required for informative images. An explicit `is_decorative` state is required to permit empty alt text. Decorative and alternative-text state must be mutually consistent and validated again during page save, preview and publication.
- Archived or unsafe media cannot be newly selected or published. An asset referenced by any current draft, published snapshot or retained version cannot be destructively deleted. Archiving must not break historical/public content.
- Preserve the shared structured-document, lock-version, exact-preview, atomic-publication and rollback lifecycle. Media changes must participate in Undo/Redo without duplicating or deleting the underlying asset.
- Keep the inspector on the actual rendered website. Do not satisfy the task with the older dashboard form or a substitute media canvas.
- Each bounded S5 task is tested, committed, pushed and recorded here before the next task starts.

## Data contract

### Media asset

The persistent record will contain:

- opaque `public_id` UUID and private storage path;
- safe original filename, detected MIME type, extension and byte size;
- intrinsic pixel width and height plus a SHA-256 checksum;
- plain-text title, alternative text and caption metadata;
- explicit `is_decorative` and `is_archived` state;
- uploader identity and timestamps.

Only definitively scanned files receive a released asset record. Scanner failure/rejection leaves neither a reusable record nor a retained file. API responses omit storage paths and return only safe metadata plus the stable delivery URL.

### Image reference and presentation

Image-capable component content will support:

- `image_media_id`: stable media UUID;
- `image_alt`: page-specific accessible alternative text, or empty only with `image_is_decorative: true`;
- `image_is_decorative`: explicit boolean;
- `caption`: optional page-specific plain text;
- `image_link`: optional safe internal or absolute HTTP(S) destination.

Structured presentation will support bounded values for crop/focal position, width, height, alignment, object fit, radius, overlay colour/opacity and image opacity. Gallery item images and section background images use the same persistent reference and publication guard where applicable. Legacy allow-listed image URLs remain readable but are not offered as the primary new editing workflow.

## Delivery tasks

### S5.1 — Persistent secure media foundation

- Add the media migration, model, factory/service and relationships needed for stable, searchable records.
- Add Power-Admin-only list/search, upload, metadata update and archive endpoints with pagination and bounded queries.
- Implement quarantine, image container/dimension validation, configured malware scanning, atomic release, cleanup and privacy-minimal audit events.
- Implement a stable media delivery endpoint that authorizes draft-only assets and permits logged-out delivery only for assets referenced by a current published snapshot.
- Implement reference discovery across current sections, published snapshots and retained versions; prevent destructive removal and prevent archived/invalid assets from new publication.
- Extend CMS section validation to accept stable media references, explicit decorative state and the bounded image presentation schema. Page preview and publication must revalidate references server-side.
- Add migration, authorization, malicious/unavailable-scanner, validation, privacy, search/reuse, metadata and reference-safety regressions for VE-206 through VE-214.

### S5.2 — Actual-page image selection and inspector

- Make each exact rendered image in Image, Text + Image and Gallery components keyboard/click selectable without following its optional link.
- Open a labelled in-context image inspector with a visible Page › Section › Component › Image hierarchy.
- Add `Replace image` from the persistent library and `Upload image` through the same library; retain the inspector and file selection after recoverable failure.
- Provide searchable/browsable asset cards with preview, filename/title, dimensions, type, size, alt/decorative state and a clear reuse action.
- Provide live exact-image controls for alternative/decorative state, caption, crop/focal position, width, height, alignment, object fit, radius, link, overlay and opacity.
- Apply every change immediately at the selected rendered image, with accessible names/states, visible focus, Escape/close behavior and status/error announcements.
- Use the same selection path for standalone, split-layout and nested Gallery images; support section background image selection without regressing the section inspector.
- Add actual-page interaction regressions for VE-048 through VE-062.

### S5.3 — Draft, publication, rollback and continuity evidence

- Upload and select one new image, reuse it in another image location without duplicate upload, edit metadata and apply page-specific alt/decorative and presentation values.
- Exercise failure recovery for unsafe type, malformed image, oversized/dimension-bomb candidate, unavailable scanner, unauthorized role and referenced-asset archive/delete attempt.
- Save the page draft, fully reload and prove the exact media references, accessible text and image presentation survive while logged-out visitors retain the previous public result.
- Generate exact preview, publish atomically and prove the logged-out page receives the new safe asset and correct alt/decorative behavior; prove an unreferenced private asset remains unavailable.
- Publish a later image state, roll back and prove the earlier public media reference remains deliverable and the logged-out page returns to the previous exact state.
- Run full backend and web suites, production build, `git diff --check`, and a fresh migration rollback/reapply because S5 changes the database.
- Record evidence by VE ID without self-declaring VE-233, VE-236, VE-242, VE-243, QA-002 or QA-003 complete.

## Test gates

Every S5 task must pass:

- UI-route and direct-API denial for Public, Patient, Moderator and Admin media entry/mutation attempts;
- unsupported extension/MIME, forged MIME, malformed image, unsafe signature, oversized byte/pixel count, unsafe filename and scanner-outage failure with no retained file/record;
- metadata plain-text bounds, explicit alt/decorative consistency, unknown properties, unknown/archived asset references and unsafe image links rejected;
- media search/browse pagination and reuse without duplicate storage;
- private asset delivery denial before publication and safe public delivery only while referenced by a current published snapshot;
- exact rendered-image selection and live control behavior with keyboard, focus, labelled state, announcements and recoverable errors;
- draft save/reload, exact preview, explicit publish, previous-version retention and rollback with reference continuity;
- full `php artisan test`, full `npm test -- --run`, `npm run build`, `git diff --check` and migration rollback/reapply at the phase evidence gate.

## Completion rule

S5 implementation is complete only when a Power Admin can upload or reuse a definitively scanned persistent asset, select the exact image where it appears, edit its complete supported accessible/presentation state live, save and reload the private draft, preview it exactly, publish it atomically and retain safe historical references through rollback. Automated implementation evidence may advance VE-048 through VE-065 and VE-206 through VE-214, but the uninterrupted image journey, role journey and independent release rows remain outside this agent's self-approval authority.

## Implementation ledger

### S5.1 — Complete

- Revision: `7b23d96`.
- Persistent library: `cms_media_assets` stores opaque UUID identity, private storage, detected type, byte/pixel dimensions, checksum, searchable title/alt/caption metadata, explicit decorative state, archive state, uploader and timestamps. API resources omit paths and checksums.
- Upload safety: Power-Admin-only JPEG, PNG and WebP uploads use safe filenames, a 10 MB limit, server-detected image containers, 12,000-pixel/40-megapixel bounds, quarantine-first configured malware scanning and atomic release. Rejection, indeterminate scanning, release failure or record failure leaves no reusable record or retained file.
- Access and reuse: protected browse/search returns paginated safe metadata and stable delivery URLs; metadata can be maintained; unreferenced assets can be archived without deleting the file. Public, Patient, Moderator, Admin, unverified Power Admin and inactive Power Admin access is denied without mutation.
- Delivery and references: a private draft asset is available to a verified active Power Admin but returns 404 to logged-out visitors. Logged-out delivery begins only when a current published snapshot references the opaque asset. Current sections, published snapshots and retained versions are counted; referenced assets cannot be archived, and archived/missing assets cannot be newly saved, previewed or published.
- Structured document: Image, Text + Image, Gallery and section-background schemas accept stable media identifiers; informative images require alt text while decorative images require an explicit boolean and empty alt. Width, height, alignment, fit, radius, nine-position crop/focus, overlay and opacity values are server allow-listed. Legacy safe URLs remain readable for continuity.
- Fresh gate: focused media 8 tests / 100 assertions; backend 123 tests / 1,137 assertions; web 10 files / 39 tests; production build PASS; `git diff --check` PASS. Fresh isolated SQLite migration, full rollback and reapply PASS. The known non-blocking bundle-size warning remains.
- Automated evidence contributed: VE-206 through VE-214 backend foundations plus shared VE-065, VE-225 and VE-226 validation/publication guards. Exact rendered-image interaction and uninterrupted image acceptance remain S5.2/S5.3 and independent QA.
- Next task: S5.2 exact rendered-image selection, media library/upload dialog and live contextual image inspector.
