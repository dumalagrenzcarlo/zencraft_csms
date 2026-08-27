# Milestone 3 — Operational parity

Milestone 3 hardens SMS Moonlight's day-to-day school operations for the multi-tenant SaaS architecture. Scanner events, assignments, submissions, files, imports, exports, reports, and announcements are resolved within the active school's database and storage context.

## Attendance scanner

- The Electron scanner authenticates with the `X-API-AUTHCODE` header and no longer places credentials in URLs.
- API endpoints are rate limited and return explicit JSON authentication responses.
- Each locally queued scan carries a stable event ID. The tenant database enforces uniqueness so retrying an event cannot create another attendance record, even after the short duplicate-scan window.
- Batch and direct scans reject implausibly old or future dates.
- Student and personnel scans may record a later attendance event after the ten-minute duplicate window.
- Downloaded directory archives use tenant storage and temporary archives are removed after delivery.

Existing scanner installations may continue using the legacy query token during transition, but new clients use the header.

The scanner's legacy Electron runtime still requires a planned major upgrade. Its compatible dependency patches are locked, but completing the runtime upgrade requires a Windows C++ build environment to rebuild and package the native SQLite driver. This is a deployment prerequisite before a production scanner release, not a server-side Milestone 3 blocker.

## Assignments and files

- Teachers can create and send assignments only for assignment-enabled classes they advise.
- Students can view and submit only assignments for their enrollment, and submissions close at the deadline.
- Assignment and submission files are stored on the tenant's private disk.
- Downloads pass through authenticated, ownership-aware controllers; operational files cannot be fetched through the generic public upload route.
- Replacing or deleting a record also cleans up the corresponding private file without deleting a valid previous upload when a database update fails.
- Teacher roster archive actions are restricted to owned classes and use the same student archival service as administration workflows.

Legacy assignment files already stored on the tenant public disk remain downloadable through the authenticated controllers to support migration.

## Imports, exports, reports, and announcements

- Student CSV imports are transactional, limited to 5,000 data rows, reject duplicate or oversized LRNs, and cannot replace an existing non-student account.
- Synthetic student email domains come from application configuration rather than runtime environment reads.
- CSV exports neutralize spreadsheet formula prefixes in user-controlled cells.
- Existing tenant-scoped attendance, academic, student, adviser, staff, and college report workflows remain available.
- Announcement queries share active-date and audience scopes, and invalid audiences are rejected at the model boundary.

## Tenant isolation

The school provisioning acceptance test now proves that attendance event IDs, assignments, submissions, and announcements created in one school are absent from another school's database. Private file paths are additionally rooted by the active tenant's filesystem bootstrapper.

## Acceptance tests

Run the focused Milestone 3 checks from `sms_moonlight`:

```powershell
php artisan test tests/Feature/OperationalParityTest.php
php artisan test tests/Feature/RfidAttendanceTest.php
php artisan test tests/Feature/SchoolProvisioningTest.php
```

Run the scanner checks from `sms_pwa`:

```powershell
npm test
```

The complete Laravel test suite and production frontend build remain release gates.

## Deferred milestones

- Milestone 4: college, payments, quizzes, and all configurable modules.
- Milestone 5: billing, onboarding, support operations, backups, migration, and production rollout.
