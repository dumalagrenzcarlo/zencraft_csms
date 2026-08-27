# School Management System — Complete Test Plan

**Project:** SMS Moonlight  
**Document type:** Manual QA, system test, and user acceptance test plan  
**Prepared:** July 29, 2026  
**Application:** Laravel 12 / MoonShine 4 web application  
**Primary timezone:** Asia/Manila  
**Execution status:** Not started

---

## 1. Purpose

This plan verifies that the School Management System is safe and ready for release. It covers:

- Admin, teacher/instructor, and student portals
- High school and college academic workflows
- Student, teacher, instructor, and staff records
- Grades, schedules, assignments, announcements, payments, quizzes, and attendance
- QR/RFID scanner APIs and offline synchronization
- Imports, exports, PDFs, CSVs, file uploads, and downloads
- Permissions, data isolation, feature switches, security, usability, compatibility, performance, backup, and recovery

Use this document as both the test script and execution record. For every case, record **Pass**, **Fail**, **Blocked**, or **Not Applicable**, plus evidence or a defect ID.

## 2. Release decision

The release may proceed only when:

- Every P0 test passes.
- Every applicable P1 test passes, or a documented workaround and release approval exists.
- There are no open Critical or High defects.
- The automated test suite passes.
- All migrations are applied successfully to a staging copy of production data.
- Backup and restore have been demonstrated.
- The school’s authorized representative signs the UAT section.

## 3. Important prerequisite found during plan preparation

At the time this plan was written, this migration was pending on the current local database:

`2026_07_29_000001_add_elementary_fields_enabled_setting.php`

Before executing tests against that database:

1. Back up the database and uploaded files.
2. Run `php artisan migrate:status`.
3. Apply pending migrations in staging with `php artisan migrate --force`.
4. Run `php artisan optimize:clear`.
5. Confirm the application loads before continuing.

Do not apply an untested migration directly to the only production database.

## 4. Scope and configuration matrix

The application has configuration switches that materially change its behavior. Run the core suite once with the intended production configuration, then run the indicated switch tests.

| Area | Control | Current code default | Required coverage |
|---|---|---:|---|
| College | `COLLEGE_MODULE_ENABLED` | On | On and Off |
| Quiz | `QUIZ_MODULE_ENABLED` | Off | On if school will use it; otherwise verify Off |
| Staff | `STAFF_MODULE_ENABLED` | On | On and Off |
| Staff attendance | `TEACHER_STAFF_ATTENDANCE_ENABLED` | On | On and Off |
| Payments | `PAYMENTS_MODULE_ENABLED` | Off | On if school will use it; otherwise verify Off |
| Easter eggs | `EASTER_EGGS_ENABLED` | On | Intended production value |
| QR | `qr_code_enabled` database setting | On | On and Off |
| RFID | `rfid_enabled` database setting | On | On and Off |
| T-shirt size | `tshirt_size_enabled` database setting | On | On and Off |
| Elementary fields | `elementary_fields_enabled` database setting | On | On and Off |

### Out of scope

- Hardware electrical reliability of third-party RFID/QR scanners
- Browser or operating-system defects outside the supported matrix
- Penetration testing by a certified external security assessor
- Mail/SMS delivery, unless a provider is configured for the release

## 5. Test environment

### Required staging setup

- A staging URL using HTTPS and the same portal/domain arrangement as production
- A sanitized copy of representative school data
- A separate test database and separate uploaded-file storage
- Queue worker running if production uses asynchronous queues
- Correct `Asia/Manila` timezone in PHP, Laravel, database, and server
- Debug mode disabled for release testing
- Browser developer tools available for console/network evidence
- Access to application and server logs
- A QR scanner or scanner app
- An RFID reader configured to type or send the same value expected in production

### Supported client matrix

Run P0 and P1 cases on:

| Device | Browser | Minimum execution |
|---|---|---|
| Windows desktop/laptop | Current Chrome | Full suite |
| Windows desktop/laptop | Current Edge | P0 + visual/print/download tests |
| Android phone/tablet | Current Chrome | Portal P0 + responsive tests |
| iPhone/iPad, if used by the school | Current Safari | Portal P0 + responsive tests |

Test at 1366×768, 1920×1080, and a mobile width near 390 px. Test both light and dark themes where available.

## 6. Roles and test data

Create dedicated accounts; do not use real user passwords.

| ID | Persona/data | Required state |
|---|---|---|
| A1 | Authorized payment admin | Username matches `PAYMENTS_ADMIN_USERNAME` |
| A2 | Other admin | Valid admin, not the configured payment admin |
| A3 | Invalid/locked-out user | No admin rights |
| T1 | High school teacher | Owns Class H1 in active school year |
| T2 | High school teacher | Owns Class H2; must not access H1 |
| I1 | College instructor | Assigned to one college course class |
| S1 | Active high school student | Enrolled in H1, portal access active |
| S2 | Other high school student | Enrolled in H2 |
| S3 | College student | One active, consistent college enrollment |
| S4 | Inactive student account | Portal access inactive |
| S5 | Conflict student | Only for negative test: conflicting active academic context |
| ST1 | Non-teaching staff | Has shift start/end and RFID card |
| H1 | High school class | Subjects, start/end time, 4 grading periods, assignments enabled |
| H2 | High school class | Different teacher; 2 grading periods |
| C1 | College program | Courses across multiple year levels and semesters |
| SY1 | Active school year | Current testing year |
| SY0 | Previous school year | Historical grades and enrollment |

Prepare:

