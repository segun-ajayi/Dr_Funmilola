# QA-013 evidence

Status: READY FOR RETEST
Implementation revision: `a49c1a9`

- The only generic review transitions are `pending_review -> verified` and `pending_review -> rejected`. A verified claim may be published, and a published claim may only leave public state through the dedicated reasoned retraction endpoint.
- Review, publication and retraction lock the claim and update the claim/target record inside one database transaction. Publish records the target type and ID; retraction updates both records and captures actor, time, reason and a privacy-minimal audit entry.
- Both `/api/public` and `/api/academic/publications` now expose only the single `published` state. A retracted record is removed from both feeds in the same transaction.
- Publication identity is DOI first, PMID second and normalized-title hash otherwise. DOI and PMID are normalized; unique indexes protect identifier and claim ownership. Records without DOI/PMID use separate title identities instead of nullable-DOI `updateOrCreate` behavior.
- Sourced target payloads are validated and allowlisted. They cannot override `source_claim_id`, `identity_key`, `verification_status` or other system-owned publication fields.
- The Power Admin review screen includes Pending, Verified, Rejected, Published and Retracted views. Published items expose a labelled, reason-required retraction form; retracted items display the reason.
- Dedicated backend regression: 9 tests / 89 assertions covering public-feed parity, role denial, every supported/forbidden transition, publish/retract audit and visibility, short reasons, null identifiers, duplicate DOI and normalized title, protected fields, fresh seed and legacy upgrade.
- Web regression: the retraction component tests cover successful reasoned retraction and accessible validation that prevents a short reason from being submitted.

Full suite: 90 backend tests / 631 assertions; 6 web files / 16 tests; formatting, TypeScript and production build passed. SQLite migration rollback/reapply passed. PostgreSQL transaction/unique-index runtime and independent authenticated browser acceptance remain required before PASS.
