# Milestone 1 — SaaS foundation

## Architecture

The central database stores platform users, schools, domains, plans, subscriptions, and audit records. Each school receives a separate database containing the complete SMS Moonlight schema. Tenant selection happens before portal authentication by resolving the request hostname against the central domains table.

Default domains for a workspace slug such as `sample-academy` are:

- `sample-academy.localhost`
- `admin.sample-academy.localhost`
- `teacher.sample-academy.localhost`
- `student.sample-academy.localhost`

The base domain is controlled by `TENANT_BASE_DOMAIN`. The platform control plane is restricted to `PLATFORM_DOMAIN`.

## Local setup

From `sms_moonlight`:

```powershell
composer install
Copy-Item .env.example .env
New-Item -ItemType File database/database.sqlite
php artisan key:generate
```

Set a strong `PLATFORM_OWNER_PASSWORD` in `.env`, then run:

```powershell
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8089
```

Open `http://localhost:8089/platform/login`. Modern browsers resolve `*.localhost` to the local machine, allowing provisioned tenant domains to work without a hosts-file change.

## Production requirements

- Configure a dedicated central database and database credentials capable of provisioning tenant databases.
- Point wildcard DNS for `*.TENANT_BASE_DOMAIN` to the application.
- Terminate TLS with a wildcard certificate and add certificates for approved custom domains.
- Use Redis for production cache isolation before enabling application caching.
- Run provisioning jobs through a monitored queue rather than synchronously.
- Store tenant uploads in isolated object-storage prefixes or buckets.
- Back up and restore central and tenant databases independently.

## Verification

```powershell
php artisan test tests/Feature/PlatformAccessTest.php
php artisan test tests/Feature/SchoolProvisioningTest.php
```

The provisioning suite verifies that two schools cannot see each other's settings or administrators.