- Valid and invalid student import workbooks
- PDF, DOC, DOCX, JPG, PNG, EXE, and files just below/above 20 MB
- QR identifiers and RFID cards for S1, T1/I1, and ST1
- One unassigned RFID card and one duplicate card value
- Attendance records before, at, and after class/shift start
- Assignment states: draft, sent, submitted, and overdue
- Announcements for admin, teacher, student, and all audiences; active and expired
- Payment types and payments on date-range boundaries
- Quiz data only if the quiz module will be enabled

## 7. Priority, severity, and evidence

### Priority

- **P0:** Release-blocking smoke, security, data integrity, backup/restore
- **P1:** Core daily workflow
- **P2:** Secondary, optional, visual, or uncommon workflow

### Defect severity

| Severity | Definition |
|---|---|
| Critical | Data loss/corruption, authentication bypass, cross-user data exposure, system unavailable |
| High | Core workflow cannot finish; incorrect grades, payments, attendance, or enrollment |
| Medium | Feature has a workaround; wrong validation, export, layout, or non-core result |
| Low | Cosmetic or wording issue with no material workflow impact |

For failures capture: case ID, date/time, tester, environment, account, exact steps, expected/actual result, screenshot/video, downloaded file, browser console/network details, relevant log excerpt, and defect ID.

## 8. Entry criteria

- [ ] Staging backup is complete and restorable.
- [ ] All intended migrations are applied.
- [ ] `php artisan migrate:status` shows no pending migrations.
- [ ] Production-intended environment switches are documented.
- [ ] Test accounts and data above exist.
- [ ] HTTPS, domains, storage link/file serving, queue, and scheduled tasks are configured.
- [ ] Automated tests and asset build can run.
- [ ] Critical application logs are empty before execution.

## 9. P0 release smoke test

Run this after every deployment before the wider suite.

| ID | Test | Steps | Expected result | Status / evidence |
|---|---|---|---|---|
| SMK-01 P0 | Home and portal entry | Open `/`, `/admin`, `/teacher`, `/student`. | Each page loads or redirects to its correct login; no 500, debug page, or mixed portal. | |
| SMK-02 P0 | Admin login | Sign in as A1 and open Dashboard. | Login succeeds; dashboard widgets render without error. | |
| SMK-03 P0 | Teacher login | Sign in as T1 and open every dashboard tab. | Only T1’s current classes/data appear. | |
| SMK-04 P0 | Student login | Sign in as S1 and open dashboard/profile. | Only S1’s information appears. | |
| SMK-05 P0 | Core record read/write | As admin create a temporary announcement, edit it, then delete it. | Each operation succeeds and list/detail pages stay consistent. | |
| SMK-06 P0 | Grade workflow | T1 saves a grade for S1; S1 views it when not hidden. | Saved value is exact and visible only to the correct student. | |
| SMK-07 P0 | Assignment workflow | T1 creates/sends an assignment; S1 downloads and submits it; T1 opens summary. | Files and state flow correctly end to end. | |
| SMK-08 P0 | Attendance | Scan S1 using the enabled QR/RFID path and open student attendance. | One valid attendance event is stored and reported under S1. | |
| SMK-09 P0 | Export | Export students and one grades PDF. | Download succeeds; file opens and contains the filtered data. | |
| SMK-10 P0 | Access isolation | While signed in as S1/T2, request a known S2/T1-owned object URL. | Server denies it (404/403/redirect); no foreign data is exposed. | |
| SMK-11 P0 | Logout/session | Log out of all three portals, then revisit a protected URL. | Session is invalidated and protected content is inaccessible. | |
| SMK-12 P0 | Logs | Review application/server logs after smoke tests. | No new unhandled exceptions, SQL errors, or sensitive values. | |

## 10. Installation, deployment, and configuration

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| DEP-01 | P0 | On a clean staging database run migrations and seed only approved baseline data. | All migrations complete in order; required defaults exist; no demo data leaks into production. | |
| DEP-02 | P0 | Run migrations against a restored copy of the previous database. | Upgrade preserves users, enrollments, grades, payments, attendance, and uploads. | |
| DEP-03 | P1 | Run `composer install --no-dev --optimize-autoloader` and `npm ci && npm run build`. | Commands finish successfully and versioned assets load. | |
| DEP-04 | P0 | Set `APP_DEBUG=false`, trigger a controlled 404 and server error in staging. | Friendly error shown; stack trace, SQL, paths, secrets, and environment values are not exposed. | |
| DEP-05 | P1 | Verify app, PHP, database, and displayed date/time around midnight Manila time. | Dates, deadlines, attendance day, and greeting use Asia/Manila consistently. | |
| DEP-06 | P1 | Restart web server/PHP and queue worker, then clear caches. | Application returns normally and queued work is not lost or duplicated. | |
| DEP-07 | P1 | Verify public storage and `/uploaded-files/...` for valid files; try missing and `../` traversal paths. | Valid files load; missing/traversal paths return 404 and cannot read outside approved storage. | |
| DEP-08 | P1 | Test intended admin/teacher/student domains in production-mode staging. | Host detection and redirects stay within the correct portal and use HTTPS. | |

