# Milestone 4 — Complete parity

Milestone 4 completes the tenant-safe conversion of SMS Moonlight's college, payment, quiz, and configurable modules. The inherited workflows remain familiar, but module boundaries and high-value records are now enforced beyond navigation visibility.

## Configurable modules

The following deployment flags are enforced by request-time middleware:

- `COLLEGE_MODULE_ENABLED`
- `PAYMENTS_MODULE_ENABLED`
- `QUIZ_MODULE_ENABLED`
- `STAFF_MODULE_ENABLED`
- `TEACHER_STAFF_ATTENDANCE_ENABLED`

Disabled modules return `404` from their custom student, teacher, and administrator endpoints. Routes are registered consistently and evaluated at request time, so route caching cannot accidentally expose or remove a module based on the configuration present when the cache was generated.

## College management

- Courses, program classes, schedules, instructors, enrollments, and grades remain isolated in each school's database.
- Enrollments require a valid student, course, school year, semester, year level, and status.
- Program classes require a valid school year, college instructor, and positive capacity.
- Enrolled classes must match the student's course, year level, semester, school year, and available capacity.
- Every college grade is validated in the `0–100` range.
- Final submissions require all grading periods and become immutable at the model boundary, including administrator-side edits.
- College workbook imports are transactional, validate all rows before writing, and accept at most 5,000 data rows.

## Payments

- Payment records require a valid student, optional valid payment type, positive amount, and non-future transaction date.
- Payment type names are normalized and case-insensitively unique.
- Payment CSV exports neutralize spreadsheet formula prefixes in user-controlled cells.
- A non-payment administrator can unlock payment resources only with the configured payment administrator's password.
- Unlocks are bound to the currently authenticated administrator, invalidated when the payment administrator password changes, and expire after `PAYMENTS_UNLOCK_MINUTES` (15 minutes by default).
- Successful unlocks regenerate the session ID, and stored return URLs are restricted to the current host.

`PAYMENTS_ADMIN_USERNAME` must identify a tenant-local administrator whenever payments are enabled.

## Quizzes

- Legacy quiz models now map correctly to their `record_created` and `record_updated` columns.
- Quiz days validate their weekday and duration boundaries.
- A question can appear only once on a quiz day.
- A student can save only one answer for each question on a quiz day.
- Submission requires an answer for every assigned question and rejects answers belonging to another question.
- All answers are written atomically; an invalid or interrupted submission cannot leave a partial attempt.
- A completed quiz cannot be submitted again.
- Administrator question ordering must contain every assigned question exactly once and is updated transactionally.

## Tenant isolation

Provisioning tests now create college programs, classes, enrollments, grades, payments, quiz definitions, and quiz answers in one school and verify that a second school contains none of those records.

## Acceptance tests

Run the focused Milestone 4 suite from `sms_moonlight`:

```powershell
php artisan test tests/Feature/CompleteParityTest.php
php artisan test tests/Feature/CollegeManagementTest.php
php artisan test tests/Feature/CollegeEnrollmentImportTest.php
php artisan test tests/Feature/PaymentsConfigurationTest.php
php artisan test tests/Feature/SchoolProvisioningTest.php
```

The complete Laravel test suite and production frontend build remain the release gates.

## Deferred milestone

- Milestone 5: commercial billing, guided onboarding, support operations, backups, migration, and production rollout.
