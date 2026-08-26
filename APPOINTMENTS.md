# Appointment Operations

## Status workflow

The backend owns valid transitions. Clients cannot set arbitrary statuses.

- Requested → Pending Confirmation, Confirmed, Cancelled
- Pending Confirmation → Confirmed, Cancelled, Rescheduled
- Confirmed → Checked In, Cancelled, Rescheduled, No Show
- Checked In → In Progress, Cancelled, No Show
- In Progress → Completed
- Completed, Cancelled, Rescheduled and No Show are terminal

Confirmations, cancellations, transitions and rescheduling are audited. Rescheduling is limited to pending/confirmed appointments, requires a future timestamp and repeats server-side overlap checks while excluding the appointment being moved.

## Staff calendar

Calendar queries require `from` and `to` dates and are limited to 62 days per request. This supports day, week, month and agenda interfaces without unbounded patient-data downloads. Events include only the patient contact details needed for practice operations.

## Availability

Availability rules accept ISO weekday, start/end time, supported slot length, buffer, consultation method and active state. End time must follow start time. Changes are audited. Future tasks will add leave, holidays, blocked periods and recurring exceptions.

## Staff workspace

Admin, Moderator and Power Admin roles share the protected operations endpoints. The dashboard exposes today's schedule, pending requests, online consultations and invited-patient counts. Patient search deliberately omits clinical details and returns only identity, contact, registration and upcoming-schedule fields required for daily operations.