## 11. Authentication, authorization, and session security

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| AUTH-01 | P0 | Attempt each portal with empty, wrong, and valid credentials. | Invalid input shows a generic error; valid matching role succeeds. | |
| AUTH-02 | P0 | Use student credentials on admin and teacher login; teacher credentials on admin/student login. | All cross-role attempts fail without revealing whether username exists. | |
| AUTH-03 | P0 | Log in as S1, then request admin and teacher protected URLs. Repeat for T1 against admin/student. | Middleware blocks access and invalid mixed-role sessions do not cross portals. | |
| AUTH-04 | P1 | Create/import a student and create a teacher/instructor. Sign in with role default password. | Account is created with the displayed username and forced password change. | |
| AUTH-05 | P0 | While `must_change_password` is true, request dashboard/profile directly. | User is kept on change-password flow until password is changed. | |
| AUTH-06 | P1 | Change password with wrong current password, fewer than 8 characters, mismatch, then valid values. | Invalid changes are rejected; valid change succeeds and old password stops working. | |
| AUTH-07 | P1 | Admin resets a student, teacher, and admin password. | Correct role-specific default is applied and user is forced to change it where intended. | |
| AUTH-08 | P0 | Deactivate S1’s `StudentAccess`, then log in/revisit dashboard. | Access is denied even if credentials are correct or an old session exists. | |
| AUTH-09 | P1 | Log out, use Back, refresh, and paste protected URL. | Protected content is not available from the server; session cookies are invalidated. | |
| AUTH-10 | P1 | Leave sessions idle for configured lifetime and test “remember me” only if offered. | Expiry matches configuration; no unintended indefinite session. | |
| AUTH-11 | P0 | Submit state-changing forms with missing/altered CSRF token. | Request is rejected and data is unchanged. | |
| AUTH-12 | P0 | As T2 manipulate class, student, grade, assignment, submission, and export IDs belonging to T1. | Every read/write/download is denied; no enumeration data leaks. | |
| AUTH-13 | P0 | As S1 manipulate grade, assignment, submission, and class-student IDs belonging to S2. | Every read/download/write is denied. | |
| AUTH-14 | P1 | Rapidly submit wrong payment-unlock password until throttle applies. | Attempts are rate-limited and correct feedback is shown without exposing the configured password. | |
| AUTH-15 | P1 | Inspect cookies in HTTPS staging. | Session cookie has Secure in production, HttpOnly, appropriate SameSite, and correct domain/path. | |

## 12. Admin navigation and common CRUD behavior

Apply CRUD-01 through CRUD-05 to every visible resource in the intended configuration:

- Announcements
- Academic/School Year
- Students, Teachers, Staff
- High School Subjects, Classes, Grades
- Instructors/Professors, Programs & Courses, Course Classes, Student Enrollments, College Grades
- Student Payments, Payment Types
- Quizzes, Quiz Groups, Answers
- Settings, Users, Roles

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| CRUD-01 | P1 | Open index, paginate, search, sort, and use available filters. | Results are correct, stable, and no data from disabled modules appears. | |
| CRUD-02 | P1 | Create with minimum valid fields, then with full valid fields. | Record saves once, success feedback appears, and redirect returns to index. | |
| CRUD-03 | P1 | Submit blank required fields, bad formats, excessive lengths, duplicates, and invalid relationships. | Clear field errors appear; invalid or partial record is not stored. | |
| CRUD-04 | P1 | Open detail; edit from index and detail; save and cancel/back. | Display matches stored data; edit from detail returns to detail; no accidental duplicate. | |
| CRUD-05 | P1 | Delete an unreferenced record, then one referenced by dependent data. | Confirmation is required; safe delete works; protected delete is blocked or cascades only as designed. | |
| ADM-01 | P1 | Check sidebar in light/dark mode and with narrow viewport. | Groups, labels, active state, icons, and text contrast are correct. | |
| ADM-02 | P1 | Create an admin with username containing normal allowed punctuation, then update an existing dotted username. | Valid usernames save without a false duplicate/email failure. | |
| ADM-03 | P1 | Create a student/teacher and inspect credentials modal. | Modal opens once, cannot close accidentally, and shows the exact generated credentials. | |
| ADM-04 | P1 | Upload/replace/remove student and teacher profile images. | Preview appears before save; supported images persist; broken/missing image uses safe fallback. | |
| ADM-05 | P2 | Click the sidebar trigger seven consecutive times with easter eggs on, then off. | Easter egg appears only when enabled and does not disrupt navigation/accessibility. | |

## 13. School year and high school curriculum

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| HS-01 | P0 | Create a new school year and set it active while another is active. | Exactly one school year remains active. | |
| HS-02 | P1 | Change active year and inspect dashboards, filters, classes, grades, and attendance. | Default context changes while historical records remain available and unchanged. | |
| HS-03 | P1 | Create grade levels and high school subjects; use duplicate/blank names. | Valid unique data saves; invalid duplicates/blanks are rejected. | |
| HS-04 | P0 | Create H1 with teacher T1, grade, section, school year, status, start/end time, and 4 periods. | Relationships persist and class appears in T1’s portal. | |
| HS-05 | P1 | Create H2 with 1, 2, 3, then 4 grading periods; try 0 and 5. | 1–4 accepted; out-of-range values rejected; grade UI uses exactly configured terms. | |
| HS-06 | P1 | Assign subjects to H1 and verify their order/display in grade entry and exports. | Only H1 subjects appear consistently and in intended order. | |
| HS-07 | P0 | Attempt to assign a college instructor as high school class adviser. | Relationship is rejected. | |
| HS-08 | P1 | Assign T1 to H1 in SY1, then test availability in same and different school years. | Adviser is excluded only where already allocated according to business rule. | |
| HS-09 | P0 | Add S1 to H1/SY1, then try adding S1 to another high school class in SY1. | First enrollment succeeds; conflicting second class is rejected. | |
| HS-10 | P1 | Enroll S1 in a class in SY0 and inspect class history. | Historical class and grades remain separately accessible. | |
| HS-11 | P1 | Toggle class active/status and assignment enablement. | Portals and assignments respect the values without deleting history. | |
| HS-12 | P1 | Set class start time; record scans before, exactly at, and after start. | First daily scan determines tardiness; only after start is late. | |
| HS-13 | P1 | Remove class start time and scan after a typical start. | Student is not marked late when no start is configured. | |

