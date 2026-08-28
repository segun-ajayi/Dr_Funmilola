# QA-005 evidence

Status: BLOCKED ON REQUIRED POSTGRESQL RUNTIME EVIDENCE  
Implementation revision: `adf7156`

- Every booking create and staff reschedule transaction calls `AvailabilityService::lockSchedule` before re-evaluating availability and writing.
- PostgreSQL takes a transaction-scoped advisory lock derived from the Africa/Lagos clinic date. Competing writes for the same clinic date serialize, then the loser re-checks current conflicts and fails safely.
- SQLite retains ordinary functional regression only; it is not accepted as concurrency proof.
- Required remaining evidence: repeated true simultaneous create/create and create/reschedule tests on PostgreSQL 17 proving exactly one overlapping change commits. Port 5432 is unavailable and the local Docker engine is stopped, so this finding is not marked READY or PASS.
