# Production Deployment

## Images

Build immutable images from one commit and registry-tag them with the commit SHA:

```text
docker build --target runtime -t registry.example/practice-app:<sha> .
docker build --target web -t registry.example/practice-web:<sha> .
```

The runtime image contains PHP-FPM, PostgreSQL/Redis extensions, optimized Composer dependencies and built frontend assets. The web image contains Nginx configuration and the same built public assets.

## Required external controls

- TLS terminates at a trusted load balancer/reverse proxy in front of port 8080.
- Secrets come from a deployment secret manager or protected files, never Git.
- Restrict PostgreSQL and Redis to the internal network.
- Bind production `FileScannerInterface` to approved malware scanning.
- Configure transactional email, encrypted offsite backups, monitoring and alert recipients.

## First deployment

1. Copy `.env.production.example` to a protected `.env.production` and replace every placeholder.
2. Create `secrets/postgres_password.txt` outside version control with restrictive permissions.
3. Set `APP_IMAGE`, `WEB_IMAGE`, `POSTGRES_DB`, `POSTGRES_USER` and `REDIS_PASSWORD` in the deployment environment.
4. Start PostgreSQL and Redis; verify health.
5. Run the migration as a one-off release task: `docker compose -f compose.production.yml run --rm app php artisan migrate --force`.
6. Start app, worker, scheduler and web services.
7. Run cache warmup: `php artisan config:cache`, `route:cache`, and `view:cache` inside the app release task.
8. Validate `/api/ready`, homepage, queue processing, scheduler output and one controlled non-clinical notification.

Do not run the demonstration seeder in production.

## Routine release order

1. Confirm current backup and latest successful restore-test date.
2. Pull immutable app/web images.
3. Enable maintenance mode, allowing only the readiness network if configured.
4. Run backward-compatible migrations once.
5. Warm caches and restart queue workers with `queue:restart`.
6. Replace app/web/worker/scheduler containers.
7. Confirm readiness and smoke tests, then disable maintenance mode.
8. Monitor errors, latency, queue age and database load for at least 30 minutes.

## Rollback

Application rollback: redeploy the previous immutable app and web image when database changes are backward compatible.

Database rollback: stop and review. Do not automatically run `migrate:rollback` on patient data. Determine whether the migration is reversible, take a fresh forensic backup, and prefer a forward corrective migration. Restore a database backup only under the restore runbook with an explicit recovery-point decision.

## Monitoring

Alert on readiness failure, HTTP 5xx/error-rate increase, queue failures or oldest-job age, missing scheduler heartbeat, PostgreSQL connection/capacity thresholds, Redis memory/evictions, private-storage capacity, repeated authentication failures, unusual staff exports/access, backup age and malware-scanner failure.

Central logs must redact secrets and avoid document/message bodies. Retain access/audit logs according to the approved privacy schedule.
