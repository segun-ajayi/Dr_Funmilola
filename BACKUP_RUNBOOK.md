# Backup and Restore Runbook

## Production policy

- PostgreSQL: encrypted daily logical backup plus provider-managed point-in-time recovery where available.
- Private patient documents: encrypted, versioned object-storage backup with access logging.
- Application secrets: managed separately; never place `.env` or encryption keys inside database backups.
- Retention: 35 daily, 12 monthly backups unless the approved privacy/records schedule requires otherwise.
- Store a geographically separate copy in an approved region and account.

## Daily checks

1. Confirm the database backup completed and has non-zero expected size.
2. Confirm object-storage replication/versioning health.
3. Confirm encryption and backup-account access policies remain enabled.
4. Alert on missed backup, unusual size change or failed integrity check.

## Monthly restore test

1. Create an isolated, access-restricted recovery environment.
2. Restore PostgreSQL without production application traffic.
3. Run migrations in status-only mode and the automated test smoke subset.
4. Restore a sampled set of encrypted private objects and verify checksums and authorization metadata.
5. Record recovery-point objective, recovery time, operator, result and corrective actions.
6. Destroy the isolated recovery data using the approved disposal procedure.

Never test a restore by overwriting production. A backup is not considered reliable until a restore has succeeded.
