# Milestone 5 — Commercial launch

Milestone 5 turns the parity-complete school system into an operable SaaS product. It adds enforceable subscription states, guided onboarding, auditable support access, verified tenant backups, migration reconciliation, and production health checks.

## Delivered capabilities

- Trial expiry, active, past-due grace, cancellation, plan change, and billable-user synchronization
- Owner-only billing and school suspension/reactivation controls
- Eight-step school onboarding readiness checklist
- Time-limited, reason-required support access grants with immediate revocation
- Tenant-scoped support dashboards and school access
- Platform audit log for provisioning and commercial operations
- Portable gzip JSONL tenant backups with SHA-256 and manifest verification
- Daily scheduled verified backups at 02:00
- Migration inventory counts for legacy-data reconciliation
- Platform readiness endpoint and launch-readiness command

## Operator commands

```bash
php artisan saas:backup --verify
php artisan saas:backup sample-academy --verify
php artisan saas:backup:verify 42
php artisan saas:migration:inventory sample-academy
php artisan saas:readiness
php artisan saas:subscriptions:reconcile
```

The readiness command requires complete onboarding, a usable subscription, and a verified backup no older than two days for every selected school.

## Acceptance criteria

- Expired trials and subscriptions outside their grace period cannot enter tenant portals.
- Support users cannot see or open a school without an active grant.
- Only platform owners can provision, bill, suspend, or grant support access.
- Every backup is recorded centrally and can be independently checksum/manifest verified.
- Operators can compare key dataset counts before and after a migration.
- `/health/ready` returns HTTP 503 when the central database or writable runtime storage is unavailable.
