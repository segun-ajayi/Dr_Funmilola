# Advanced Scheduling and Reminders

## Schedule model

Weekly availability rules remain the normal clinic template. Staff can layer two exception types over that template:

- `closed`: blocks overlapping slots for leave, closures or unavailable periods;
- `additional`: creates extra appointment windows for an additional clinic.

Exceptions are stored as UTC timestamps and displayed in `Africa/Lagos`. A single exception is limited to 31 days. Public slot generation applies closures before returning slots, deduplicates additional windows and always performs the central appointment conflict check.

`slot_minutes` is the configured cadence between candidate start times. The effective cadence is never shorter than the service duration plus the rule buffer, so appointments cannot overlap their required cleanup/preparation time. Additional-clinic exceptions have no separate cadence field and therefore advance by the selected service duration. Booking submission and staff rescheduling re-run this same slot decision inside the scheduling transaction; client-supplied times are never treated as authoritative.

On PostgreSQL, each create or reschedule transaction takes a date-scoped advisory lock before re-checking the slot and writing the appointment. This serializes competing schedule changes for that clinic date and closes the check-then-insert race. SQLite remains suitable for ordinary local tests, but release evidence must include the dedicated simultaneous PostgreSQL integration test.

## Calendar

The staff calendar uses the bounded `/api/staff/calendar` feed for day, week and month views. API ranges remain limited to 62 days. Schedule-exception endpoints require Admin, Moderator or Power Admin and all create/delete actions are audited.

## Reminders

`appointments:send-reminders` runs every 15 minutes through Laravel's scheduler. It creates 24-hour and 2-hour reminders for confirmed future appointments. A unique database constraint makes each appointment/type/channel combination idempotent.

- In-app reminders are written immediately to the patient's secure notification inbox.
- Email reminders are queued through Laravel's mail notification channel.
- Mobile push remains disabled until the Android and iOS clients and device-token lifecycle are implemented.

Production must run both `php artisan schedule:work` (or an equivalent cron entry) and a supervised queue worker. Redis is the intended production queue; local development may use the database queue.

`notification_deliveries` records whether each reminder was delivered in-app or queued for email without copying medical details into delivery logs. Failed-job monitoring and provider callbacks will be completed during deployment hardening.

## Provider-neutral consultations

Online appointments continue to store only the consultation method. Meeting-provider selection, secure link creation and waiting-room behavior remain deliberately deferred to the consultation milestone, preventing vendor-specific data from entering the scheduling domain prematurely.
