# QA-003 evidence

Status: **READY FOR RETEST**

Implemented revision: `b8bbdeb` (with expanded menu controls in `bec0619`).

- `Layout` requests only `/api/cms/public-settings`, applies published palette/density/heading classes, and renders published navigation with visibility and one-level submenu support.
- The Power Admin editor exposes a live theme preview plus separate **Save private draft** and **Save & publish** actions.
- Seeded defaults are idempotent and preserve existing settings/pages.
- Backend regression proves a navigation/theme draft is visible to the protected settings endpoint but the public endpoint retains the earlier published values until explicit publication.
- Rendered web regression proves custom published labels replace the hard-coded menu and `plum`, `compact`, and `modern` classes reach the public site shell.
- Authenticated Chromium rendered the navigation/theme form with six current navigation entries, live preview and both draft/publish actions.

Independent public-browser and accessibility retest remains required before PASS.
