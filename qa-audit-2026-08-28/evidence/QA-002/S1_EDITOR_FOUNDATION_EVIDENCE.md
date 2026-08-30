# S1 — In-Place Editor Document Foundation Evidence

Recorded: 30 August 2026  
Implementation revision: `0c1cd19`  
Status: implemented and automated locally; runtime and independent acceptance remain pending

## Delivered

- The Power Admin enters Edit Mode on the actual public page and receives one persistent toolbar containing Select, Add Section, Undo, Redo, Preview, Save Draft, Publish and Exit Edit Mode.
- The rendered page registers its commands with the toolbar; non-editable public pages expose no active mutation commands.
- Editing state persists in the shared public layout while navigating public routes.
- The current structured page is held as one local document with 50-step session history, Undo/Redo, an unsaved state, saving/saved/failed announcements, keyboard Save/Undo/Redo shortcuts and unload/exit protection.
- Save Draft validates and stores the entire section document atomically under an optimistic `lock_version`; stale sessions receive 409 and cannot silently overwrite another editor.
- Preview stores an immutable, expiring snapshot of the exact current local document, including changes that have not first been saved to the database.
- Publish validates and stores one complete document in a transaction, retains the immediately previous published snapshot, audits the event and returns the same renderer contract.
- A Power-Admin-only rollback endpoint restores a retained published snapshot directly and atomically to both the structured draft and logged-out public result.
- Preview snapshots are schema version 3 and the new snapshot column rolls backward and forward cleanly.

## Atomic checklist coverage established by this slice

These rows have qualifying implementation and automated evidence contributed by S1, but are not declared final PASS without the later required runtime/continuous/independent evidence:

| IDs | S1 evidence |
| --- | --- |
| VE-001–VE-005, VE-011–VE-022 | Actual-page entry, persistent toolbar, safe disabled state and exit handling in `Layout` plus `AdminNavigation.test.tsx` |
| VE-006–VE-010, VE-189–VE-190 | Verified Power Admin middleware and new save/preview/publish/rollback role-denial feature test; the complete VE-236 matrix remains pending |
| VE-179–VE-182, VE-184, VE-188 | Separate published snapshot, private atomic draft and expiring exact-current snapshot preview feature tests |
| VE-191–VE-205 | Transactional publication, previous-version retention, direct rollback, session Undo/Redo, save states, retained failure state and conflict protection foundations; exhaustive type/failure journeys remain S6/S7 |
| VE-215–VE-216, VE-219–VE-220, VE-222–VE-225 | Versioned Page/Section/content props/presentation styles/draft/published structured model with strict allow-listing; responsive and expanded component schemas remain S3 |
| VE-231 | New visual-draft, preview, publish and rollback audit events include page, actor through the audit relation, schema/version context and no secret payload |

## Fresh automated evidence

- `php artisan test` — 110 tests, 854 assertions, PASS.
- `npm test -- --run` — 10 files, 31 tests, PASS.
- `npm run build` — production Vite build, PASS. Existing bundle-size advisory remains non-blocking for this slice.
- `php artisan migrate:rollback --step=1 --force` followed by `php artisan migrate --force` — PASS for `2026_08_30_000001_add_snapshot_to_cms_preview_tokens`.
- `git diff --check` — PASS before commit.

Dedicated regression names:

- `test_visual_document_save_is_atomic_versioned_and_conflict_protected`
- `test_exact_unsaved_preview_publish_previous_version_and_direct_rollback_share_one_snapshot`
- `test_visual_document_preview_publish_and_rollback_are_power_admin_only`
- `public site provides an authenticated Power Admin edit toggle and persistent toolbar`
- `persistent public Edit Mode keeps local history and atomically saves the complete private draft`

## Remaining risk

S1 is foundation, not release closure. The text interaction is still modal-based rather than a cursor in the exact rendered element; component, navigation, media and responsive inspectors are incomplete; direct rollback needs its actual-page UI; failure/accessibility journeys and all VE-232–VE-243 release gates remain pending. QA-002 and QA-003 therefore remain REOPENED.

