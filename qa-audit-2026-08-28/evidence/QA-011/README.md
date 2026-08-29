# QA-011 evidence

Status: READY FOR RETEST
Implementation revision: pending Step 8 implementation commit

- `/contact` is a dedicated public route with two safe journeys: new patients go to appointment booking and existing patients go to authenticated secure portal messaging.
- Contact copy publishes only the approved broad Ile-Ife location; it does not invent a phone, email or exact clinic address. It includes a prominent emergency-use warning.
- The Laravel SPA shell now distinguishes known/static, published CMS, published education and published publication URLs before returning HTML. Unknown, unpublished and retracted dynamic destinations return HTTP 404.
- The React wildcard renders a dedicated 404 page with Home and Contact recovery links; it no longer substitutes the Home CMS page.
- Runtime route sweep: Home, About, Services, Research, Academic, Education, Contact, Book, legal-status routes and sitemap returned 200; `/not-a-real-page` returned 404.
- Regression: `PublicContentRoutingTest` covers every known public route, two arbitrary missing routes, missing CMS/article/publication routes, and published/unpublished transitions.

Full suite: 96 backend tests / 693 assertions; 7 web files / 21 tests; TypeScript and production build passed. Independent browser, responsive and accessibility acceptance remain required before PASS.
