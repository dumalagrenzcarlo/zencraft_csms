<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Models\Adviser;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\ClassAdviserSchedule;
use App\Models\ClassesModel;
use App\Models\ClassStudent;
use App\Models\ClassStudentGrade;
use App\Models\CollegeCourseOffering;
use App\Models\CollegeEnrollmentCourse;
use App\Models\Notification;
use App\Models\SchoolYear;
use App\Models\Setting;
use App\Models\Student;
use App\Services\Exports\StudentGradesPdfExporter;
use App\Services\Exports\StudentWorkbookExporter;
use App\Support\AttendanceTardySummary;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $staffProfiles = Adviser::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->get();
        $adviserProfile = $staffProfiles->firstWhere('staff_type', Adviser::TYPE_TEACHER);
        $teacher = $adviserProfile ?? $staffProfiles->first();

        abort_unless($teacher, 404);

        $instructorIds = $staffProfiles
            ->filter(fn (Adviser $profile): bool => $profile->isCollegeInstructor())
            ->pluck('id');
        $canUseAdviserContext = $adviserProfile !== null;
        $canUseInstructorContext = config('school_portal.features.college_module')
            && $instructorIds->isNotEmpty();
        $requestedContext = $request->string('context')->toString();
        $portalContext = match (true) {
            $requestedContext === 'instructor' && $canUseInstructorContext => 'instructor',
            $requestedContext === 'adviser' && $canUseAdviserContext => 'adviser',
            $canUseAdviserContext => 'adviser',
            default => 'instructor',
        };
        $allowedTabs = $portalContext === 'instructor'
            ? ['college-grades', 'attendance']
            : ['students', 'assignments', 'attendance', 'schedules'];
        $requestedTab = $request->string('tab')->toString();
        $activeTab = in_array($requestedTab, $allowedTabs, true) ? $requestedTab : null;

        $schoolYears = SchoolYear::query()
            ->orderByDesc('active')
            ->orderByDesc('id')
            ->get();

        $selectedSchoolYearId = $this->selectedSchoolYearId($request, $schoolYears);

        $classes = ClassesModel::query()
            ->with([
                'grade',
                'schoolYear',
                'adviser',
                'classSubjects.subject',
            ])
            ->where('adviser_id', $teacher->id)
            ->when($selectedSchoolYearId, fn ($query) => $query->where('school_year_id', $selectedSchoolYearId))
            ->orderBy('grade_id')
            ->orderBy('section')
            ->get();

        $selectedClass = $this->selectedClass($request, $classes);

        $collegeSchedules = config('school_portal.features.college_module')
            ? CollegeCourseOffering::query()
                ->select('college_course_offerings.*')
                ->leftJoin('school_year', 'school_year.id', '=', 'college_course_offerings.school_year_id')
                ->with([
                    'schoolYear',
                    'programCourse.program',
                ])
                ->whereIn('college_course_offerings.instructor_id', $instructorIds)
                ->where('college_course_offerings.active', true)
                ->orderByDesc('school_year.active')
                ->orderByDesc('school_year.id')
                ->orderBy('college_course_offerings.section')
                ->get()
            : collect();

        $activeCollegeSchoolYear = $collegeSchedules->first()?->schoolYear;

        $collegeGradeRecords = config('school_portal.features.college_module')
            ? CollegeEnrollmentCourse::query()
                ->with([
                    'enrollment.student',
                    'enrollment.program',
                    'programCourse',
                    'offering.schoolYear',
                    'offering.programCourse',
                ])
                ->whereHas(
                    'offering',
                    fn ($query) => $query->whereIn('instructor_id', $instructorIds)
                )
                ->get()
                ->sortBy(fn (CollegeEnrollmentCourse $course): string => implode('|', [
                    $course->programCourse?->course_code ?? '',
                    $course->enrollment?->student?->lastname ?? '',
                    $course->enrollment?->student?->firstname ?? '',
                ]))
                ->values()
            : collect();

        $selectedCollegeClass = $request->filled('college_class_id')
            ? $collegeSchedules->firstWhere('id', $request->integer('college_class_id'))
            : null;
        $collegeGradeSearch = trim($request->string('college_grade_search')->toString());
        $requestedCollegeGradeStatus = $request->string('college_grade_status')->toString();
        $collegeGradeStatus = in_array($requestedCollegeGradeStatus, ['draft', 'submitted'], true)
            ? $requestedCollegeGradeStatus
            : '';
        $filteredCollegeGradeRecords = $selectedCollegeClass
            ? $collegeGradeRecords
                ->where('offering_id', $selectedCollegeClass->id)
                ->when($collegeGradeSearch !== '', function ($records) use ($collegeGradeSearch) {
                    $needle = Str::lower($collegeGradeSearch);

                    return $records->filter(function (CollegeEnrollmentCourse $record) use ($needle): bool {
                        $student = $record->enrollment?->student;
                        $searchable = implode(' ', [
                            $student?->lrn,
                            $student?->firstname,
                            $student?->middlename,
                            $student?->lastname,
                        ]);

                        return Str::contains(Str::lower($searchable), $needle);
                    });
                })
                ->when($collegeGradeStatus !== '', function ($records) use ($collegeGradeStatus) {
                    return $records->filter(
                        fn (CollegeEnrollmentCourse $record): bool => $collegeGradeStatus === 'submitted'
                            ? $record->gradesAreSubmitted()
                            : ! $record->gradesAreSubmitted()
                    );
                })
                ->values()
            : collect();

        $classStudentsQuery = ClassStudent::query()
            ->select('class_students.*')
            ->with('student')
            ->join('students', 'class_students.student_id', '=', 'students.id')
            ->when($selectedClass, fn ($query) => $query->where('class_id', $selectedClass->id))
            ->when($selectedSchoolYearId, fn ($query) => $query->where('school_year_id', $selectedSchoolYearId))
            ->whereHas('class', fn ($query) => $query->where('adviser_id', $teacher->id))
            ->when(! $request->boolean('show_archived'), function ($query): void {
                $query->whereHas('student', fn ($studentQuery) => $studentQuery->where(function ($archiveQuery): void {
                    $archiveQuery->whereNull('archived')->orWhere('archived', false);
                }));
            })
            ->when($request->filled('gender'), fn ($query) => $query->whereHas(
                'student',
                fn ($studentQuery) => $studentQuery->where('gender', $request->string('gender')->toString())
            ))
            ->when(trim($request->string('search')->toString()) !== '', function ($query) use ($request): void {
                $terms = preg_split('/\s+/u', trim($request->string('search')->toString())) ?: [];

                $query->whereHas('student', function ($studentQuery) use ($terms): void {
                    foreach ($terms as $term) {
                        $search = '%'.$term.'%';

                        $studentQuery->where(function ($termQuery) use ($search): void {
                            $termQuery
                                ->where('lrn', 'like', $search)
                                ->orWhere('firstname', 'like', $search)
                                ->orWhere('lastname', 'like', $search)
                                ->orWhere('middlename', 'like', $search);
                        });
                    }
                });
            });

        $studentCount = (clone $classStudentsQuery)->count();
        $maleCount = (clone $classStudentsQuery)->where('students.gender', 'Male')->count();
        $femaleCount = (clone $classStudentsQuery)->where('students.gender', 'Female')->count();
        $studentIds = (clone $classStudentsQuery)->pluck('class_students.student_id')->unique();

        $attendanceStudentIds = $portalContext === 'instructor'
            ? $collegeGradeRecords
                ->when(
                    $selectedCollegeClass,
                    fn ($records) => $records->where('offering_id', $selectedCollegeClass->id)
                )
                ->pluck('enrollment.student_id')
                ->filter()
                ->unique()
                ->values()
            : $studentIds;

        $students = $classStudentsQuery
            ->orderBy('students.lastname')
            ->orderBy('students.firstname')
            ->paginate(10)
            ->withQueryString();

        $grades = ClassStudentGrade::query()
            ->with(['student', 'subject'])
            ->when($selectedClass, fn ($query) => $query->where('class_id', $selectedClass->id))
            ->whereIn('student_id', $studentIds)
            ->get();

        $attendance = AttendanceRecord::query()
            ->with('student')
            ->whereIn('student_id', $attendanceStudentIds)
            ->when(! $request->filled('attendance_from') && ! $request->filled('attendance_to'), function ($query): void {
                $query->whereDate('currentdate', now()->toDateString());
            })
            ->when(trim($request->string('attendance_search')->toString()) !== '', function ($query) use ($request): void {
                $terms = preg_split('/\s+/u', trim($request->string('attendance_search')->toString())) ?: [];

                $query->whereHas('student', function ($studentQuery) use ($terms): void {
                    foreach ($terms as $term) {
                        $search = '%'.$term.'%';

                        $studentQuery->where(function ($termQuery) use ($search): void {
                            $termQuery
                                ->where('lrn', 'like', $search)
                                ->orWhere('firstname', 'like', $search)
                                ->orWhere('lastname', 'like', $search)
                                ->orWhere('middlename', 'like', $search);
                        });
                    }
                });
            })

            ->when($request->filled('attendance_from'), function ($query) use ($request): void {
                $query->whereDate('currentdate', '>=', $request->attendance_from);
            })

            ->when($request->filled('attendance_to'), function ($query) use ($request): void {
                $query->whereDate('currentdate', '<=', $request->attendance_to);
            })

            ->orderByDesc('currentdate')
            ->paginate(10)
            ->withQueryString();

        $schedules = ClassAdviserSchedule::query()
            ->where('adviser_id', $teacher->id)
            ->when($selectedClass, fn ($query) => $query->where(function ($schedules) use ($selectedClass): void {
                $schedules
                    ->where('class_id', $selectedClass->id)
                    ->orWhere(function ($legacySchedules) use ($selectedClass): void {
                        $legacySchedules
                            ->whereNull('class_id')
                            ->where('section', $selectedClass->section);
                    });
            }))
            ->orderByRaw("CASE day WHEN 'Monday' THEN 1 WHEN 'Tuesday' THEN 2 WHEN 'Wednesday' THEN 3 WHEN 'Thursday' THEN 4 WHEN 'Friday' THEN 5 ELSE 6 END")
            ->get();

        $birthdayCelebrants = Student::query()
            ->whereIn('id', $studentIds)
            ->whereNotNull('dob')
            ->whereMonth('dob', now()->month)
            ->whereDay('dob', now()->day)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        $todayAttendanceCount = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('currentdate', now()->toDateString())
            ->distinct('student_id')
            ->count('student_id');

        $todayAttendanceMissing = max(0, $studentIds->count() - $todayAttendanceCount);
        $todayLateCount = AttendanceTardySummary::countForClassDate(
            $selectedClass,
            $studentIds,
            now()->toDateString(),
        );

        $attendanceFrom = $request->filled('attendance_from')
            ? $request->string('attendance_from')->toString()
            : (! $request->filled('attendance_to') ? now()->toDateString() : '');

        $attendanceTo = $request->filled('attendance_to')
            ? $request->string('attendance_to')->toString()
            : (! $request->filled('attendance_from') ? now()->toDateString() : '');

        $availableStudents = \App\Models\Student::query()->active()->orderBy('lastname')->orderBy('firstname')->get();
        $assignments = Assignment::query()
            ->withCount('submissions')
            ->when($selectedClass, fn ($query) => $query->where('class_id', $selectedClass->id))
            ->where('adviser_id', $teacher->id)
            ->orderByDesc('deadline')
            ->get();
        $teacherStudentDetailEditingEnabled = Setting::enabled('teacher_student_detail_editing_enabled', false);

        return view('portals.teacher.dashboard', compact(
            'teacher',
            'portalContext',
            'canUseAdviserContext',
            'canUseInstructorContext',
            'activeTab',
            'schoolYears',
            'selectedSchoolYearId',
            'classes',
            'selectedClass',
            'collegeSchedules',
            'activeCollegeSchoolYear',
            'collegeGradeRecords',
            'selectedCollegeClass',
            'filteredCollegeGradeRecords',
            'collegeGradeSearch',
            'collegeGradeStatus',
            'students',
            'grades',
            'attendance',
            'schedules',
            'birthdayCelebrants',
            'todayAttendanceCount',
            'todayAttendanceMissing',
            'todayLateCount',
            'attendanceFrom',
            'attendanceTo',
            'availableStudents',
            'assignments',
            'studentCount',
            'maleCount',
            'femaleCount',
            'teacherStudentDetailEditingEnabled',
        ));
    }

    public function profile(Request $request): View
    {
        $staffProfiles = Adviser::query()
            ->with('user')
            ->where('user_id', Auth::guard('moonshine')->id())
            ->get();
        $teacher = $staffProfiles->firstWhere('staff_type', Adviser::TYPE_TEACHER)
            ?? $staffProfiles->first();

        abort_unless($teacher, 404);

        $canUseAdviserContext = $staffProfiles->contains('staff_type', Adviser::TYPE_TEACHER);
        $canUseInstructorContext = config('school_portal.features.college_module')
            && $staffProfiles->contains(fn (Adviser $profile): bool => $profile->isCollegeInstructor());
        $portalContext = $request->string('context')->toString() === 'instructor'
            && $canUseInstructorContext
                ? 'instructor'
                : ($canUseAdviserContext ? 'adviser' : 'instructor');

        return view('portals.teacher.profile', compact(
            'teacher',
            'portalContext',
            'canUseAdviserContext',
            'canUseInstructorContext',
        ));
    }

    public function editStudent(ClassStudent $classStudent): RedirectResponse
    {
        $this->ensureTeacherOwnsClassStudent($classStudent);

        return redirect()
            ->route('teacher.dashboard', [
                'tab' => 'students',
                'class_id' => $classStudent->class_id,
                'school_year_id' => $classStudent->school_year_id,
            ])
            ->with('info', 'Use the Edit button in the students table to update notes and grade visibility.');
    }

    public function createStudent(): View
    {
        return view('portals.teacher.students.create');
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'school_year_id' => ['required', 'integer', 'exists:school_year,id'],
        ]);

        $studentIds = collect($data['student_ids'] ?? [])
            ->when(isset($data['student_id']), fn ($ids) => $ids->push($data['student_id']))
            ->filter()
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return redirect()
                ->route('teacher.dashboard', [
                    'tab' => 'students',
                    'class_id' => $data['class_id'],
                    'school_year_id' => $data['school_year_id'],
                ])
                ->withErrors(['student_ids' => 'Select at least one student to add.']);
        }

        $teacherId = Adviser::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->value('id');

        abort_unless(
            ClassesModel::query()
                ->where('id', $data['class_id'])
                ->where('adviser_id', $teacherId)
                ->exists(),
            404
        );

        foreach ($studentIds as $studentId) {
            ClassStudent::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'class_id' => $data['class_id'],
                    'school_year_id' => $data['school_year_id'],
                ],
                [
                    'hidden_grade' => false,
                    'notes' => null,
                ]
            );
        }

        return redirect()
            ->route('teacher.dashboard', [
                'tab' => 'students',
                'class_id' => $data['class_id'],
                'school_year_id' => $data['school_year_id'],
            ])
            ->with('success', $studentIds->count() === 1 ? 'Student added successfully.' : $studentIds->count().' students added successfully.');
    }

    public function updateStudent(Request $request, ClassStudent $classStudent): RedirectResponse
    {
        $this->ensureTeacherOwnsClassStudent($classStudent);
        $classStudent->loadMissing('student');

        $rules = [
            'hidden_grade' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];

        if (Setting::enabled('teacher_student_detail_editing_enabled', false)) {
            $rules = [
                ...$rules,
                'lrn' => [
                    'required',
                    'string',
                    'max:15',
                    Rule::unique('students', 'lrn')->ignore($classStudent->student_id),
                ],
                'lastname' => ['required', 'string', 'max:30'],
                'firstname' => ['required', 'string', 'max:30'],
                'middlename' => ['nullable', 'string', 'max:30'],
                'dob' => ['nullable', 'date'],
                'is_4ps_member' => ['nullable', 'boolean'],
                'height' => ['nullable', 'string', 'max:10'],
                'weight' => ['nullable', 'string', 'max:10'],
            ];
        }

        $data = $request->validate($rules);

        $classStudent->update([
            'hidden_grade' => $request->boolean('hidden_grade'),
            'notes' => $data['notes'] ?? null,
        ]);

        if (Setting::enabled('teacher_student_detail_editing_enabled', false)) {
            $classStudent->student->update([
                'lrn' => $data['lrn'],
                'lastname' => $data['lastname'],
                'firstname' => $data['firstname'],
                'middlename' => $data['middlename'] ?? null,
                'dob' => $data['dob'] ?? null,
                'is_4ps_member' => $request->boolean('is_4ps_member'),
                'height' => $data['height'] ?? null,
                'weight' => $data['weight'] ?? null,
            ]);
        }

        return redirect()
            ->route('teacher.dashboard', [
                'tab' => 'students',
                'class_id' => $classStudent->class_id,
                'school_year_id' => $classStudent->school_year_id,
            ])
            ->with('success', 'Student updated successfully.');
    }

    public function deleteStudent(ClassStudent $classStudent): RedirectResponse
    {
        $this->ensureTeacherOwnsClassStudent($classStudent);

        $classId = $classStudent->class_id;
        $schoolYearId = $classStudent->school_year_id;

        $classStudent->delete();

        return redirect()
            ->route('teacher.dashboard', [
                'tab' => 'students',
                'class_id' => $classId,
                'school_year_id' => $schoolYearId,
            ])
            ->with('success', 'Student removed from class.');
    }

    public function manageGrades(ClassStudent $classStudent): View
    {
        $this->ensureTeacherOwnsClassStudent($classStudent);

        $classStudent->load(['student', 'class.grade', 'class.classSubjects.subject']);

        $grades = ClassStudentGrade::query()
            ->with('subject')
            ->where('class_id', $classStudent->class_id)
            ->where('student_id', $classStudent->student_id)
            ->get();

        $classStudent->setRelation('grades', $grades);

        return view('portals.teacher.partials.grades-modal', compact('classStudent'));
    }

    public function saveGrades(Request $request, ClassStudent $classStudent): RedirectResponse
    {
        $this->ensureTeacherOwnsClassStudent($classStudent);

        $classStudent->loadMissing('class.grade');

        if ($classStudent->gradesAreSubmitted()) {
            return redirect()
                ->back()
                ->withErrors(['grades' => 'Grades have already been submitted and can no longer be edited.']);
        }

        $termKeys = $classStudent->termKeys();
        $termRules = [];

        foreach (['q1', 'q2', 'q3', 'q4'] as $termKey) {
            $termRules['grades.*.'.$termKey] = in_array($termKey, $termKeys, true)
                ? ['nullable', 'numeric', 'min:0', 'max:100']
                : ['nullable', 'numeric'];
        }

        $data = $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.id' => ['nullable', 'integer'],
            'grades.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'action' => ['nullable', 'string', 'in:save,submit'],
            ...$termRules,
        ]);

        $isSubmitting = ($data['action'] ?? 'save') === 'submit';

        if ($isSubmitting) {
            foreach ($data['grades'] as $index => $gradeData) {
                foreach ($termKeys as $termKey) {
                    if (! isset($gradeData[$termKey]) || $gradeData[$termKey] === '') {
                        return redirect()
                            ->back()
                            ->withErrors(['grades.'.$index.'.'.$termKey => 'Complete all configured terms before submitting final grades.']);
                    }
                }
            }
        }

        foreach ($data['grades'] as $gradeData) {
            ClassStudentGrade::updateOrCreate(
                [
                    'class_id' => $classStudent->class_id,
                    'student_id' => $classStudent->student_id,
                    'subject_id' => $gradeData['subject_id'],
                ],
                [
                    'grade_id' => $classStudent->class->grade_id,
                    'q1' => $gradeData['q1'] ?? 0,
                    'q2' => $gradeData['q2'] ?? 0,
                    'q3' => $gradeData['q3'] ?? 0,
                    'q4' => $gradeData['q4'] ?? 0,
                ]
            );
        }

        if ($isSubmitting) {
            $classStudent->forceFill([
                'grades_submitted_at' => now(),
                'grades_submitted_by' => Auth::guard('moonshine')->id(),
            ])->save();
        }

        return redirect()
            ->back()
            ->with('success', $isSubmitting ? 'Grades submitted successfully.' : 'Grades saved successfully.');
    }

    public function collegeGradesModal(CollegeEnrollmentCourse $collegeEnrollmentCourse): View
    {
        $this->ensureTeacherOwnsCollegeEnrollmentCourse($collegeEnrollmentCourse);

        $collegeEnrollmentCourse->load([
            'enrollment.student',
            'enrollment.program',
            'programCourse',
            'offering.schoolYear',
            'offering.programCourse',
        ]);

        return view(
            'portals.teacher.partials.college-grades-modal',
            compact('collegeEnrollmentCourse')
        );
    }

    public function saveCollegeGrades(
        Request $request,
        CollegeEnrollmentCourse $collegeEnrollmentCourse
    ): RedirectResponse {
        $this->ensureTeacherOwnsCollegeEnrollmentCourse($collegeEnrollmentCourse);

        if ($collegeEnrollmentCourse->gradesAreSubmitted()) {
            return redirect()
                ->back()
                ->withErrors(['grades' => 'College grades have already been submitted and can no longer be edited.']);
        }

        $data = $request->validate([
            'prelim_grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'midterm_grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'prefinal_grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'final_grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'remarks' => ['nullable', 'string', 'in:Passed,Failed,Incomplete,Dropped'],
            'action' => ['nullable', 'string', 'in:save,submit'],
        ]);

        $isSubmitting = ($data['action'] ?? 'save') === 'submit';
        $gradeFields = [
            'prelim_grade' => 'Prelim',
            'midterm_grade' => 'Midterm',
            'prefinal_grade' => 'Pre-final',
            'final_grade' => 'Final',
        ];

        if ($isSubmitting) {
            foreach ($gradeFields as $field => $label) {
                if (! isset($data[$field]) || $data[$field] === '') {
                    return redirect()
                        ->back()
                        ->withErrors([$field => "Complete the {$label} grade before submitting final grades."]);
                }
            }
        }

        $collegeEnrollmentCourse->fill([
            'prelim_grade' => $data['prelim_grade'] ?? null,
            'midterm_grade' => $data['midterm_grade'] ?? null,
            'prefinal_grade' => $data['prefinal_grade'] ?? null,
            'final_grade' => $data['final_grade'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ]);

        if ($isSubmitting) {
            $collegeEnrollmentCourse->forceFill([
                'grades_submitted_at' => now(),
                'grades_submitted_by' => Auth::guard('moonshine')->id(),
            ]);
        }

        $collegeEnrollmentCourse->save();

        return redirect()
            ->route('teacher.dashboard', [
                'context' => 'instructor',
                'tab' => 'college-grades',
                'college_class_id' => $collegeEnrollmentCourse->offering_id,
            ])
            ->with('success', $isSubmitting ? 'College grades submitted successfully.' : 'College grades saved successfully.');
    }

    public function exportStudents(Request $request): StreamedResponse
    {
        $classStudents = $this->teacherClassStudentsQuery($request)
            ->with(['student', 'class.grade', 'schoolYear'])
            ->orderBy('students.lastname')
            ->orderBy('students.firstname')
            ->get();

        $filename = 'students-export-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($classStudents): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Student Number', 'Lastname', 'Firstname', 'Middlename', 'Gender', 'Birthday', 'Class', 'School Year', 'Hide Grade', 'Notes']);

            foreach ($classStudents as $classStudent) {
                $student = $classStudent->student;
                fputcsv($handle, [
                    $student->lrn ?? '',
                    $student->lastname ?? '',
                    $student->firstname ?? '',
                    $student->middlename ?? '',
                    $student->gender ?? '',
                    $student->dob ?? '',
                    ($classStudent->class->grade->grade ?? '').' - '.($classStudent->class->section ?? ''),
                    $classStudent->schoolYear->school_year ?? '',
                    $classStudent->hidden_grade ? '1' : '0',
                    $classStudent->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportStudentQrCodes(Request $request, StudentWorkbookExporter $exporter): Response
    {
        abort_unless(\App\Models\Setting::enabled('qr_code_enabled', true), 404);

        $classStudents = $this->teacherClassStudentsQuery($request)
            ->with('student')
            ->orderBy('students.lastname')
            ->orderBy('students.firstname')
            ->get();

        return $exporter->downloadQrCodes(
            $classStudents->pluck('student')->filter()->values(),
            'student-qr-codes-'.now()->format('Ymd-His').'.pdf'
        );
    }

    public function exportStudentGrades(Request $request, StudentGradesPdfExporter $exporter): Response
    {
        $classStudents = $this->teacherClassStudentsQuery($request)
            ->with(['student', 'class.classSubjects.subject', 'grades.subject'])
            ->orderBy('students.lastname')
            ->orderBy('students.firstname')
            ->get();

        return $exporter->download(
            $classStudents,
            'student-grades-'.now()->format('Ymd-His').'.pdf'
        );
    }

    public function archiveStudents(Request $request)
    {
        $teacherId = Adviser::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->value('id');

        ClassStudent::query()
            ->whereHas('class', fn ($query) => $query->where('adviser_id', $teacherId))
            ->when($request->filled('class_id'), function ($query) use ($request): void {
                $query->where('class_id', $request->integer('class_id'));
            })
            ->update([
                'archived' => true,
            ]);

        return redirect()
            ->back()
            ->with('success', 'Students archived successfully.');
    }

    public function createSchedule(): View
    {
        return view('portals.teacher.schedules.create');
    }

    public function storeSchedule(Request $request)
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'day' => ['required', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])],
            'time_frame' => ['required', 'string', 'max:100'],
        ]);

        $teacherId = Adviser::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->value('id');

        abort_if(blank($teacherId), 404);

        $class = ClassesModel::query()
            ->whereKey($data['class_id'])
            ->where('adviser_id', $teacherId)
            ->firstOrFail();

        ClassAdviserSchedule::create([
            'adviser_id' => $teacherId,
            'class_id' => $class->id,
            'day' => $data['day'],
            'section' => $class->section,
            'time_frame' => $data['time_frame'],
        ]);

        return redirect()
            ->route('teacher.dashboard', [
                'tab' => 'schedules',
                'class_id' => $class->id,
                'school_year_id' => $class->school_year_id,
            ])
            ->with('success', 'Schedule added successfully.');
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string'],
            'deadline' => ['required', 'date'],
            'file' => ['required', 'file', 'mimes:doc,docx,pdf', 'max:20480'],
        ]);

        $teacher = Adviser::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->firstOrFail();

        $class = ClassesModel::query()
            ->where('id', $data['class_id'])
            ->where('adviser_id', $teacher->id)
            ->where('enable_assignments', true)
            ->firstOrFail();

        $file = $request->file('file');
        $path = $file->store('assignments', 'public');

        Assignment::create([
            'class_id' => $class->id,
            'adviser_id' => $teacher->id,
            'title' => $data['title'],
            'notes' => $data['notes'] ?? null,
            'deadline' => $data['deadline'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
        ]);

        return redirect()
            ->route('teacher.dashboard', ['tab' => 'assignments', 'class_id' => $class->id, 'school_year_id' => $class->school_year_id])
            ->with('success', 'Assignment created as draft. Use Send To Class when students should view it.');
    }

    public function updateAssignment(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->ensureTeacherOwnsAssignment($assignment);
        $assignment->load('class');

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
            'deadline' => ['required', 'date'],
        ]);

        $newDeadline = Carbon::parse($data['deadline']);

        if ($assignment->deadline && $newDeadline->lt($assignment->deadline)) {
            return redirect()
                ->route('teacher.dashboard', [
                    'tab' => 'assignments',
                    'class_id' => $assignment->class_id,
                    'school_year_id' => $assignment->class?->school_year_id,
                ])
                ->withErrors(['deadline' => 'Due date can only be extended.'])
                ->withInput();
        }

        $assignment->update([
            'notes' => $data['notes'] ?? null,
            'deadline' => $newDeadline,
        ]);

        return redirect()
            ->route('teacher.dashboard', [
                'tab' => 'assignments',
                'class_id' => $assignment->class_id,
                'school_year_id' => $assignment->class?->school_year_id,
            ])
            ->with('success', 'Assignment updated successfully.');
    }

    public function deleteAssignment(Assignment $assignment): RedirectResponse
    {
        $this->ensureTeacherOwnsAssignment($assignment);
        $assignment->load('class');

        if ($assignment->submissions()->exists()) {
            return redirect()
                ->route('teacher.dashboard', [
                    'tab' => 'assignments',
                    'class_id' => $assignment->class_id,
                    'school_year_id' => $assignment->class?->school_year_id,
                ])
                ->withErrors(['assignment' => 'Assignment cannot be deleted because submissions already exist.']);
        }

        $classId = $assignment->class_id;
        $schoolYearId = $assignment->class?->school_year_id;

        if ($assignment->file_path) {
            Storage::disk('public')->delete($assignment->file_path);
        }

        $assignment->delete();

        return redirect()
            ->route('teacher.dashboard', [
                'tab' => 'assignments',
                'class_id' => $classId,
                'school_year_id' => $schoolYearId,
            ])
            ->with('success', 'Assignment deleted successfully.');
    }

    public function sendAssignment(Assignment $assignment): RedirectResponse
    {
        $this->ensureTeacherOwnsAssignment($assignment);
        $assignment->load(['class.grade', 'class.schoolYear']);

        if (! $assignment->sent_at) {
            $assignment->update([
                'sent_at' => now(),
            ]);

            $studentIds = ClassStudent::query()
                ->where('class_id', $assignment->class_id)
                ->pluck('student_id')
                ->unique();

            foreach ($studentIds as $studentId) {
                Notification::create([
                    'id' => (string) Str::uuid(),
                    'type' => 'assignment.sent',
                    'notifiable_type' => Student::class,
                    'notifiable_id' => $studentId,
                    'data' => json_encode([
                        'assignment_id' => $assignment->id,
                        'title' => $assignment->title,
                        'deadline' => $assignment->deadline?->toDateTimeString(),
                        'class' => trim(($assignment->class?->grade?->grade ?? '').' - '.($assignment->class?->section ?? '')),
                        'message' => 'New assignment posted.',
                    ]),
                ]);
            }
        }

        return redirect()
            ->route('teacher.dashboard', [
                'tab' => 'assignments',
                'class_id' => $assignment->class_id,
                'school_year_id' => $assignment->class?->school_year_id,
            ])
            ->with('success', 'Assignment sent to class successfully.');
    }

    public function assignmentSummary(Assignment $assignment): View
    {
        $this->ensureTeacherOwnsAssignment($assignment);

        $assignment->load(['class.grade', 'class.schoolYear']);

        $classStudents = ClassStudent::query()
            ->with('student')
            ->where('class_id', $assignment->class_id)
            ->orderBy(
                Student::query()
                    ->select('lastname')
                    ->whereColumn('students.id', 'class_students.student_id')
                    ->limit(1)
            )
            ->get();

        $submissions = AssignmentSubmission::query()
            ->with('student')
            ->where('assignment_id', $assignment->id)
            ->get()
            ->keyBy('student_id');

        return view('portals.teacher.partials.assignment-summary', compact('assignment', 'classStudents', 'submissions'));
    }

    public function downloadAssignment(Assignment $assignment): BinaryFileResponse
    {
        $this->ensureTeacherOwnsAssignment($assignment);

        return response()->download(
            Storage::disk('public')->path($assignment->file_path),
            $assignment->file_name
        );
    }

    public function downloadSubmission(AssignmentSubmission $submission): BinaryFileResponse
    {
        $submission->load('assignment');
        $this->ensureTeacherOwnsAssignment($submission->assignment);

        return response()->download(
            Storage::disk('public')->path($submission->file_path),
            $submission->file_name
        );
    }

    private function selectedSchoolYearId(Request $request, $schoolYears): ?int
    {
        $requested = $request->integer('school_year_id');

        if ($requested > 0 && $schoolYears->contains('id', $requested)) {
            return $requested;
        }

        return $schoolYears->firstWhere('active', true)?->id
            ?? $schoolYears->first()?->id;
    }

    private function selectedClass(Request $request, $classes): ?ClassesModel
    {
        $requested = $request->integer('class_id');

        if ($requested > 0) {
            return $classes->firstWhere('id', $requested) ?? $classes->first();
        }

        return $classes->first();
    }

    public function gradesModal(int $id): View
    {
        $classStudent = $this->teacherClassStudentsQuery(request())
            ->with(['student', 'class.grade', 'class.classSubjects.subject'])
            ->findOrFail($id);

        $grades = ClassStudentGrade::query()
            ->with('subject')
            ->where('class_id', $classStudent->class_id)
            ->where('student_id', $classStudent->student_id)
            ->get();

        $classStudent->setRelation('grades', $grades);

        return view('portals.teacher.partials.grades-modal', compact('classStudent'));
    }

    private function teacherClassStudentsQuery(Request $request)
    {
        $teacher = Adviser::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->firstOrFail();

        return ClassStudent::query()
            ->select('class_students.*')
            ->join('classes', 'classes.id', '=', 'class_students.class_id')
            ->join('students', 'students.id', '=', 'class_students.student_id')
            ->where('classes.adviser_id', $teacher->id)
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_students.class_id', $request->integer('class_id')))
            ->when($request->filled('school_year_id'), fn ($query) => $query->where('class_students.school_year_id', $request->integer('school_year_id')));
    }

    private function ensureTeacherOwnsClassStudent(ClassStudent $classStudent): void
    {
        $teacherId = Adviser::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->value('id');

        abort_unless($classStudent->class()->where('adviser_id', $teacherId)->exists(), 404);
    }

    private function ensureTeacherOwnsAssignment(Assignment $assignment): void
    {
        $teacherId = Adviser::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->value('id');

        abort_unless((int) $assignment->adviser_id === (int) $teacherId, 404);
    }

    private function ensureTeacherOwnsCollegeEnrollmentCourse(
        CollegeEnrollmentCourse $collegeEnrollmentCourse
    ): void {
        $collegeEnrollmentCourse->loadMissing('offering');

        $ownsOffering = Adviser::query()
            ->whereKey($collegeEnrollmentCourse->offering?->instructor_id)
            ->where('user_id', Auth::guard('moonshine')->id())
            ->where(function ($query): void {
                $query
                    ->where('staff_type', Adviser::TYPE_INSTRUCTOR)
                    ->orWhere(function ($teacher): void {
                        $teacher
                            ->where('staff_type', Adviser::TYPE_TEACHER)
                            ->where('is_college_instructor', true);
                    });
            })
            ->exists();

        abort_unless($ownsOffering, 404);
    }
}
