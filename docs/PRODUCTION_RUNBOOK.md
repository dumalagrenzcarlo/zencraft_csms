# Production runbook

## Deploy

1. Put the application in maintenance mode and snapshot the central database.
2. Install locked dependencies and build assets.
3. Run `php artisan migrate --force` for the central control plane.
4. Run `php artisan tenants:migrate --force` for tenant schemas.
5. Cache production configuration and routes.
6. Restart queue workers and the PHP application service.
7. Leave maintenance mode, check `/up` and `/health/ready`, then run `php artisan saas:readiness`.

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan down
php artisan migrate --force
php artisan tenants:migrate --force
php artisan optimize
php artisan queue:restart
php artisan up
php artisan saas:readiness
```

## Required processes

- Run a durable queue worker for the configured queue connection.
- Run `php artisan schedule:run` every minute. The scheduler reconciles subscription expirations hourly and creates and verifies all tenant backups daily at 02:00.
- Store `SAAS_BACKUP_DISK` on infrastructure independent of the application host in production, normally an encrypted private S3 bucket with versioning and lifecycle retention.
- Alert on failed scheduler/queue jobs, HTTP 503 from `/health/ready`, and any `failed` or `verification_failed` tenant backup.

## Migration procedure

1. Provision the destination school and keep it suspended.
2. Generate and save the source record inventory.
3. Use the guarded student/college import workflows and migrate files into tenant-private storage.
4. Generate the destination inventory and reconcile every dataset.
5. Complete the onboarding checklist and create a verified backup.
6. Activate billing, reactivate the school, and execute portal acceptance checks.

## Recovery

The `.jsonl.gz` format contains a versioned manifest, table markers, and one JSON record per row. Always verify an archive before recovery. Restore into a new isolated database, reconcile counts, and switch tenant database routing only after application acceptance testing. Never restore over the live tenant database.

## Rollback

Application releases should be immutable. Roll back to the previous release artifact, restart workers, and use migration rollback only when the migration is explicitly documented as reversible. For any data-impacting release, restore into a new database from the pre-deploy snapshot and validate before switching traffic.
