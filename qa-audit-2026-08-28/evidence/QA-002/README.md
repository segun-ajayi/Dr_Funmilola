# QA-002 evidence

Status: **BLOCKED ON APPROVED PUBLIC-MEDIA PIPELINE AND INDEPENDENT ACCEPTANCE**

Implemented revisions: `b8bbdeb`, `bec0619`, `d905416`.

## Implemented locally

- Edit Mode persists while a Power Admin navigates public routes and loads the protected draft without exposing it to ordinary visitors.
- Sections are visibly selectable; double-clicking eyebrow, heading or paragraph text opens a labelled inline draft editor.
- Selected text supports validated, non-overlapping bold, italic, underline and safe-link ranges. Raw HTML is never stored or rendered.
- Section-level font family, size, weight, emphasis, colour, line height, alignment, width, background and spacing controls are allowlisted.
- Hero/CTA buttons expose label, safe URL, style, icon and visibility controls.
- Image sections expose approved URL, alternative text, optional safe link, width, side, fit/crop focus and border-radius controls.
- Navigation supports rename, add, delete, reorder, hide/show, safe destinations and one accessible submenu level.
- Pages support create, rename, slug, SEO fields, draft, preview, publish, unpublish, duplicate, version history and restore.
- Sections support add, remove, duplicate, reorder, hide/show and presentation controls.
- Stale page-detail and inline-section updates receive HTTP 409 rather than silently overwriting a newer draft.

## Verification

- Backend: full suite passed, 86 tests / 560 assertions. CMS feature coverage includes role denial, draft isolation, expiring preview, publish/unpublish, duplicate, restore, conflict, XSS/unsafe URL/schema validation, navigation nesting and all structured section types.
- Web: TypeScript passed; 5 files / 14 tests passed. Rendered coverage includes persistent Edit Mode, section selection, double-click inline editing, selected-text formatting, private draft save, theme/navigation rendering and editor controls.
- Production build: JS 440.49 KB / 133.84 KB gzip; CSS 281.67 KB / 40.88 KB gzip.
- Migration: `2026_08_28_000003_extend_cms_page_lifecycle` applied, rolled back and reapplied successfully.
- Authenticated Chromium: public About page displayed persistent Edit Mode, selectable outlines and a labelled inline body-text dialog; Cancel was exercised without changing content. The full editor rendered the page picker, structured fields, presentation controls and version history.

## Remaining blocker

Managed image upload/library/replacement cannot be safely enabled until QA-022 has an approved production malware scanner plus public-media hosting, retention, transformation and monitoring governance. The editor therefore accepts only an already-approved public image URL and clearly labels upload/crop as unavailable. Physical-device, screen-reader, tablet/mobile and independent-browser acceptance also remains outstanding. This finding must not be declared PASS from local evidence alone.
