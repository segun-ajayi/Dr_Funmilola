# Online Consultations

## Security and privacy boundary

An online consultation belongs to one confirmed online appointment. The patient named on that appointment and active staff roles are the only authorized participants. Consultation endpoints require an active authenticated account and verified email.

The platform stores scheduling and attendance metadata only. It does not record audio/video, expose room locators, or add clinical notes. The provider room locator is encrypted at rest and never returned by list/detail APIs.

## Provider abstraction

`VideoProviderInterface` separates room creation and participant configuration from consultation workflow rules. The default `UnconfiguredVideoProvider` creates only an opaque internal locator and returns no live media credentials. A future Jitsi, Daily, Zoom or other vetted adapter can implement the same contract without changing appointment authorization or consent logic.

`VIDEO_PROVIDER=unconfigured` is the safe default. Selecting a real provider requires a separate security, privacy, data-processing and regional-hosting review.

## Workflow

1. Staff prepares a room for a confirmed online appointment.
2. The patient reviews and accepts consent version `v1`.
3. From 30 minutes before the appointment until 60 minutes after its scheduled end, the patient can enter the waiting room.
4. Staff admits the patient (`ready`), starts the consultation (`in_progress`) and ends it (`ended`).
5. Join authorization creates a signed URL that expires after ten minutes.
6. Opening the signed room endpoint rechecks participant authorization and creates an attendance record. Leaving records `left_at`.

Invalid state changes are rejected server-side and consultation creation/state changes are audited.

## Mobile clients

The waiting-room, consent, join-authorization, room-configuration and leave endpoints are JSON APIs suitable for Android and iOS. Native camera/microphone controls will consume the selected provider's configuration later; no web-only business rules are required.

## Known limitation

This phase intentionally does not provide live video. The safe unconfigured adapter makes the authorization, consent, waiting-room and attendance foundation functional without pretending an unvetted media provider is production-ready.
