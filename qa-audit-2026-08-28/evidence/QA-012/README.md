# QA-012 evidence

Status: BLOCKED ON CONTENT-OWNER / INSTITUTIONAL APPROVAL
Implementation revision: pending Step 8 implementation commit

- Services now render all active cards from the same authoritative service catalogue used by booking. A service link carries its slug into booking and preselects the matching active service.
- Education cards are links to published article detail routes. Details show author, medical reviewer/date, safe plain-text content and the required medical disclaimer. Draft articles return 404 in both web and API journeys.
- Academic portfolio now supports search, category, newest/oldest/title sorting, pagination, result count, publication details, authors, journal/date, DOI and authoritative-source links.
- Publication detail routes exist only for the single `published` lifecycle state. Verified, pending, rejected and retracted records stay unavailable.
- About and Academic display only published career/achievement records with their source links, plus an honest empty state when no entry has completed review.
- Approved navigation now includes Contact and a Research submenu for Academic portfolio and Patient education; the footer also links all destinations.
- Automated web regressions exercise Contact, 404, Education list-to-detail, Academic sort/detail/DOI/source and publication canonical metadata.

The technical journeys are implemented, but current professional employment/title, public contact details, pending career/achievement claims, four pending publications and owner-approved education copy cannot be promoted by implementation judgment. Those approval gates keep QA-012 BLOCKED rather than self-declared READY/PASS.
