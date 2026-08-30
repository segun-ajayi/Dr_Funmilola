# QA-024 evidence

Status: READY FOR RETEST — implementation `d90a2af`.

- Auth reset, portal sign-out/messages/documents/profile/reminders, staff appointment/search/calendar/education and patient/staff consultation mutations now expose pending, success and recoverable error states.
- Form data and selected values are cleared only after server success; failure copy explicitly tells the user that their input remains available for retry.
- Mutation buttons are disabled only while their own request is pending and become available again after failure.
- `PortalAppointments.test.tsx` forces a message failure, verifies the alert, preserved subject/body and enabled retry action.

The independent retest must still force 422/403/404/409/429/500 and network interruption across the complete mutation catalogue before PASS.