## 14. Student administration and import

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| STU-01 | P1 | Create a student with minimum fields and full profile, including long addresses/birthplace. | Valid fields persist without truncation; portal account/access is created as designed. | |
| STU-02 | P0 | Create/import duplicate username/LRN or other unique identifier. | Duplicate is rejected or deterministically updates only the intended record; no duplicate account. | |
| STU-03 | P1 | Edit name, academic fields, photo, RFID, elementary fields, and T-shirt size. | Changes appear consistently in admin, portal, attendance directory, and exports. | |
| STU-04 | P1 | Delete/archive a student with class, grade, attendance, payment, or submission history. | System protects required history and explains the action; no orphaned records. | |
| STU-05 | P1 | Download the import template and open it in Excel/LibreOffice. | Workbook opens without repair warning and headers/instructions are usable. | |
| STU-06 | P0 | Import a valid workbook with several students. | Correct rows import once; accounts/access use configured defaults; success count is accurate. | |
| STU-07 | P1 | Include a non-student worksheet alongside the valid worksheet. | Non-student worksheet is ignored. | |
| STU-08 | P1 | Import blank file, wrong extension, missing headers, malformed values, duplicates, and mixed valid/invalid rows. | Validation is understandable; no silent corruption; transaction/partial-import behavior is documented by result. | |
| STU-09 | P1 | Re-import the same valid workbook. | No unintended duplicate students, users, or access rows are created. | |
| STU-10 | P1 | Export all students and a class-filtered set. | CSV/XLSX contains correct rows, headers, encoding, dates, and no unrelated students. | |
| STU-11 | P1 | Open student detail with payments/quizzes on and off. | Academic relationships always show; optional relationships appear only when enabled. | |
| STU-12 | P1 | Toggle elementary and T-shirt settings and revisit create/edit/detail. | Fields appear/disappear consistently; hidden existing values are preserved unless explicitly cleared. | |

## 15. College management

Run if the college module is intended for production.

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| COL-01 | P1 | Create a program and add courses across year levels and semesters. | Courses belong to the correct program and sort by year, semester, then workbook/order value. | |
| COL-02 | P0 | Compare college program courses with high school subjects. | They are independent; edits/deletes do not cross-affect each other. | |
| COL-03 | P1 | Create instructor I1 and sign in to teacher portal. | Instructor account works and is identified as instructor/teacher portal user. | |
| COL-04 | P0 | Attempt to assign a high school teacher to a college course class. | Invalid relationship is rejected. | |
| COL-05 | P1 | Create course class with school year, program course, instructor, and schedule data. | Course class saves and appears in I1’s schedule/context. | |
| COL-06 | P0 | Enroll S3 with program, school year, semester, and year level. | Enrollment saves once and resolves S3 as college context. | |
| COL-07 | P0 | Add enrollment courses that match enrollment program/year/semester. | Only eligible offerings/courses are accepted. | |
| COL-08 | P0 | Try another program, year, semester, or school year course. | Every mismatch is rejected with no partial enrollment course. | |
| COL-09 | P1 | Use quick-add student from college enrollment. | Student and account are created once; enrollment can continue with the new student. | |
| COL-10 | P1 | Enter/update college grades and inspect student and instructor portals. | Correct course label, units/context, and grade appear to the correct people. | |
| COL-11 | P0 | Give S5 two conflicting active college enrollments, only in disposable test data. | System reports/blocks ambiguous academic context rather than choosing silently. | |
| COL-12 | P1 | Mark a college enrollment inactive and revisit S3 portal. | Inactive enrollment no longer drives current context; history remains. | |
| COL-13 | P1 | Disable college module and clear caches. | College sidebar/resources disappear; high school workflows remain; direct college routes are inaccessible. | |

## 16. Teacher/instructor portal

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| TCH-01 | P1 | Open dashboard for T1 with multiple school years/classes; change filters and tabs. | Selected context is valid, retained in links, and only T1-owned data appears. | |
| TCH-02 | P1 | Search/filter students by name, ID, gender, class, and school year. | Results and counts match filters; archived students follow the intended visibility rule. | |
| TCH-03 | P1 | Add one and multiple existing students to T1’s class; submit none; try T2’s class ID. | Valid selections add once; empty shows error; foreign class is denied. | |
| TCH-04 | P1 | Edit a class-student’s notes and Hide Grade setting. | Values persist and student grade access follows Hide Grade. | |
| TCH-05 | P1 | Remove a student from T1’s class. | Only the class membership is removed; master student and unrelated history remain. | |
| TCH-06 | P0 | Enter grade values 0, decimal, 100, blank, -1, 101, and text. | Configured valid values save exactly; invalid values are rejected. | |
| TCH-07 | P0 | Save partial grades, then submit with missing terms, then complete and submit. | Draft allows partial values; final submission requires all configured terms. | |
| TCH-08 | P0 | Attempt to edit grades after final submission. | Grades are locked and unchanged. | |
| TCH-09 | P1 | Test grade entry for 1-, 2-, 3-, and 4-period classes. | Only configured terms are required/displayed/exported. | |
| TCH-10 | P1 | Open grade modal and exports for T1’s students, then manipulate IDs for T2. | T1 data works; foreign data is denied. | |
| TCH-11 | P1 | Export filtered students, QR PDF, and bulk grade PDF. | Exports contain only the selected T1 class/year and open correctly. | |
| TCH-12 | P1 | Archive students with and without a class filter. | Only T1-owned intended rows are archived. | |
| TCH-13 | P1 | Add a schedule with complete data and with missing/unusual values. | Valid schedule appears; required validation prevents unusable records. | |
| TCH-14 | P1 | Open teacher profile and compare admin record/photo/account fields. | Display is accurate and does not expose password or unrelated private data. | |

