# Content Verification Findings

Audit date: 28 August 2026  
Method: public-page review, seed-data review and targeted checks against primary/authoritative sources. No correction was invented and no CMS content was published.

## Overall result

No clearly fabricated public claim was found in the live seeded public pages. The application is appropriately cautious about unverified qualifications. The sampled publication titles and identifiers are supported by PubMed/PMC/ASCO sources, and the Toronto/Princess Margaret fellowship statement is supported by UHN.

The main content risks are governance and completeness rather than proven fabrication:

- Six title-only Publication records bypass the Research & Verification Queue (QA-014).
- Publication/claim status transitions can diverge and rejected material can remain public (QA-013).
- Career, achievements, contact, publication detail/DOI links and education detail journeys are incomplete (QA-012).
- Current employment/title claims still need an approved current institutional source before being promoted beyond cautious wording.

## Claim review

| Claim | Location | Problem | Reason for concern | Source required | Severity |
| --- | --- | --- | --- | --- | --- |
| Name: Dr. Funmilola Olanike Wuraola | Site-wide/profile seed | Supported | Name matches ORCID and publications | Retain ORCID plus institutional identity approval | Info |
| ORCID 0000-0003-3315-990X | /api/public and Research seed | Supported | ORCID resolves to the named researcher; ORCID is self-asserted by design | ORCID plus institutional corroboration for employment claims | Info |
| Surgeon/academic clinician associated with OAU/OAUTHC | /about and fallback profile | Supported but current wording should be approved | Multiple PubMed affiliations support OAU/OAUTHC; current appointment/title is time-sensitive | Current OAU/OAUTHC directory, HR/department confirmation or approved profile | Medium |
| Lecturer / Consultant General Surgeon | Fallback About and queued Career claim | Not publicly expanded; current status not independently institutionally confirmed in audit | ORCID employment is useful but self-asserted and may change | Current OAU/OAUTHC source | Medium |
| Breast Surgical Oncology Fellow at University of Toronto / Princess Margaret | ResearchClaim seed | Supported as a dated 2021 claim | UHN Global Cancer Program identifies Dr. Funmi Wuraola as a Breast Surgical Oncology Fellow and describes the Nigeria project | UHN source already retained; preserve year/context | Info |
| CARTA fellowship | Required audit subject | No application claim found; no authoritative result located in targeted official-domain search | Should not be added based on absence of evidence | CARTA or awarding-institution primary source | Medium if proposed for publication |
| Breast Oncology Surgeon & Academic Clinician | Global profile/title | Plausible but broad professional-title copy needs explicit approval | Fellowship/publication evidence supports domain involvement, but current licensure/credential phrasing is sensitive | Current institutional/credential approval | Medium |
| Clinicopathologic Profile and Psychosocial Experiences of Nigerian Breast Cancer Survivors | Seeder/queued publication | Supported | PubMed PMID 37769219; DOI 10.1200/GO.23.00022; author/affiliation match | PubMed record retained | Info |
| Pilot Health Care Provider Education Program for BRCA1/2 Genetic Testing, Counseling, and Management in Nigeria | Seeder/queued publication | Supported | PubMed PMID 41538750; DOI 10.1200/GO-25-00306 | PubMed record retained | Info |
| Oncoplastic Breast-Conserving Surgery in African Women: A Systematic Review | Direct pending Publication row | Supported, but row lacks metadata/source | PubMed PMID 39052945; DOI 10.1200/GO.23.00460 | Add full PubMed metadata through queue | Medium |
| Prospects for Population-Based Breast Cancer Screening in Nigeria: An Integrative Literature Review | Direct pending Publication row | Supported, but row lacks metadata/source | ASCO DOI 10.1200/GO-25-00027 | Add full primary-source metadata through queue | Medium |
| The Out-of-Pocket Cost of Breast Cancer Care in Nigeria: A Prospective Analysis | Direct pending Publication row | Supported, but row lacks metadata/source | PMC article and Journal of Cancer Policy DOI 10.1016/j.jcpo.2024.100518 | Add full primary-source metadata through queue | Medium |
| Barriers to Breast Reconstruction After Mastectomy for Breast Cancer Management in Nigeria | Direct pending Publication row | Supported, but row lacks metadata/source | PubMed PMID 41985118; DOI 10.1200/GO-25-00636 | Add full PubMed metadata through queue | Medium |
| Practice location in Ile-Ife, Nigeria | Public site/footer | Broadly supported but exact clinic withheld | Affiliations support Ile-Ife; exact patient-facing location intentionally shared after confirmation | Approved practice contact/address and privacy decision | Low |

## Authoritative sources reviewed

- [ORCID record 0000-0003-3315-990X](https://orcid.org/0000-0003-3315-990X)
- [UHN Princess Margaret Global Cancer Program](https://www.uhn.ca/PrincessMargaret/Global_Cancer_Program)
- [PubMed PMID 37769219](https://pubmed.ncbi.nlm.nih.gov/37769219/)
- [PubMed PMID 41538750](https://pubmed.ncbi.nlm.nih.gov/41538750/)
- [PubMed PMID 39052945](https://pubmed.ncbi.nlm.nih.gov/39052945/)
- [ASCO DOI 10.1200/GO-25-00027](https://ascopubs.org/doi/10.1200/GO-25-00027)
- [PMC: Out-of-Pocket Cost of Breast Cancer Care](https://pmc.ncbi.nlm.nih.gov/articles/PMC12067558/)
- [PubMed PMID 41985118](https://pubmed.ncbi.nlm.nih.gov/41985118/)

Targeted official-domain searches for a direct OAU/OAUTHC staff profile and CARTA record returned no result during this audit. That is not evidence that the appointment/fellowship is false; it means institutional confirmation should be obtained before publication.

## Publication-system defects

### QA-013 — High — Lifecycle divergence

- Any claim, even status=published, can be changed to rejected through decide.
- Rejection does not retract the Publication/Career/Achievement record created earlier.
- Publication publish uses updateOrCreate on doi, including null; DOI is nullable and not unique.
- Publishing writes Publication verification_status=verified.
- /api/academic/publications queries verified, while /api/public queries published, so public feeds disagree.

### QA-014 — Medium — Seed bypass

DatabaseSeeder inserts six Publication rows directly with title/category/pending_review only. They do not retain source URL, full authors, journal, DOI/PMID or a ResearchClaim link. Two of the same titles also have properly sourced ResearchClaim records; publishing can create a second complete row rather than converting a governed claim into the original record.

## Public content completeness

- /about uses cautious verification copy but is not a complete professional profile.
- /academic provides search and category filter but no visible sort, pagination, detail, DOI or external-source links.
- /education lists reviewed cards but does not link to article detail despite the detail API existing.
- /services CMS output is a summary and omits the six active service cards available in /api/public.
- /contact does not exist; career and achievements have no complete public experience.
- Footer Privacy/Terms/Accessibility text has no destination.

## Content approval gates

1. Obtain current, approved institutional confirmation for present employment/title and patient-facing practice/contact details.
2. Preserve all dated claims with their date/context; do not present the 2021 fellowship as a current post.
3. Route every publication/career/achievement seed through the verification queue with immutable source metadata.
4. Fix publish/reject/retract state integrity before any content release.
5. Populate complete authors, journal, date, DOI/PMID and source link from primary records.
6. Add content-owner/clinical/legal approval for About, Services, Education, Contact, Privacy and Terms.
7. Run duplicate/title/DOI/link checks after migration and again immediately before publication.
