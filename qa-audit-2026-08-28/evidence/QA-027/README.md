# QA-027 evidence

Status: BLOCKED ON OWNER / LEGAL APPROVAL
Implementation revision: `ee80e16`

- Privacy, Terms and Accessibility now have real routes and real footer links, eliminating dead footer text.
- Each route explicitly states that the document is not yet published because owner/legal approval is required. The site does not present model-written policy as approved governance.
- All three status routes are `noindex, nofollow`, omit canonical tags and are excluded from the sitemap until approved content is supplied.
- The routes direct new care requests to booking and private communication to authenticated portal messaging without inventing public contact details.
- Route and metadata regressions prove the pages return 200, footer destinations exist and approval-pending routes cannot be indexed as final legal policy.

Actual approved Privacy, Terms and Accessibility text plus accountable owner/legal sign-off are external inputs. QA-027 therefore remains BLOCKED; technical route/link/indexing infrastructure is ready for the approved documents.
