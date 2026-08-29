# QA-014 evidence

Status: READY FOR RETEST
Implementation revision: pending Step 7 implementation commit

- `DatabaseSeeder` no longer inserts the six title-only `Publication` rows. It creates fully sourced `ResearchClaim` queue entries only, with source title, authoritative PubMed URL and complete target metadata.
- Rerunning the seeder uses `firstOrCreate`, so it cannot reset a previously reviewed, rejected, published or retracted claim back to pending.
- The six seed claims use PubMed records 37769219, 41538750, 41985118, 41759050, 39052945 and 39522636, with DOI/PMID/title/journal/date/category/link metadata retained for review.
- The reviewed upgrade migration removes the known incomplete pending title-only rows, normalizes identifiers, merges legacy identity duplicates while preserving the strongest state and missing metadata, links already-published claims to retained records, and then installs uniqueness constraints.
- Fresh-seed regression proves there are zero direct publication rows, exactly six publication queue claims, every queue claim has source title/URL/DOI/PMID/payload, and a second seed preserves an existing reviewed state.
- Upgrade regression rolls the lifecycle migration back, inserts duplicate legacy DOI rows plus the obsolete title-only shape, reapplies the migration, and proves one complete published record remains with the incomplete seed removed.
- The live local database migration and rollback/reapply succeeded. After the updated seed, it holds six sourced publication claims: two existing reviewed/published records remain linked and four remain pending for Power Admin review.

Full-suite totals and the final revision are recorded in `../../REMEDIATION_PROGRESS.md`. Owner/content approval of pending claims, PostgreSQL migration rehearsal and independent retest remain required before PASS.
