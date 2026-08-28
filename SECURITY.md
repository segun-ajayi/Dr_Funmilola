# Security and Clinical Privacy

- Collect only data necessary for the requested workflow.
- Keep basic account data separate from future clinical records.
- Never expose private patient files through the public web root.
- Enforce ownership and role authorization on every protected endpoint.
- Prevent appointment conflicts on the server inside a database transaction.
- Sanitize CMS rich text and validate file type, size and purpose.
- Log security-sensitive administrative actions without storing secrets or unnecessary clinical text.
- Require HTTPS, secure cookies, rate limiting, environment secrets and encrypted production backups.
- Before clinical launch, complete a privacy impact assessment against applicable Nigerian data-protection and medical-record obligations.
- Browser authentication uses CSRF-protected same-origin sessions; native applications use scoped, expiring, revocable tokens stored in platform-secure storage.
- Unverified accounts cannot read appointment information, including accounts created initially by a guest booking request.

## Response and browser controls

Every Laravel response receives `nosniff`, clickjacking protection, a restrictive same-origin Content Security Policy, referrer policy, cross-origin isolation guidance and a permissions policy. HSTS is emitted only on HTTPS requests so local HTTP development is not poisoned.

## Files

Patient documents are first written under a random quarantine name on the private disk. PDF/JPEG/PNG validation, a 10 MB bound, single-extension safe filename rules, container signatures and `FileScannerInterface` scanning run before release. Only a definitive clean result is moved into the patient-owned private document directory and recorded in the database. Unsafe, unavailable, timed-out or indeterminate scans fail closed and remove the quarantine object. Upload, rejection, scanner failure and authorized download actions use privacy-minimal audit metadata.

`UPLOAD_SCANNER=basic` is limited to isolated local development and automated fixtures. Production defaults to `unconfigured`, which deliberately rejects every upload. A release must bind the interface to an approved ClamAV or managed-scanner adapter and document hosting region, retention, monitoring, incident response and failure-mode test evidence.

## Devices and tokens

Native tokens are named, scoped, expire after 30 days and can be listed/revoked only by their owner. Plain tokens are returned once and never stored. Device-list responses expose metadata, not token hashes. Revocation is audited.

## Audit access

Power Admin-only audit queries are limited to 90-day ranges and 50 records per page. Filters support action prefix, actor and dates. Metadata keys suggesting passwords, tokens, secrets, message bodies or content are stripped from the response.

## Readiness and retention

`/api/ready` reports only generic database/private-storage readiness. `practice:prune-expired` is dry-run by default and supports only expired preview tokens, expired mobile tokens and read notifications older than one year. Destructive execution requires `--execute`; audit history and patient documents are not part of automated pruning.
