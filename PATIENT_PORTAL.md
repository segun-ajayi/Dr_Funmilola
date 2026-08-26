# Patient Portal and Practice Inbox

## Scope

This milestone provides care-coordination tools, not a hospital electronic medical record. It stores contact preferences, appointment information, patient-supplied documents, administrative cancellation requests, secure practice messages and in-app notifications. It does not store diagnoses, clinical notes, prescriptions or results as structured clinical data.

## Access and privacy

- Patient endpoints require an active authenticated account, verified email and the Patient role.
- Every appointment, document and message-thread operation is scoped to the signed-in patient on the server.
- Staff inbox and patient-context endpoints require Admin, Moderator or Power Admin.
- Uploads accept PDF, JPEG and PNG files up to 10 MB and are stored on Laravel's private local disk.
- Storage paths are never returned by the API. Downloads pass through an authorized controller.
- Profile changes, cancellation requests and staff cancellation decisions are audited.

## Cancellation workflow

A patient creates a pending request. The appointment remains unchanged until staff approves it. Approval uses the central appointment workflow service, changes the appointment to Cancelled and notifies the patient. A declined request leaves the appointment unchanged.

## Messaging and notifications

Patients own message threads and may reply only to their own threads. Staff can review and reply through the practice inbox. New threads, replies and cancellation updates create Laravel database notifications. The notification boundary is ready for queued mail and mobile push channels in later phases.

## Mobile contract

All workflows use JSON endpoints under `/api` and server-side authorization. Android and iOS clients can reuse the same Sanctum identity, profile, documents, messaging, notification and appointment rules without recreating business logic.
