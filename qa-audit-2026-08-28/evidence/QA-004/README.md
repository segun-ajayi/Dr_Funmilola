# QA-004 evidence

Status: READY FOR RETEST  
Implementation revision: `adf7156`

- Booking POST now calls `AvailabilityService::isBookableSlot` inside its write transaction.
- The decision enforces active service online eligibility, rule method, weekly rule membership, closures, additional clinics, duration, buffer, future time, exact start cadence, and current conflicts.
- Staff reschedule uses the same decision with the current appointment excluded from conflict checks.
- Regression: generated slot succeeds once; exact repeat, off-cadence time, and unsupported online service/method fail with a safe refresh-and-select validation message.
- Full backend: 64 tests/357 assertions. Targeted scheduling/booking/staff: 13 tests/61 assertions.
- Remaining retest: expand the independent truth table across all closure/leave/additional boundary combinations on PostgreSQL staging.
