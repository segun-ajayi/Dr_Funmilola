# Content Research and Verification

## Publishing rule

External research is seed material, not permission to publish. Every researched claim enters the Research & Verification Queue as `pending_review`. Only a Power Admin can mark it Verified or Rejected. A second explicit Publish action maps a verified claim into a supported structured record.

Queue states:

- `pending_review`: source captured, not approved;
- `verified`: Power Admin checked the source;
- `rejected`: unsuitable, incorrect or insufficiently supported;
- `published`: promoted into an approved publication, career or achievement record.

Review and publishing actions record reviewer identity, time and an audit event. Public APIs query only verified and published records.

## Authoritative seed sources reviewed on 27 August 2026

- ORCID `0000-0003-3315-990X`: identifies Funmilola Olanike Wuraola and lists employment at Obafemi Awolowo University as Lecturer/Consultant General Surgeon in Surgery. <https://orcid.org/0000-0003-3315-990X>
- Princess Margaret Global Cancer Program: identifies Dr. Funmi Wuraola as a Breast Surgical Oncology Fellow at the University of Toronto and describes a Nigeria breast-conserving-surgery project. <https://www.uhn.ca/PrincessMargaret/Global_Cancer_Program>
- PubMed PMID `37769219`: bibliographic record for “Clinicopathologic Profile and Psychosocial Experiences of Nigerian Breast Cancer Survivors,” DOI `10.1200/GO.23.00022`. <https://pubmed.ncbi.nlm.nih.gov/37769219/>
- PubMed PMID `41538750`: bibliographic record for “Pilot Health Care Provider Education Program for BRCA1/2 Genetic Testing, Counseling, and Management in Nigeria,” DOI `10.1200/GO-25-00306`. <https://pubmed.ncbi.nlm.nih.gov/41538750/>

These records are seeded with high source confidence but remain Pending Review. Confidence describes source quality; it does not bypass Power Admin approval.

## Structured destinations

Supported publishing mappings are `publication`, `career` and `achievement`. Payloads are created by trusted seed/import code and reviewed before promotion. Arbitrary target types and arbitrary public HTML are rejected.

## Education safety

Education articles cannot publish without title, summary, plain-text content, named author, named medical reviewer, review date, updated date, category and a substantive medical disclaimer. Articles remain private drafts until explicit publication.

## Public privacy

Practice contacts expose only records marked public. Exact private clinic information remains outside the public profile and is shared operationally with confirmed patients.
