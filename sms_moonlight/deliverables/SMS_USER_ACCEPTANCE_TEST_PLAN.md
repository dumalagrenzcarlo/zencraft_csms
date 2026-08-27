# School Management System — User Acceptance Test Plan

**Purpose:** Confirm that the system supports the school’s day-to-day work and is ready for actual users.  
**Intended testers:** School administrators, registrar/academic staff, teachers, instructors, finance staff, attendance staff, and students.  
**Testing style:** Follow the scenario as a normal user. Record the result as **Passed**, **Failed**, **Blocked**, or **Not Applicable**.

## Acceptance rules

- All Critical scenarios must pass.
- High-priority failures must be corrected or formally accepted by the school.
- Grades, enrollment, attendance, payments, and student privacy must be correct.
- Each participating department must approve its section.

## Test accounts and sample data

Prepare test-only accounts and records:

- School administrator
- Payment-authorized administrator and regular administrator
- High school teacher with an assigned class
- A second teacher with a different class
- College instructor with an assigned course class
- High school student with an active account
- A second student in another class
- College student with an active enrollment
- Inactive student account
- Non-teaching staff member
- Current and previous school years
- Sample subjects, classes, college program/courses, grades, assignments, announcements, attendance, and payments

## User acceptance checklist

| ID | Priority | User | Scenario | User steps | Expected result |
|---|---|---|---|---|---|
| ACC-01 | Critical | Administrator | Open the administration portal | Open the administration portal from the school’s official link. | The correct login page opens with the school name and branding. |
| ACC-02 | Critical | Teacher | Open the teacher portal | Open the teacher portal from the school’s official link. | The teacher login page opens and is clearly different from the admin portal. |
| ACC-03 | Critical | Student | Open the student portal | Open the student portal from the school’s official link. | The student login page opens and is clearly intended for students. |
| ACC-04 | Critical | Administrator | Sign in with correct credentials | Enter a valid administrator username and password. | The administrator dashboard opens. |
| ACC-05 | Critical | Teacher | Sign in with correct credentials | Enter a valid teacher username and password. | The teacher dashboard opens and shows only the teacher’s assigned work. |
| ACC-06 | Critical | Student | Sign in with correct credentials | Enter a valid student username and password. | The student dashboard opens and shows only that student’s information. |
| ACC-07 | High | All users | Enter an incorrect password | Try signing in with a valid username and wrong password. | Access is refused and a clear, non-confusing message is shown. |
| ACC-08 | Critical | All users | Use credentials on the wrong portal | Try student credentials on the admin/teacher portal and teacher credentials on the admin/student portal. | Access is refused on every incorrect portal. |
| ACC-09 | High | Teacher / Student | First-time password change | Sign in using a newly issued account and follow the password-change prompt. | The user must choose a new password before using the portal. |
| ACC-10 | High | Teacher / Student | Change password | Enter the current password, a new password, and confirmation. Then sign out and sign in again. | The new password works and the old password no longer works. |
| ACC-11 | Critical | Student | Inactive student account | Attempt to sign in using an inactive student account. | The inactive account cannot enter the student dashboard. |
| ACC-12 | Critical | All users | Sign out | Sign out and then try to return to the dashboard using Back or the saved link. | The user remains signed out and must sign in again. |
| NAV-01 | High | Administrator | Review dashboard | Open the administrator dashboard and review the cards, charts, and summaries. | Information is readable and agrees with the available school records. |
| NAV-02 | High | Administrator | Use the main menu | Open every menu group available to the administrator. | Each menu opens the correctly named page without confusion. |
| NAV-03 | High | Administrator | Search a list | Search for a known student, teacher, subject, and class. | The correct record is easy to find. |
| NAV-04 | Normal | Administrator | Sort and filter lists | Sort and filter a populated list, then clear the filters. | Results change correctly and return to the full list when cleared. |
| NAV-05 | High | All users | View on a laptop | Complete common tasks on a typical laptop screen. | Important buttons, forms, and information are fully visible and usable. |
| NAV-06 | High | Teacher / Student | View on a phone | Open the portal, menus, tabs, tables, and forms on a phone. | Content is readable and controls remain easy to use. |
| NAV-07 | Normal | All users | Light and dark appearance | Use light and dark appearance options where available. | Text and buttons remain readable in both appearances. |
| NAV-08 | Normal | All users | Helpful empty screens | Open an area with no records, such as no assignments or announcements. | A clear empty message appears instead of an error or misleading information. |
| YEAR-01 | Critical | Administrator | Create a school year | Add the next school year with complete information. | The new school year is saved and appears in the list. |
| YEAR-02 | Critical | Administrator | Select the active school year | Mark the intended school year as active. | Only one school year is active. |
| YEAR-03 | High | Administrator | View a previous school year | Select a previous school year and inspect its classes and records. | Historical information remains available and is not changed. |
| CUR-01 | High | Administrator | Add a grade level | Create a grade level used by the school. | The grade level is saved and available when creating classes. |
| CUR-02 | High | Administrator | Add high school subjects | Create the subjects taught for a grade level. | Subjects are saved and can be assigned to a class. |
| CUR-03 | Critical | Administrator | Create a high school class | Select school year, grade level, section, teacher, class times, and grading periods. | The class is saved with all selected details. |
| CUR-04 | Critical | Administrator | Assign subjects to a class | Add the correct subjects to the sample class. | The subjects appear in the teacher’s grade area for that class. |
| CUR-05 | High | Administrator | Use different grading periods | Create sample classes with two and four grading periods. | Each class shows only its configured grading periods. |
| CUR-06 | Critical | Administrator | Prevent a teacher conflict | Try assigning an unavailable teacher to another class in the same school year. | The system prevents an invalid assignment or clearly warns the administrator. |
| CUR-07 | High | Administrator | Assignment availability by class | Turn assignment use on for one class and off for another. | Assignment tools appear only for the class allowed to use them. |
| CUR-08 | High | Administrator | Class start and end time | Save and update the class start and end time. | Times are displayed correctly and used by attendance reporting. |
| STU-01 | Critical | Administrator | Add a student | Enter the required student information and save. | The student record and student portal account are created. |
| STU-02 | High | Administrator | Add a complete student profile | Add personal, contact, school-history, photo, and optional information. | All entered information appears correctly on the student record. |
| STU-03 | Critical | Administrator | Prevent duplicate students | Try adding a student using an existing student number or account username. | A duplicate is not created and the user receives a clear message. |
| STU-04 | High | Administrator | Edit student information | Correct the student’s name, contact information, photo, and optional fields. | Updates appear consistently in the administrator and student views. |
| STU-05 | Critical | Administrator | Enroll a high school student | Add the student to the intended class and school year. | The student appears once in that class. |
| STU-06 | Critical | Administrator | Prevent two current high school classes | Try adding the same student to a second class in the same school year. | The conflicting class enrollment is prevented. |
| STU-07 | High | Administrator | View student history | Open a student who has records in current and previous school years. | Current and historical classes/grades are clearly separated. |
| STU-08 | High | Administrator | Student account credentials | Create a student and note the displayed username and temporary password. | The credentials allow the correct student to start the first-time login process. |
| STU-09 | High | Administrator | Reset a student password | Use the reset-password action and ask the student to sign in. | The temporary password works and the student is prompted to replace it. |
| STU-10 | High | Administrator | Import students | Use the provided student workbook to import several valid students. | All valid students are created once and the completion message is accurate. |
| STU-11 | High | Administrator | Import a workbook with problems | Import a workbook containing missing and duplicate information. | The system explains the problem and does not create confusing duplicate records. |
| STU-12 | High | Administrator | Export students | Export all students, then export a selected class. | Each file opens and contains the correct students and readable columns. |
| STU-13 | Normal | Administrator | Optional student fields | Turn the T-shirt size and elementary-school information options on and off. | The corresponding fields appear only when intended; existing information is retained. |
| STU-14 | Critical | Administrator | Preserve student history | Attempt to remove a student who already has grades, attendance, or payment history. | The system protects important school history and explains what can be done. |
| TCH-01 | Critical | Administrator | Add a teacher | Enter the teacher’s required profile and account information. | The teacher is saved and receives teacher portal credentials. |
| TCH-02 | High | Administrator | Add an instructor | Add a college instructor with profile and account information. | The instructor is saved and can use the teacher/instructor portal. |
| TCH-03 | High | Administrator | Add non-teaching staff | Create a staff member with role and work schedule information. | The staff record is saved without creating an unnecessary teaching account. |
| TCH-04 | High | Administrator | Edit teacher information | Update the teacher’s name, photo, contact details, and assigned information. | Changes are shown correctly throughout the system. |
| TCH-05 | Critical | Teacher | See only assigned classes | Sign in as two different teachers and compare their dashboards. | Each teacher sees only their own classes, students, assignments, and submissions. |
| TCH-06 | High | Teacher | Filter dashboard | Change school year, class, gender, and student search filters. | The list and totals match the selected filters. |
| TCH-07 | High | Teacher | Add students to own class | Add one and then several existing students to the teacher’s class. | Selected students are added once to the correct class. |
| TCH-08 | Critical | Teacher | Cannot manage another teacher’s class | Attempt to open a student or class belonging to the second teacher. | The other teacher’s information cannot be viewed or changed. |
| TCH-09 | High | Teacher | Add class notes | Add and update notes for a student in the class. | Notes are saved for the correct class/student record. |
| TCH-10 | High | Teacher | Hide a student’s grades | Turn Hide Grade on for a student, then ask that student to view grades. | The student cannot view or download hidden grades. |
| TCH-11 | Critical | Teacher | Save draft grades | Enter some grades and choose Save without completing all grading periods. | The entered grades are saved as a draft and remain editable. |
| TCH-12 | Critical | Teacher | Validate grade values | Try grades below 0, above 100, and a valid value. | Invalid values are refused and valid grades are saved correctly. |
| TCH-13 | Critical | Teacher | Submit final grades | Complete every required grading period and submit the grades. | Final grades are saved and clearly marked as submitted. |
| TCH-14 | Critical | Teacher | Protect submitted grades | Try editing grades after final submission. | Submitted grades cannot be changed through the normal teacher workflow. |
| TCH-15 | High | Teacher | Export class list | Export the currently selected class and school year. | The file contains only the teacher’s selected students. |
| TCH-16 | High | Teacher | Export grade report | Export grades for the selected class. | A readable report opens with the correct students, subjects, and periods. |
| TCH-17 | High | Teacher | Create a schedule entry | Add a valid schedule entry and return to the schedule tab. | The new schedule appears with the correct day, section, and time. |
| POR-01 | Critical | Student | View own profile | Open Profile after signing in. | The page shows the correct student and does not show another student’s information. |
| POR-02 | Critical | Student | View current class information | Open the dashboard and review class, subjects, and school year. | Information matches the administrator’s current enrollment record. |
| POR-03 | Critical | Student | View grades | Open the grades area for the current class. | The student sees only their own available grades and correct grading periods. |
| POR-04 | High | Student | Download grade report | Download the student grade report and open it. | The report is readable and shows the correct student, class, subjects, and grades. |
| POR-05 | High | Student | View class history | Open a previous class or school year when available. | Historical information is clearly labeled and remains unchanged. |
| POR-06 | High | Student | View announcements | Open announcements intended for students. | Active student/all-user announcements are shown with readable formatting. |
| POR-07 | High | Student | View payment history | Open Payment History when the school uses payments. | The student sees only their own payments with correct dates, types, and amounts. |
| POR-08 | High | Student | View assignments | Open the assignment area for a class that uses assignments. | Only assignments sent to the student’s current class are shown. |
| POR-09 | Critical | Student | Cannot view another student | Try opening a saved link that belongs to a different test student. | The other student’s grades, files, and information are not shown. |
| POR-10 | High | College student | View college information | Sign in as the sample college student. | The dashboard shows the correct program, year, semester, courses, and grades. |
| ASN-01 | Critical | Teacher | Create an assignment draft | Select the class, enter title/notes/deadline, and attach a PDF, DOC, or DOCX. | The assignment is saved as a draft and is not yet visible to students. |
| ASN-02 | High | Teacher | Invalid assignment file | Try an unsupported file and a file larger than the allowed size. | The assignment is not saved and a clear message explains the file requirement. |
| ASN-03 | Critical | Teacher | Send assignment to class | Open the draft and choose Send to Class. | The assignment becomes visible to students in that class. |
| ASN-04 | High | Teacher | Extend an assignment deadline | Change the deadline to a later date. | The new deadline is saved and shown to students. |
| ASN-05 | High | Teacher | Prevent an earlier deadline | Try changing the deadline to an earlier date. | The change is refused and the existing deadline remains. |
| ASN-06 | Critical | Student | Download assignment | Open a sent assignment and download its attachment. | The correct file downloads and opens. |
| ASN-07 | Critical | Student | Submit assignment work | Attach a valid PDF, DOC, or DOCX, add optional notes, and submit. | The submission is saved with the correct file and submission time. |
| ASN-08 | High | Student | Replace a submission | Submit a different file for the same assignment. | The new file replaces the previous submission without creating two entries. |
| ASN-09 | Critical | Teacher | Review assignment summary | Open the assignment summary after one student submits. | Submitted and not-submitted students are shown accurately. |
| ASN-10 | High | Teacher | Download student submission | Download the sample student’s submitted work. | The correct file downloads with a useful file name. |
| ASN-11 | High | Teacher | Delete an unused draft | Delete a draft assignment that has no submissions. | The draft disappears after confirmation. |
| ASN-12 | Critical | Teacher | Protect assignment with submissions | Try deleting an assignment that already has student work. | The assignment and submissions are protected. |
| COL-01 | Critical | Administrator | Create a college program | Add a college program offered by the school. | The program is saved and available for courses and enrollment. |
| COL-02 | Critical | Administrator | Add program courses | Add courses for different year levels and semesters. | Courses appear under the correct program, year, and semester. |
| COL-03 | High | Administrator | Review course order | Open the program’s course list. | Courses are arranged in a logical year-and-semester order. |
| COL-04 | Critical | Administrator | Create a course class | Select school year, program course, instructor, and schedule details. | The course class is saved and appears for the assigned instructor. |
| COL-05 | Critical | Administrator | Prevent wrong teacher assignment | Try assigning a high school-only teacher to a college course class. | The invalid assignment is prevented. |
| COL-06 | Critical | Administrator | Enroll a college student | Select student, program, school year, year level, and semester. | The college enrollment is saved once. |
| COL-07 | Critical | Administrator | Add matching enrolled courses | Add courses that match the student’s program, year, semester, and school year. | Eligible courses are accepted and shown on the enrollment. |
| COL-08 | Critical | Administrator | Prevent mismatched enrolled course | Try adding a course from another program, year, semester, or school year. | The mismatched course is not added. |
| COL-09 | High | Administrator | Quick-add college student | Create a new student while working on an enrollment. | The new student is created once and can immediately be enrolled. |
| COL-10 | Critical | Instructor | View assigned college classes | Sign in as the instructor and review the dashboard/schedule. | Only the instructor’s assigned course classes appear. |
| COL-11 | Critical | Administrator / Instructor | Enter college grades | Record grades for the sample college enrollment. | Grades are saved for the correct student and course. |
| COL-12 | Critical | College student | View college grades | Ask the sample college student to view their grades. | The same course and grade information appears in the student portal. |
| ATT-01 | Critical | Attendance staff | Register a student RFID card | Open the student record and scan/enter the test card. | The card is assigned to the correct student. |
| ATT-02 | Critical | Attendance staff | Prevent duplicate RFID card | Try assigning the same card to another student, teacher, or staff member. | The duplicate assignment is prevented. |
| ATT-03 | Critical | Attendance staff | Record student attendance | Scan the student’s registered QR or RFID. | A successful result appears and attendance is recorded for that student. |
| ATT-04 | High | Attendance staff | Avoid an immediate duplicate scan | Scan the same student twice in quick succession. | The second scan does not create a misleading duplicate attendance entry. |
| ATT-05 | Critical | Attendance staff | Record teacher/instructor attendance | Scan the registered card for a teacher or instructor. | Attendance is recorded for the correct person, not as a student. |
| ATT-06 | Critical | Attendance staff | Record non-teaching staff attendance | Scan the registered card for the sample staff member. | Attendance is recorded under the correct staff member. |
| ATT-07 | High | Attendance staff | Unknown RFID card | Scan an unregistered card. | The system clearly reports that the card is not assigned and records no attendance. |
| ATT-08 | High | Administrator | Check a card’s owner | Use the RFID Checker for a student, teacher/staff, and unknown card. | The correct person is identified or the card is reported as unknown. |
| ATT-09 | Critical | Administrator | View student attendance report | Select a date range containing the test scans. | The correct students, dates, first/last scan, totals, and late counts appear. |
| ATT-10 | Critical | Administrator | Check student lateness | Compare scans before, at, and after the class start time. | Lateness agrees with the first scan and saved class start time. |
| ATT-11 | Critical | Administrator | View staff attendance report | Select a date range containing teacher/staff scans. | The correct staff, dates, first/last scan, totals, and late counts appear. |
| ATT-12 | High | Administrator | Check staff lateness | Compare the sample staff scan with the saved shift start time. | Lateness agrees with the first scan and staff schedule. |
| ATT-13 | High | Administrator | Use attendance date filters | Try one day, several days, no-result dates, and an end date before the start date. | Valid results are accurate and invalid dates are clearly handled. |
| ATT-14 | High | Administrator | Turn QR or RFID use off | Turn off one attendance method and review available actions. | Only the enabled attendance method remains available; records are preserved. |
| ANN-01 | High | Administrator | Create an announcement for everyone | Add title, formatted message, and active dates. | The announcement appears to the intended users during the active period. |
| ANN-02 | High | Administrator | Target students | Create a student announcement and check admin, teacher, and student views. | Students see it; unintended audiences do not. |
| ANN-03 | High | Administrator | Target teachers | Create a teacher announcement and check the portals. | Teachers/instructors see it; students do not. |
| ANN-04 | High | Administrator | Expire an announcement | Set an expiry time and check before and after it. | The announcement disappears after its expiry time. |
| ANN-05 | Normal | Administrator | Format an announcement | Use headings, lists, links, and simple emphasis. | Safe formatting is readable in the user portal. |
| ANN-06 | High | Administrator | Edit and remove an announcement | Update an active announcement, then delete it. | Users see the update and the deleted announcement is no longer available. |
| PAY-01 | Critical | Authorized finance admin | Open payment records | Sign in using the administrator authorized for payments. | Payment menus and records open directly. |
| PAY-02 | Critical | Regular administrator | Open protected payment records | Open payments and enter the authorized administrator’s password when prompted. | Correct approval unlocks payment pages for the current session only. |
| PAY-03 | High | Regular administrator | Use the wrong approval password | Enter an incorrect payment approval password. | Payment information remains locked. |
| PAY-04 | High | Finance staff | Create payment types | Add the payment types used by the school. | The types are available when recording a payment. |
| PAY-05 | Critical | Finance staff | Record a student payment | Select student/type, enter amount, date/time, and notes, then save. | The payment is saved exactly once under the correct student. |
| PAY-06 | Critical | Finance staff | Correct a payment | Edit the sample payment using the approved school process. | The corrected information is shown consistently. |
| PAY-07 | Critical | Student | View own payment history | Ask the student to open Payment History. | The correct payment appears and no other student’s payments are shown. |
| PAY-08 | Critical | Finance staff | Export payments by date | Export a range containing the sample payment and compare it with the screen. | The file opens and contains the same records and totals. |
| PAY-09 | High | Finance staff | Search and summarize payments | Search/select sample students and review the payment summary. | Totals include only the selected students and records. |
| PAY-10 | High | Regular administrator | End payment access | Sign out after payment approval and sign in again. | Payment approval must be obtained again. |
| QZ-01 | High | Administrator | Create quiz content | Add questions, answers, and mark the correct answers. | Quiz content is saved accurately. |
| QZ-02 | High | Administrator | Create a quiz group | Assign the quiz to the intended grade level and school year. | The eligible students can receive the quiz. |
| QZ-03 | Normal | Administrator | Arrange question order | Reorder questions and reopen the quiz. | The selected order is retained. |
| QZ-04 | Critical | Student | Take an eligible quiz | Sign in as an eligible student, choose answers, and submit. | The submission is accepted and belongs to the correct student. |
| QZ-05 | Critical | Student | Ineligible quiz access | Sign in as a student from another grade/year and look for the quiz. | The quiz is not available to the ineligible student. |
| QZ-06 | Critical | Administrator | Review quiz scores | Open the quiz group scores after the sample submission. | The displayed score agrees with the student’s answers. |
| QZ-07 | High | Administrator | Quiz option not in use | Turn off quizzes when the school does not use them and review portals. | Quiz menus and tabs are absent without affecting other school work. |
| REP-01 | High | Administrator | Student QR report | Export QR codes for sample students and scan several printed/on-screen codes. | Every QR is readable, labeled, and identifies the correct student. |
| REP-02 | Critical | Administrator | Individual grade report | Download one student’s grades. | The report opens and contains the correct student, class history, subjects, and grades. |
| REP-03 | Critical | Teacher | Class grade report | Export a class grade report. | The report contains only the selected class and accurate grades. |
| REP-04 | High | Administrator | Teacher/staff export | Export the teacher/staff list. | The file opens with the expected profile and account information. |
| REP-05 | High | Administrator | Names with punctuation and accents | Export records containing commas, apostrophes, accents, and long names. | Names remain readable and columns do not shift. |
| REP-06 | High | All users | Uploaded profile pictures | Upload, replace, and view student/teacher profile pictures. | A preview appears and the saved picture displays correctly. |
| REP-07 | High | Teacher / Student | Uploaded school documents | Download assignment and submission files with normal and long file names. | The correct document downloads and opens with a useful name. |
| USE-01 | High | All users | Complete work using keyboard | Use Tab, Enter, and arrow keys for login, menus, forms, and dialogs. | Important tasks can be completed without becoming trapped. |
| USE-02 | High | All users | Readability at larger text size | Zoom the browser to 200% on key pages. | Text and controls remain readable and usable. |
| USE-03 | High | All users | Clear form messages | Leave required fields blank or enter an invalid value in common forms. | The page clearly identifies what must be corrected. |
| USE-04 | High | All users | Prevent accidental duplicate actions | Double-click Save, Send, or Submit during a test transaction. | Only one intended record/action is created. |
| USE-05 | Normal | All users | Confirm school wording | Review labels, instructions, messages, dates, names, and report headings. | Wording matches the school’s terminology and is understandable to intended users. |

## Execution summary

| Priority | Applicable | Passed | Failed | Blocked | Not Applicable |
|---|---:|---:|---:|---:|---:|
| Critical | | | | | |
| High | | | | | |
| Normal | | | | | |
| Total | | | | | |

## Department sign-off

| Area | Representative | Decision | Date | Comments |
|---|---|---|---|---|
| School administration | | | | |
| Registrar / academics | | | | |
| High school teachers | | | | |
| College department | | | | |
| Attendance / staff administration | | | | |
| Finance, if applicable | | | | |
| Student representative | | | | |

**Final acceptance:** ☐ Approved ☐ Approved with agreed follow-up items ☐ Not approved

