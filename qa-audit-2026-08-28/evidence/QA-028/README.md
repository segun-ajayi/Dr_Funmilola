# QA-028 evidence

Status: BLOCKED ON APPROVED PRODUCTION SCANNER/GOVERNANCE AND INDEPENDENT RESPONSIVE/DEVICE ACCEPTANCE
Implementation revision: `5fa3156`

## Implemented boundary

- The booking form accepts an optional PDF, JPG, JPEG or PNG up to 10 MB and remains usable without a file.
- Booking and patient-portal uploads use the same `SecureDocumentService`: safe single-extension filename, private UUID quarantine object, container/signature scan, definitive-clean release, and privacy-minimal audit metadata.
- A clean booking attachment is owned by the booking patient and linked to the appointment inside the booking transaction.
- Rejected/indeterminate scans remove quarantine data and roll back appointment, patient-document and newly created patient records.
- Browser-generated UUIDs are stored under a unique booking request identifier. A same-request retry returns the original private reference and does not create another appointment or file.
- Owners and authorized staff can download through the protected route; another patient is denied. The private storage path is not returned.
- The UI has associated name/phone/email/reason/file labels, explicit type/size/privacy help, selected-file status, an announced sending/scanning state, safe validation errors and an attachment success message.
- Abandoned forms send no upload. Accepted files remain patient-owned documents if an appointment link is later removed and are excluded from automated pruning pending the approved clinical-record retention process.

## Automated evidence

- Booking with no attachment: 201, one appointment, zero documents.
- Clean attachment: quarantine emptied, private release exists, patient and appointment ownership match, upload audited.
- Unsafe signature: 422 on `attachment`, no appointment/document/file remains.
- Scanner outage: fail closed with no records/files; retry after recovery succeeds once.
- Unsupported and oversized attachments: rejected before appointment creation.
- Duplicate retry: original reference returned, exactly one appointment and one document.
- Authorization: verified owner and Moderator download; another patient receives 403.
- Dedicated backend suite: 6 tests, 41 assertions.
- Full backend suite: 77 tests, 437 assertions.
- Web: TypeScript passed; 4 test files/8 tests passed; production build passed (JS 407.57 KB/125.60 KB gzip, CSS 269.18 KB/38.47 KB gzip).
- Fresh SQLite migration/seed, latest migration rollback, and re-migration passed.

## Runtime evidence and remaining blocker

The rebuilt booking journey rendered in the in-app Chromium browser with the desktop shell, date/time step and privacy panel intact. The component-level journey exercised the attachment control and request/success states. No independent mobile-width browser run, physical iOS/Android acceptance, Safari/Firefox/Edge matrix, or assistive-technology acceptance is available.

Production defaults to the fail-closed `unconfigured` scanner. There is no approved ClamAV/managed-scanner adapter, hosting-region/privacy/retention approval, monitoring, incident response integration, or staging failure-mode evidence. QA-028 therefore remains BLOCKED and is not marked READY or PASS.
