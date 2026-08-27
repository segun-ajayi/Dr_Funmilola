# Advanced Scheduling and Reminders

## Schedule model

Weekly availability rules remain the normal clinic template. Staff can layer two exception types over that template:

- `closed`: blocks overlapping slots for leave, closures or unavailable periods;
- `additional`: creates extra appointment windows for an additional clinic.

Exceptions are stored as UTC timestamps and displayed in `Africa/Lagos`. A single exception is limited to 31 days. Public slot generation applies closures before returning slots, deduplicates additional windows and always performs the central appointment conflict check.

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
