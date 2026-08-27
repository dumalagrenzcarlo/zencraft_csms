# ZenCraft CSMS SaaS

ZenCraft CSMS SaaS is the multi-school evolution of SMS Moonlight. It retains the established school-management workflows while adding a central control plane, isolated school databases, domain-based tenant routing, plans, subscriptions, and workspace lifecycle controls.

## Projects

| Project | Description |
| --- | --- |
| [`sms_moonlight`](./sms_moonlight/) | Laravel 12 SaaS control plane and tenant school application. |
| [`sms_pwa`](./sms_pwa/) | Electron attendance scanner, retained for tenant-aware API integration. |

## Milestone 1

The first milestone provides:

- Platform owner and support authentication
- Platform dashboard and school directory
- School provisioning with a dedicated database
- Root, administrator, teacher, and student domains per school
- Plans, subscriptions, trial state, and user-metering fields
- Initial school administrator creation
- Tenant-specific file storage and queue context
- Active/suspended tenant enforcement
- Central audit records for platform login and provisioning
- Automated access, provisioning, and isolation tests

See [`docs/MILESTONE_1.md`](./docs/MILESTONE_1.md) for architecture and setup details.

## Milestone 2

The core school MVP provides tenant-safe workflows for:

- Administrators, teachers, staff, and students
- School years, grade levels, subjects, and classes
- One-class-per-school-year student enrollment
- Adviser-scoped class rosters
- Draft and submitted grades with controlled student release
- Student and class attendance views
- Administrator, teacher, and student portal boundaries
- Database constraints and cross-school isolation for core academic data

See [`docs/MILESTONE_2.md`](./docs/MILESTONE_2.md) for supported workflows and acceptance criteria.

## Milestone 3

Operational parity provides tenant-safe workflows for:

- RFID attendance scanning with durable replay protection
- Private assignment and student-submission files
- Teacher-owned assignment and roster operations
- Guarded student CSV imports and spreadsheet-safe exports
- Tenant-scoped operational and academic reports
- Audience-aware, expiry-aware announcements
- Cross-school isolation for operational records

See [`docs/MILESTONE_3.md`](./docs/MILESTONE_3.md) for security decisions, supported workflows, and acceptance criteria.

## Milestone 4

Complete operational parity provides:

- Tenant-safe college courses, schedules, enrollment, and submitted grades
- Validated payment records and temporary administrator-bound payment access
- Complete, atomic, one-time quiz submissions
- Request-time enforcement for college, payments, quizzes, staff, and staff attendance
- Cross-school isolation for college, payment, and quiz records

See [`docs/MILESTONE_4.md`](./docs/MILESTONE_4.md) for configuration, security decisions, and acceptance criteria.

## Security

Environment files, SQLite databases, tenant databases, logs, dependencies, backups, and build output are excluded from version control. Never commit production credentials or student data.
