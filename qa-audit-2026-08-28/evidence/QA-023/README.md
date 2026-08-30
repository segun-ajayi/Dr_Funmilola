# QA-023 evidence

Status: READY FOR RETEST (not self-declared PASS).

Implementation revision: `db49100`

- `docs/AUDIT_EVENT_CATALOGUE.md` defines required identity, care, publishing, query, redaction and retention boundaries.
- Centralized new-event recording recursively rejects password, token, secret, body, content and payload keys.
- Account changes record changed field names plus safe before/after values only.
- Account invitation/update, password reset, mobile/API revocation and browser-session revocation are covered.
- Audit access remains Power Admin-only, paginated and newest-first.
- Action-prefix, actor and date filters pass; date ranges over 90 days are rejected.
- API output applies recursive defense-in-depth redaction before returning metadata.

Backend authorization/filter/redaction/change regressions and the rendered filter/pagination viewer test pass. Independent privacy review and production retention approval remain required.
