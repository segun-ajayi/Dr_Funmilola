# QA-001 evidence

Status: READY FOR RETEST  
Implementation revision: `cf396fd`

## Change

- Public published snapshots moved from `/api/cms/pages/{slug}` to the unambiguous `/api/content/pages/{slug}` contract.
- Protected CMS page and nested section/version routes now require numeric model identifiers.
- The public renderer uses the new content endpoint; preview remains `/api/cms/preview/{token}`.

## Automated evidence

- `CmsTest::test_public_slug_and_protected_numeric_page_routes_are_unambiguous`
  - unpublished public slug: 404;
  - Power Admin numeric page and editable section: 200;
  - Patient, Moderator, Admin numeric page: 403;
  - missing protected numeric ID and missing public slug: safe 404.
- `CmsTest::test_real_backend_editor_journey_selects_edits_previews_publishes_and_reads_public_page`
  - create/list/select numeric page;
  - add and update draft section;
  - preview edited draft;
  - prove draft is not public;
  - publish and verify the public content endpoint.
- Targeted CMS regression: 9 tests, 59 assertions passed.
- Full backend regression: 62 tests, 349 assertions passed.
- Web: strict typecheck, 7 tests, and production build passed. Bundle remained 405.45 KB JS (124.93 KB gzip) and 268.23 KB CSS (38.27 KB gzip).

## Runtime evidence

- Laravel route inventory lists one public `api/content/pages/{slug}` route and 12 protected `api/cms/pages` operations, with numeric constraints applied to protected model identifiers.
- In-app Chromium rendered `http://127.0.0.1:8000/` with heading “Care that sees the whole woman, not only the diagnosis.” and no console errors after the frontend endpoint change.

## Remaining risk

The available browser session was not authenticated. The implementation agent did not enter a password without action-time confirmation, so an independent authenticated visual select/edit/preview/publish retest is still required. This finding is not marked PASS by the implementation agent.