## 17. Assignments

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| ASN-01 | P1 | In an assignment-enabled T1 class, create with PDF/DOC/DOCX below 20 MB. | Assignment is saved as draft; original file name is retained for download. | |
| ASN-02 | P1 | Try EXE/JPG, missing file, over 20 MB, blank/over-200 title, and invalid deadline. | Each invalid submission is rejected and no orphan file/record remains. | |
| ASN-03 | P0 | While draft, inspect S1 dashboard and request its URL directly. | Draft is invisible/inaccessible to students. | |
| ASN-04 | P0 | Send assignment to class twice. | It becomes visible once; one notification per student; no duplicate notifications. | |
| ASN-05 | P1 | Extend deadline, edit notes, then attempt to shorten deadline. | Extension/notes save; earlier deadline is rejected. | |
| ASN-06 | P1 | S1 downloads assignment; S2 and a college student try direct URL. | S1 receives correct file; non-class students are denied. | |
| ASN-07 | P0 | S1 submits valid PDF/DOC/DOCX and optional notes. | One submission is stored with correct student, file, notes, and timestamp. | |
| ASN-08 | P1 | S1 resubmits a different file. | Existing submission updates; old stored file is removed; no duplicate row. | |
| ASN-09 | P1 | Submit an invalid/oversized file and test after deadline. | File validation works; deadline behavior matches approved school rule and is explicitly accepted. | |
| ASN-10 | P1 | T1 opens summary and downloads S1 submission. | Submitted/not-submitted list is accurate; correct original file downloads. | |
| ASN-11 | P0 | T2 manipulates assignment/submission ID; S2 manipulates S1 submission ID. | All foreign access is denied. | |
| ASN-12 | P1 | Delete a draft with no submissions, then try an assignment with submissions. | Draft and stored file delete; submitted assignment is protected. | |
| ASN-13 | P1 | Disable assignments on H1 after an assignment exists. | Student no longer gains access; history is not silently deleted. | |

## 18. Student portal

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| POR-01 | P1 | Open S1 dashboard at morning, afternoon, and evening server times. | Greeting matches local time and page renders normally. | |
| POR-02 | P1 | Inspect current class, subjects, school year, announcements, grades, assignments, quizzes, and payments as applicable. | Academic context is correct and optional tabs follow configuration. | |
| POR-03 | P1 | Open profile and compare all displayed fields with admin record. | Data is accurate; sensitive account fields are not exposed. | |
| POR-04 | P0 | Open current/historical grade modal and download PDF. | Correct student/class/year and configured terms appear. | |
| POR-05 | P0 | Set Hide Grade for S1 and repeat modal/download/direct URL. | Hidden grade is not exposed by UI or direct download route. | |
| POR-06 | P1 | Open active, future, and expired announcements for each audience. | Only current announcements targeted to student/all are visible; safe formatting works. | |
| POR-07 | P0 | Put script, event handler, javascript URL, and safe rich text in an announcement. | Scriptable HTML is removed/neutralized; allowed formatting remains. | |
| POR-08 | P1 | Test empty states: no class, no grades, no assignments, no payments, and no announcements. | Helpful empty state appears; no exception or misleading zero data. | |
| POR-09 | P0 | Sign in as S3 college student and inspect academic context. | College data appears without leaking high school class workflows. | |
| POR-10 | P0 | Sign in as S5 with intentionally conflicting context. | Clear safe handling occurs; no arbitrary other enrollment is displayed. | |
| POR-11 | P1 | Use mobile viewport for navigation, tabs, modals, tables, forms, downloads, and logout. | Controls remain reachable/readable without destructive overlap or horizontal trapping. | |

## 19. Announcements and notifications

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| ANN-01 | P1 | Create announcements for all, admin, teacher, and student audiences. | Each audience sees exactly its intended announcements. | |
| ANN-02 | P1 | Set start/expiry boundaries just before, exactly at, and after current time. | Visibility changes at the correct Manila time. | |
| ANN-03 | P1 | Open announcements from menus/modal on teacher and student portals. | Same sanitized content and metadata appear; modal closes and refocuses correctly. | |
| ANN-04 | P1 | Edit and delete an active announcement while portal is open, then refresh. | Updated state appears consistently and deleted item is unavailable. | |
| ANN-05 | P1 | Send an assignment and inspect notifications for class students and outsiders. | Exactly the intended class students receive one valid notification. | |

## 20. Attendance, QR, RFID, and scanner APIs

