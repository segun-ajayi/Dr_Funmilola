# Safe Visual CMS

## Purpose

The CMS lets a Power Admin create and publish supported website pages without editing source code. It is a structured page builder, not a general code editor.

## Safety model

- Only the Power Admin role can access editor APIs.
- Supported sections are Hero, Text, Call to Action, Statistics and Image with Text.
- Each section accepts an exact allowlist of fields. Text must be plain text; HTML tags are rejected.
- Links must be internal paths or HTTP(S) URLs.
- Presentation choices use bounded presets for background, alignment, width and spacing.
- JavaScript, arbitrary HTML/CSS, PHP, SQL and executable templates are never accepted.
- Protected application paths such as `/portal`, `/staff`, `/api` and authentication routes cannot become CMS slugs.

## Publishing workflow

1. Open `/staff/cms` as Power Admin.
2. Create or choose a page.
3. Add supported sections and edit their labelled fields.
4. Reorder sections with the up/down controls.
5. Preview. The preview link contains a random token, expires after one hour and does not publish the draft.
6. Publish explicitly. The public page becomes available at `/p/{slug}`.

Draft editing changes the page status to Draft but does not alter the last published snapshot. This means a published page remains stable while new work is reviewed.

## Versions and rollback

Page creation, detail changes, section changes, reorder, publish and restore create immutable JSON snapshots. Restoring a version first saves the current draft, loads the selected snapshot into a new draft and records another version. History is never deleted by rollback.

All significant CMS actions are also written to the audit log.

## Navigation and theme

Navigation supports up to eight safe internal links. Theme settings use preset palette, density and heading-style values. Draft settings require a separate publish action before public clients receive them.

## Mobile impact

Published CMS data is structured JSON, so Android and iOS can render approved content without interpreting arbitrary HTML. The web editor stacks into a touch-friendly single-column layout on small screens.
