# QA-025 evidence

Status: READY FOR RETEST
Implementation revision: `ee80e16`

- The server-rendered SPA shell emits route-specific title, description, robots, canonical, Open Graph title/description/type/url for static routes, CMS pages, education details and publication details.
- Published CMS SEO fields populate both public snapshots and server metadata. A reviewed migration fills missing metadata on the four core pages without replacing existing custom values.
- React updates the same metadata on client-side navigation, including CMS-driven title/description and detail pages.
- Auth, portal, staff, preview and approval-pending legal routes are `noindex`; true 404 responses are `noindex` and deliberately have no canonical.
- `/sitemap.xml` contains indexable static routes plus published CMS pages, education articles and publications. Draft, unpublished, verified-only and retracted records are excluded. Legal status pages are excluded until approval.
- `/robots.txt` points to the absolute sitemap URL.
- Backend regressions assert server HTML metadata/canonical/noindex rules, sitemap inclusion/exclusion and robots. Web regressions assert description, Open Graph, canonical and noindex updates.

Full suite: 96 backend tests / 693 assertions; 7 web files / 21 tests; TypeScript and production build passed. Independent crawler/search-console and production-domain acceptance remain required before PASS.