Use a separate API collection and record request, response, timestamp, status code, and resulting database/UI row. Never include production tokens in evidence.

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| ATT-01 | P0 | Call token validation with missing, invalid, and valid token. | Missing/invalid rejected generically; valid accepted over HTTPS. | |
| ATT-02 | P1 | Call school data and student-image endpoints with valid/invalid authorization and parameters. | Authorized response/file is correct; invalid input is rejected; no path/data leak. | |
| ATT-03 | P0 | Call autosync and compare students, teachers/instructors, and staff to enabled configuration. | Directory contains correct unique scanner identifiers and excludes disabled/inactive identities. | |
| ATT-04 | P1 | Call RFID card directory. | Only assigned, enabled cards are returned with correct type and record identity. | |
| ATT-05 | P0 | Scan S1’s RFID once. | Success response; attendance row references S1 and correct Manila date/time. | |
| ATT-06 | P0 | Repeat same S1 scan immediately. | Quick duplicate is skipped according to rule; no duplicate attendance inflation. | |
| ATT-07 | P1 | Scan S1 later the same day. | Event handling matches first/last-scan reporting; first scan remains tardiness basis. | |
| ATT-08 | P0 | Scan T1/I1 RFID and ST1 RFID. | Attendance references correct adviser/staff identity; no student row is created. | |
| ATT-09 | P1 | Test ST1 before/at/after shift start and with missing shift start. | Late status uses first scan and shift time; missing time is not falsely late. | |
| ATT-10 | P0 | Scan an unassigned/malformed/blank/too-long RFID value. | Request is rejected without storing a record or exposing internals. | |
| ATT-11 | P0 | Assign one RFID UID to S1 then try same UID for T1/ST1/another student. | Duplicate cross-person assignment is rejected. | |
| ATT-12 | P1 | Use admin RFID Checker for assigned student, teacher/instructor, staff, and unknown card. | Correct identity appears; unknown is clearly reported; no unrelated PII. | |
| ATT-13 | P1 | Test RFID registration from index/detail: scan once, autofocus, save, cancel, and replace card. | Intended record updates without full-page corruption; uniqueness still enforced. | |
| ATT-14 | P0 | Send legacy student attendance sync payload. | Supported payload records correct student attendance once. | |
| ATT-15 | P0 | Send teacher record-ID sync payload. | Correct teacher attendance is recorded; IDs cannot be retyped to another entity. | |
| ATT-16 | P1 | Send a batch with valid, duplicate, malformed, and unknown records. | Per-record result is accurate; valid data is not duplicated; invalid data does not corrupt batch. | |
| ATT-17 | P1 | Simulate offline records spanning midnight Manila time and sync later. | Original event date/time is preserved according to contract; day is not shifted incorrectly. | |
| ATT-18 | P0 | Open student attendance dashboard and filter start/end/same day/no results/invalid range. | Rows, total, first/last scan, absence estimate, late count, and date filters are accurate. | |
| ATT-19 | P0 | Open staff attendance dashboard with the same boundary cases. | Only eligible teacher/staff records appear; shift and tardy calculations are accurate. | |
| ATT-20 | P0 | Inspect raw attendance resource/list. | Employee rows do not appear in the student-only resource and identities are not mixed. | |
| ATT-21 | P1 | Disable QR while RFID remains on; exercise QR exports/API and RFID flows. | QR-only functions are hidden/404; RFID directory and scan continue working. | |
| ATT-22 | P1 | Disable RFID while QR remains on; exercise checker, fields, registration, and APIs. | RFID functions are hidden/rejected; QR attendance remains working. | |
| ATT-23 | P1 | Disable teacher/staff attendance and then staff module independently. | Relevant fields/menu/directory identities disappear without breaking student attendance. | |
| ATT-24 | P1 | Send concurrent duplicate scans from two clients. | Transaction/uniqueness logic prevents unintended double count. | |

## 21. Payments

Run if payments are intended for production.

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| PAY-01 | P0 | Sign in as A1 and open payment menu/pages. | A1 has direct access because username matches configured authorized admin. | |
| PAY-02 | P0 | As A2 open payment pages; enter wrong then A1’s correct password. | Locked until correct A1 password; unlock is session-scoped and auditable. | |
| PAY-03 | P1 | Log out/end A2 session after unlock and return. | Unlock does not persist into a new session. | |
| PAY-04 | P1 | Create/edit/deactivate payment types; try duplicate/blank type. | Valid type saves; invalid type is rejected; historical payments retain their type. | |
| PAY-05 | P0 | Add payments for S1 at exact start/end date-times, before, and after. | Amount, type, date/time, student, and notes persist exactly. | |
| PAY-06 | P0 | Export with start/end boundaries and inspect file. | Includes both boundaries and excludes outside records; totals match UI/database. | |
| PAY-07 | P1 | Search/filter student payment view and select multiple students. | Summary aggregates only selected students and correct date/type filters. | |
| PAY-08 | P0 | Inspect S1 payment history, then S2 and disabled module. | Student sees only own history; tab/data disappear when disabled. | |
| PAY-09 | P0 | Manipulate student/payment IDs in admin and student requests. | Unauthorized user cannot expose or alter another student’s payment data. | |
| PAY-10 | P1 | Test zero, negative, excessive, malformed, and decimal amounts. | Only business-valid monetary values save with correct decimal precision. | |
| PAY-11 | P1 | Disable module and clear caches. | Sidebar/resources/routes/student tab are unavailable; historical data remains stored. | |

## 22. Quiz module

Run only if quizzes will be enabled; otherwise execute QZ-10.

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| QZ-01 | P1 | Create questions, answers, a quiz group, days, and grade/school-year association. | Relationships save correctly and required validation is clear. | |
| QZ-02 | P1 | Reorder questions and refresh/open another browser. | Order persists deterministically. | |
| QZ-03 | P1 | Sign in as eligible S1 and ineligible S2/S3. | Only matching high school grade/year student can access. | |
| QZ-04 | P0 | Submit one valid answer per question. | Answers are stored for S1 and score is calculated from configured correctness. | |
| QZ-05 | P0 | Tamper with an answer ID belonging to another quiz. | Foreign answer is ignored/rejected and cannot earn credit. | |
| QZ-06 | P1 | Submit missing, duplicate, and repeat submissions. | Validation/state behavior matches school rule without duplicate answer rows. | |
| QZ-07 | P1 | Admin opens group scores and filters/compares with submitted answers. | Student scores and totals are accurate. | |
| QZ-08 | P0 | S1/S2 manipulate quiz group/day IDs. | Ineligible/foreign quiz is inaccessible. | |
| QZ-09 | P1 | Delete/deactivate question/group with existing answers. | Historical results are protected or impact is clearly controlled. | |
| QZ-10 | P1 | Disable module and clear caches; request quiz routes directly. | Menus/tabs/routes are absent or 404; other portal features remain operational. | |

