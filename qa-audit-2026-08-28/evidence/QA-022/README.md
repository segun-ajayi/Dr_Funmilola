# QA-022 evidence

Status: BLOCKED ON APPROVED PRODUCTION SCANNER AND GOVERNANCE EVIDENCE  
Implementation revision: `c306f15`

## Implemented boundary

- Uploads first enter a non-public, UUID-named quarantine path on the private disk.
- Validation permits PDF/JPG/JPEG/PNG up to 10 MB, requires a safe single-extension filename, and verifies PDF/JPEG/PNG container signatures.
- `FileScannerInterface` scans the quarantined filesystem object rather than an unpersisted request object.
- Only a definitive clean result is moved into the patient-owned private document directory and recorded in `patient_documents`.
- Unsafe, unavailable, timed-out/indeterminate, release-failed and database-failed paths remove temporary/released objects and fail closed.
- Successful upload, scanner rejection, scanner failure and authorized download have privacy-minimal audit events.
- Production configuration defaults to `UPLOAD_SCANNER=unconfigured`, which rejects every upload. The basic signature scanner is documented as local/test-only.

## Automated evidence

- Clean PDF, JPEG and PNG are quarantined, scanned, released privately and audited.
- Malformed allowed containers, unsupported extensions, double extensions and oversized files fail.
- The benign EICAR test signature fails and leaves no quarantine object or metadata.
- Simulated scanner/provider outage fails closed and leaves no file or metadata.
- Direct storage-path request does not expose file bytes.
- Patient B receives 403 for Patient A's file; authorized staff download succeeds and is audited.
- Targeted upload/security/portal suite: 17 tests, 89 assertions.
- Full backend: 71 tests, 396 assertions. Web typecheck and 7 tests passed.

## Remaining release blocker

No approved ClamAV/managed-scanner adapter, hosting-region/privacy approval, provider credentials, monitoring, incident-response integration or staging scanner failure-mode evidence is available. Local signature tests do not establish production malware protection; QA-022 remains BLOCKED and is not marked READY or PASS.
