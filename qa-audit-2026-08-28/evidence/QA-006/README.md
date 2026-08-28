# QA-006 evidence

Status: READY FOR RETEST  
Implementation revision: `adf7156`

- `slot_minutes` is documented and implemented as the configured cadence between candidate start times.
- Effective cadence is `max(slot_minutes, service duration + buffer)`, preventing configuration from creating starts inside the required service/buffer interval.
- Additional-clinic exceptions advance by service duration because their schema has no separate cadence field.
- Regression `test_slot_minutes_is_the_minimum_start_cadence_with_duration_and_buffer_floor` proves a 90-minute cadence produces 9:00, 10:30, and 12:00 starts for a 30-minute service with 10-minute buffer.
- Full backend: 64 tests/357 assertions.