## 23. Exports, PDFs, uploads, and file safety

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| FILE-01 | P1 | Generate student QR PDF with 0, 1, and many students. | Valid PDF downloads; pages are not clipped; every QR is unique/readable and labeled. | |
| FILE-02 | P0 | Scan several exported QR codes and compare identity. | Each code resolves only to the intended student. | |
| FILE-03 | P1 | Generate individual and bulk grade PDFs for varied names/subjects/terms. | Files open without repair; content is complete, legible, and correctly paginated. | |
| FILE-04 | P1 | Export student/adviser/payment CSVs with commas, quotes, accents, and long text. | Correct encoding and CSV escaping; spreadsheet opens without shifted columns. | |
| FILE-05 | P0 | Apply class/year/date filters, export, and compare row-by-row with UI/data. | Export honors all filters and role ownership. | |
| FILE-06 | P0 | Upload a renamed executable/script as an allowed-looking extension and use double extensions. | Server validates actual upload safely, does not execute it, and rejects disallowed content as designed. | |
| FILE-07 | P1 | Download files with spaces, Unicode, quotes, and very long names. | Correct safe file downloads with sensible content type/name. | |
| FILE-08 | P0 | Request another user’s file URL and traversal/encoded traversal variants. | Access/path rules deny unauthorized or out-of-root files. | |
| FILE-09 | P1 | Replace/delete records with uploaded files and inspect storage. | Superseded files are cleaned where designed; active files are not orphaned or deleted incorrectly. | |

## 24. Feature-switch regression

For each switch, change it in staging, clear configuration/application caches, restart workers if needed, then test both UI and direct URLs/APIs.

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| CFG-01 | P1 | College On → Off → On. | College UI/routes follow switch; data survives and returns intact. | |
| CFG-02 | P1 | Quiz On → Off → On. | Quiz UI/routes follow switch; data survives and returns intact. | |
| CFG-03 | P1 | Payments On → Off → On. | Payment UI/routes follow switch; authorization remains required after re-enable. | |
| CFG-04 | P1 | Staff On → Off → On. | Staff resource/directory follows switch; teacher and student functions remain stable. | |
| CFG-05 | P1 | Staff attendance On → Off → On. | Staff attendance fields/page/directory behavior is internally consistent. | |
| CFG-06 | P1 | QR On → Off → On. | QR actions/API follow setting; RFID independence is preserved. | |
| CFG-07 | P1 | RFID On → Off → On. | RFID fields/checker/API follow setting; QR independence is preserved. | |
| CFG-08 | P2 | T-shirt and elementary fields On → Off → On with existing values. | UI follows switches and stored values survive. | |
| CFG-09 | P2 | Easter eggs On → Off. | Only optional easter-egg behavior changes. | |
| CFG-10 | P0 | After every switch scenario, run SMK-01 through SMK-12. | No switch causes unrelated regression. | |

## 25. Security and privacy exploratory tests

Perform only on staging or with written authorization.

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| SEC-01 | P0 | Enter HTML/JS payloads in names, notes, announcements, schedules, assignment notes, and search. | Output is encoded/sanitized; no script executes in any portal/export. | |
| SEC-02 | P0 | Try SQL metacharacters and wildcard-heavy strings in login, search, filters, and API fields. | No SQL error/bypass; parameterized behavior and safe validation. | |
| SEC-03 | P0 | Modify hidden fields, relationship IDs, booleans, and HTTP verbs. | Server authorization/validation controls result regardless of UI. | |
| SEC-04 | P0 | Check login, unlock, scanner, and import endpoints for brute-force/oversized request handling. | Reasonable throttling/size limits; service remains available. | |
| SEC-05 | P0 | Inspect HTML, API JSON, logs, error pages, exports, and browser storage. | No password/hash, application key, token, absolute server path, or unrelated PII leaks. | |
| SEC-06 | P1 | Verify HTTPS redirect, HSTS and security headers in production-like staging. | No credentials/files sent over HTTP; headers match deployment policy. | |
| SEC-07 | P1 | Open protected pages in two roles using separate browser profiles; log one out/change password. | Sessions remain isolated; one role cannot inherit another’s access. | |
| SEC-08 | P1 | Test clickjacking/content embedding according to deployment policy. | Sensitive/admin pages cannot be framed by untrusted origins. | |
| SEC-09 | P0 | Run dependency audit (`composer audit`, `npm audit`) and review results. | No unresolved known Critical/High vulnerability accepted without documented decision. | |

## 26. Usability, accessibility, and compatibility

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| UX-01 | P1 | Complete P0 flows using keyboard only. | Logical tab order, visible focus, usable menus/modals/forms, no keyboard trap. | |
| UX-02 | P1 | Inspect labels, error association, headings, alt text, table headers, and modal names with accessibility tools. | Core flows meet WCAG 2.1 AA fundamentals. | |
| UX-03 | P1 | Zoom browser to 200% and test mobile width. | Content remains readable/operable without loss or overlap. | |
| UX-04 | P1 | Check color contrast/focus/error state in light and dark themes. | Text and controls remain distinguishable; meaning is not color-only. | |
| UX-05 | P2 | Test long names, sections, courses, announcement titles, and translated/special characters. | Layout wraps/truncates safely and full value remains accessible. | |
| UX-06 | P1 | Double-click submit/delete/send and use browser refresh/back during requests. | No duplicate records/actions; user gets clear state feedback. | |
| UX-07 | P1 | Repeat P0 cases on supported browser/device matrix. | No material browser-specific failure. | |
| UX-08 | P2 | Print/open generated pages and PDFs using common viewer/printer settings. | Content is legible with sensible page breaks and margins. | |

