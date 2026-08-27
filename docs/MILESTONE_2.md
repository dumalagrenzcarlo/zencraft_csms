# Milestone 2 — Core school MVP

Milestone 2 turns the inherited SMS Moonlight academic modules into a tenant-safe school MVP. Every record described here lives in the provisioned school's database and is resolved only after the request hostname selects that school.

## Supported workflows

### School administration

- Create and maintain administrators, teachers, staff, and students.
- Configure school years and keep a single school year active.
- Configure grade levels, subjects, classes, advisers, schedules, and class subjects.
- Enroll a student in one high-school class per school year.
- Record and review student attendance.
- Manage grades for the subjects assigned to each class.

### Teacher portal

- Sign in with a tenant-local teacher account.
- View only classes assigned to the authenticated teacher.
- Add active students to an owned, active class.
- View the class roster, student details, grades, and attendance.
- Save grade drafts and submit final grades.
- Grade only enrolled students and subjects assigned to the class.

### Student portal

- Sign in with an active tenant-local student account.
- View the current school year, class, adviser, and subjects.
- View only the authenticated student's attendance history.
- View grades only after the adviser submits them for release.
- Respect an adviser's explicit grade-visibility hold for a student.

## Data integrity and security

- Specialized `admin`, `teacher`, and `student` hostnames cannot serve another portal's paths.
- A class enrollment always inherits its class's school year; a client-supplied year cannot override it.
- Batch enrollment is transactional, preventing partially completed rosters.
- Archived students cannot be added through the teacher portal.
- A student can have only one high-school class in a school year.
- Duplicate class-subject and class-student-subject grade records are blocked by database constraints.
- Grade models validate enrollment, class subjects, the class grade level, and the `0–100` range.
- Draft and hidden grades are excluded from the student dashboard, modal, and PDF download.
- People, school years, classes, enrollment, grades, and attendance remain isolated between school databases.

## Portal URLs

For a tenant slug such as `sample-academy`, the default entry points are:

- `sample-academy.TENANT_BASE_DOMAIN`
- `admin.sample-academy.TENANT_BASE_DOMAIN/admin`
- `teacher.sample-academy.TENANT_BASE_DOMAIN/teacher`
- `student.sample-academy.TENANT_BASE_DOMAIN/student`

The path-prefixed routes also allow approved custom tenant domains to use the same application. A specialized portal hostname rejects paths belonging to a different portal.

## Acceptance tests

Run the focused Milestone 2 suite from `sms_moonlight`:

```powershell
php artisan test tests/Feature/CoreSchoolMvpTest.php
php artisan test tests/Feature/SchoolProvisioningTest.php
php artisan test tests/Feature/AdminResourceWorkflowTest.php
php artisan test tests/Feature/StudentAcademicContextTest.php
```

The complete Laravel suite remains the release gate.

## Deferred milestones

SMS Moonlight's later modules remain in the codebase for compatibility, but they are not part of the Milestone 2 acceptance boundary:

- Milestone 3: scanner operations, assignments, files, imports, exports, reports, and announcements.
- Milestone 4: college, payments, quizzes, and all configurable modules.
- Milestone 5: billing, onboarding, support operations, backups, migration, and production rollout.