## 27. Performance, reliability, and concurrency

Use representative volumes agreed with the school; suggested minimum: 5,000 students, 200 staff/teachers, 200 classes/course offerings, 100,000 attendance events, and several years of grades/payments.

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| PERF-01 | P1 | Measure login and dashboard response at representative volume. | P95 server response meets agreed target (suggested ≤2 s; reports ≤5 s). | |
| PERF-02 | P1 | Search/filter/paginate students, attendance, payments, and enrollments. | No timeout/memory error; response and query count stay reasonable. | |
| PERF-03 | P1 | Generate largest expected imports/exports/PDFs. | Completes within agreed limit without truncated/corrupt file or server exhaustion. | |
| PERF-04 | P0 | Send expected peak scanner traffic plus duplicate bursts. | No lost valid scans, unintended duplicates, or API outage. | |
| PERF-05 | P0 | Two teachers/admins edit the same grade/enrollment/payment-related record. | Conflict behavior is known; last-write does not silently corrupt related data. | |
| PERF-06 | P1 | Interrupt network during upload/import/submit, then retry. | No half-created record/orphan file; retry is safe and user receives clear result. | |
| PERF-07 | P1 | Run for a full school-day simulation and inspect logs/resources. | No progressive memory, queue, session, or connection failure. | |

## 28. Backup, restore, and disaster recovery

| ID | Pri | Test and steps | Expected result | Status / evidence |
|---|---:|---|---|---|
| BAK-01 | P0 | Back up database, uploaded files, and required environment/configuration separately from host. | Timestamped backup completes and is access-controlled. | |
| BAK-02 | P0 | Restore to a clean staging instance. | Application boots and record/file counts match backup manifest. | |
| BAK-03 | P0 | Spot-check restored student, account, class/enrollment, grade, attendance, payment, assignment/submission, and image. | Database relationships and corresponding files are intact. | |
| BAK-04 | P0 | Apply pending migrations to the restored copy, then repeat smoke suite. | Migration succeeds with no data loss and all P0 cases pass. | |
| BAK-05 | P1 | Measure backup/restore duration and data loss window. | Meets approved RPO/RTO; result is documented. | |
| BAK-06 | P1 | Verify rollback procedure for failed deployment without destructive commands on the only copy. | Previous app release and compatible data can be recovered by documented steps. | |

## 29. Automated regression commands

Run from the project root:

```powershell
php artisan migrate:status --no-ansi
composer test
npm run build
composer audit
npm audit --omit=dev
```

Record:

| Check | Result | Date/time | Evidence/notes |
|---|---|---|---|
| Migration status (no pending) | | | |
| PHPUnit suite | | | |
| Production asset build | | | |
| Composer dependency audit | | | |
| NPM dependency audit | | | |

The existing PHPUnit suite covers important portions of admin authentication boundaries, resource workflows, student details/dashboard behavior, upload previews, college rules, academic-context conflicts, class adviser availability, QR export, RFID and staff attendance, tardiness, payments, portal enhancements, and student import. Manual testing remains required for browser behavior, full workflows, deployment, scanner hardware, permissions-by-ID, file safety, accessibility, concurrency, and recovery.

### Baseline observed while preparing this plan

On July 29, 2026, `composer test` produced **87 passed, 1 failed, 493 assertions**. The single failure was:

`Tests\Feature\UploadPreviewConfigurationTest::test_profile_uploads_use_previewable_image_fields`

The failure occurred because the test accessed the `settings` table without creating the test schema (`SQLSTATE[HY000]: no such table: settings`). The two other tests in that class passed. Treat the suite as **failed** until the test setup is corrected and all 88 tests pass in one clean run; do not suppress or exclude the failing test for release sign-off.

## 30. Suggested execution order

1. Back up and restore into staging.
2. Apply migrations and validate build/configuration.
3. Run automated regression and dependency audits.
4. Run P0 smoke.
5. Run authentication and access-isolation cases.
6. Run admin master-data and high school/college academic setup.
7. Run teacher/instructor and student end-to-end workflows.
8. Run attendance/scanner, payment, quiz, import/export, and file cases.
9. Run feature-switch regression.
10. Run cross-browser, accessibility, performance, security, and recovery cases.
11. Re-test fixes, run P0 smoke again, review logs, and obtain UAT sign-off.

## 31. Test execution summary

| Priority | Total applicable | Passed | Failed | Blocked | N/A |
|---|---:|---:|---:|---:|---:|
| P0 | | | | | |
| P1 | | | | | |
| P2 | | | | | |
| **Total** | | | | | |

### Defect summary

| Severity | Open | Fixed awaiting retest | Closed | Accepted risk |
|---|---:|---:|---:|---:|
| Critical | | | | |
| High | | | | |
| Medium | | | | |
| Low | | | | |

## 32. UAT sign-off

| Decision | Name / role | Signature | Date | Notes |
|---|---|---|---|---|
| QA recommendation | | | | |
| School administrator acceptance | | | | |
| Academic/registrar acceptance | | | | |
| Finance acceptance, if applicable | | | | |
| Technical release approval | | | | |

**Final decision:** ☐ Approved for release ☐ Approved with accepted risks ☐ Not approved
