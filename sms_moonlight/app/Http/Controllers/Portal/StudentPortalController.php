<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\ClassStudent;
use App\Models\ClassStudentGrade;
use App\Models\CollegeEnrollment;
use App\Models\CollegeEnrollmentCourse;
use App\Models\CollegeProgramCourse;
use App\Models\Notification;
use App\Models\QuizGroupDay;
use App\Models\Student;
use App\Models\StudentQuizAnswer;
use App\Services\OperationalFileStorage;
use App\Support\StudentAcademicContextResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class StudentPortalController extends Controller
{
    public function dashboard(Request $request, StudentAcademicContextResolver $academicContextResolver): View
    {
        $quizModuleEnabled = (bool) config('school_portal.features.quiz_module');
        $paymentsModuleEnabled = (bool) config('school_portal.features.payments_module');

        $studentRelations = [
            'classStudents.class.grade',
            'classStudents.class.adviser',
            'classStudents.class.assignments.submissions',
            'classStudents.schoolYear',
            'classStudents.class.classSubjects.subject',
            'classStudentGrades.class.grade',
            'classStudentGrades.subject',
            'attendanceRecords',
        ];

        if ($quizModuleEnabled) {
            $studentRelations = [
                ...$studentRelations,
                'studentQuizAnswers.quizGroupDay.quizGroup.schoolYear',
                'studentQuizAnswers.quizGroupDay.quizGroup.grade',
                'studentQuizAnswers.quiz',
                'studentQuizAnswers.answer',
            ];
        }

        if ($paymentsModuleEnabled) {
            $studentRelations[] = 'paymentHistories.paymentType';
        }

        $student = Student::query()
            ->with($studentRelations)
            ->where('user_id', Auth::guard('moonshine')->id())
            ->firstOrFail();

        $classSearch = trim((string) $request->string('class_search')->toString());
        $classes = $student->classStudents
            ->sortByDesc('school_year_id')
            ->sortByDesc('id')
            ->values();

        if ($classSearch !== '') {
            $classNeedle = mb_strtolower($classSearch);
            $classes = $classes
                ->filter(function (ClassStudent $classStudent) use ($classNeedle): bool {
                    $class = $classStudent->class;
                    $searchable = collect([
                        $class?->grade?->grade,
                        $class?->section,
                        $class?->adviser?->name,
                        $classStudent->schoolYear?->school_year,
                        $class?->schoolYear?->school_year,
                    ])->filter()->implode(' ');

                    return str_contains(mb_strtolower($searchable), $classNeedle);
                })
                ->values();
        }

        $academicContext = $academicContextResolver->resolve($student);
        $activeClassStudent = $academicContext->highSchoolClass;
        $collegeEnrollment = $academicContext->collegeEnrollment;

        $collegeEnrollmentHistory = config('school_portal.features.college_module')
            ? CollegeEnrollment::query()
                ->with([
                    'program',
                    'schoolYear',
                    'courses.programCourse',
                    'courses.offering.instructor',
                ])
                ->where('student_id', $student->id)
                ->orderByDesc('school_year_id')
                ->orderByDesc('year_level')
                ->orderByDesc('semester')
                ->orderByDesc('id')
                ->get()
            : collect();

        $requestedHistoryEnrollmentId = $request->integer('history_enrollment');
        $historyEnrollment = $collegeEnrollmentHistory
            ->firstWhere('id', $requestedHistoryEnrollmentId)
            ?? $collegeEnrollmentHistory->first(
                fn (CollegeEnrollment $enrollment): bool => (int) $enrollment->id !== (int) $collegeEnrollment?->id
            )
            ?? $collegeEnrollmentHistory->first();

        $collegeCourses = $collegeEnrollment?->courses
            ->sortBy(fn ($course) => $course->programCourse?->course_order)
            ->values() ?? collect();
        $collegeSemesterName = $collegeEnrollment
            ? (CollegeProgramCourse::SEMESTERS[$collegeEnrollment->semester] ?? 'Semester')
            : null;

        if ($classSearch !== '') {
            $classNeedle = mb_strtolower($classSearch);
            $collegeCourses = $collegeCourses
                ->filter(function ($collegeCourse) use ($classNeedle, $collegeSemesterName): bool {
                    $offering = $collegeCourse->offering;
                    $programClass = $collegeCourse->programCourse;
                    $searchable = collect([
                        $offering?->section,
                        $programClass?->course_code,
                        $programClass?->description,
                        $offering?->instructor?->name,
                        $offering?->schedule,
                        $offering?->room,
                        $collegeSemesterName,
                    ])->filter()->implode(' ');

                    return str_contains(mb_strtolower($searchable), $classNeedle);
                })
                ->values();
        }

        $grades = ClassStudentGrade::query()
            ->with('subject')
            ->where('student_id', $student->id)
            ->whereHas('class.classStudents', function ($query) use ($student): void {
                $query
                    ->where('student_id', $student->id)
                    ->whereNotNull('grades_submitted_at')
                    ->where(function ($visibility): void {
                        $visibility->whereNull('hidden_grade')->orWhere('hidden_grade', false);
                    });
            })
            ->orderByDesc('updated_at')
            ->get();
        $quizzes = $quizModuleEnabled
            ? $student->studentQuizAnswers->sortByDesc('created_at')->values()
            : collect();

        $attendanceSearch = trim((string) $request->string('attendance_search')->toString());
        $attendance = AttendanceRecord::query()
            ->with('student')
            ->where('student_id', $student->id)
            ->when($attendanceSearch !== '', function ($query) use ($attendanceSearch): void {
                $needle = '%'.$attendanceSearch.'%';

                $query->where(function ($subQuery) use ($needle): void {
                    $subQuery
                        ->where('currentdate', 'like', $needle)
                        ->orWhere('logged_time', 'like', $needle);
                });
            })
            ->orderByDesc('currentdate')
            ->orderByDesc('logged_time')
            ->paginate(10)
            ->withQueryString();

        $todayQuizzes = collect();

        if ($quizModuleEnabled && $activeClassStudent?->class) {
            $todayQuizzes = QuizGroupDay::query()
                ->with(['quizGroup.grade', 'quizGroup.schoolYear', 'quiz_quiz_group_days.quiz.quizQuizAnswers.answer'])
                ->where('day', now()->format('l'))
                ->whereHas('quizGroup', function ($query) use ($activeClassStudent): void {
                    $query
                        ->where('grade_id', $activeClassStudent->class->grade_id)
                        ->where('school_year_id', $activeClassStudent->school_year_id);
                })
                ->orderBy('id')
                ->get();
        }

        $birthdayCelebrants = collect();

        if ($activeClassStudent?->class) {
            $birthdayCelebrants = Student::query()
                ->whereHas('classStudents', function ($query) use ($activeClassStudent): void {
                    $query
                        ->where('class_id', $activeClassStudent->class_id)
                        ->where('school_year_id', $activeClassStudent->school_year_id);
                })
                ->whereNotNull('dob')
                ->whereMonth('dob', now()->month)
                ->whereDay('dob', now()->day)
                ->orderBy('lastname')
                ->orderBy('firstname')
                ->get();
        }

        $assignments = $academicContext->isHighSchool()
            ? Assignment::query()
                ->with(['class.grade', 'class.schoolYear', 'submissions' => fn ($query) => $query->where('student_id', $student->id)])
                ->where('class_id', $activeClassStudent?->class_id)
                ->whereHas('class', fn ($query) => $query->where('enable_assignments', true))
                ->whereNotNull('sent_at')
                ->orderByDesc('deadline')
                ->get()
            : collect();

        $paymentSearch = trim((string) $request->string('payment_search')->toString());
        $paymentHistories = $paymentsModuleEnabled
            ? $student->paymentHistories->sortByDesc('payment_date')->values()
            : collect();

        if ($paymentsModuleEnabled && $paymentSearch !== '') {
            $paymentNeedle = mb_strtolower($paymentSearch);
            $paymentHistories = $paymentHistories
                ->filter(function ($payment) use ($paymentNeedle): bool {
                    $searchable = collect([
                        $payment->paymentType?->name,
                        $payment->payment_date?->format('Y-m-d F j, Y g:i A'),
                        $payment->reference,
                        $payment->notes,
                        $payment->amount,
                    ])->filter()->implode(' ');

                    return str_contains(mb_strtolower($searchable), $paymentNeedle);
                })
                ->values();
        }

        $assignmentNotifications = $academicContext->isHighSchool()
            ? Notification::query()
                ->where('type', 'assignment.sent')
                ->where('notifiable_type', Student::class)
                ->where('notifiable_id', $student->id)
                ->whereNull('read_at')
                ->orderByDesc('created_at')
                ->take(5)
                ->get()
                ->map(function (Notification $notification): Notification {
                    $notification->decoded_data = json_decode((string) $notification->data, true) ?: [];

                    return $notification;
                })
            : collect();

        $dashboardAnnouncements = Announcement::query()
            ->forAudience('students')
            ->active()
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        return view('portals.student.dashboard', compact(
            'student',
            'classes',
            'academicContext',
            'activeClassStudent',
            'collegeEnrollment',
            'collegeEnrollmentHistory',
            'historyEnrollment',
            'collegeCourses',
            'collegeSemesterName',
            'grades',
            'quizzes',
            'attendance',
            'todayQuizzes',
            'birthdayCelebrants',
            'assignments',
            'assignmentNotifications',
            'dashboardAnnouncements',
            'quizModuleEnabled',
            'paymentsModuleEnabled',
            'paymentHistories'
        ));
    }

    public function profile(): View
    {
        $student = Student::query()
            ->with('user')
            ->where('user_id', Auth::guard('moonshine')->id())
            ->firstOrFail();

        return view('portals.student.profile', compact('student'));
    }

    public function gradesModal(ClassStudent $classStudent): View
    {
        $student = Student::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->firstOrFail();

        abort_unless($classStudent->student_id === $student->id, 404);
        abort_if($classStudent->hidden_grade || ! $classStudent->gradesAreSubmitted(), 404);

        $classStudent->load(['student', 'class.grade', 'class.classSubjects.subject', 'schoolYear']);

        $grades = ClassStudentGrade::query()
            ->with('subject')
            ->where('class_id', $classStudent->class_id)
            ->where('student_id', $classStudent->student_id)
            ->get();

        $classStudent->setRelation('grades', $grades);

        return view('portals.student.partials.grades-modal', compact('classStudent'));
    }

    public function collegeGradesModal(CollegeEnrollmentCourse $collegeEnrollmentCourse): View
    {
        $student = Student::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->firstOrFail();

        $collegeEnrollmentCourse->load([
            'enrollment',
            'programCourse',
            'offering.instructor',
        ]);

        abort_unless(
            (int) $collegeEnrollmentCourse->enrollment?->student_id === (int) $student->id,
            404
        );

        return view(
            'portals.student.partials.college-grades-modal',
            compact('collegeEnrollmentCourse')
        );
    }

    public function downloadGrades(ClassStudent $classStudent): Response
    {
        $student = Student::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->firstOrFail();

        abort_unless($classStudent->student_id === $student->id, 404);
        abort_if($classStudent->hidden_grade || ! $classStudent->gradesAreSubmitted(), 404);

        $classStudent->load(['student', 'class.grade', 'class.classSubjects.subject', 'schoolYear', 'grades.subject']);

        $pdf = Pdf::loadView('portals.student.exports.grades', [
            'classStudent' => $classStudent,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->download('student-grades-'.now()->format('Ymd-His').'.pdf');
    }

    public function submitAssignment(Request $request, Assignment $assignment, OperationalFileStorage $files): RedirectResponse
    {
        $student = Student::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->firstOrFail();

        abort_unless($this->studentCanAccessAssignment($student, $assignment), 404);

        if ($assignment->deadline?->isPast()) {
            return back()->withErrors([
                'file' => 'The assignment deadline has passed.',
            ]);
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
            'file' => ['required', 'file', 'mimes:doc,docx,pdf', 'max:20480'],
        ]);

        $file = $request->file('file');
        $path = $files->store($file, 'assignment-submissions');

        $existing = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        try {
            AssignmentSubmission::updateOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'student_id' => $student->id,
                ],
                [
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'notes' => $data['notes'] ?? null,
                    'submitted_at' => now(),
                ]
            );
        } catch (\Throwable $exception) {
            $files->delete($path);

            throw $exception;
        }

        if ($existing && $existing->file_path !== $path) {
            $files->delete($existing->file_path);
        }

        return redirect()
            ->route('student.dashboard', ['tab' => 'assignments'])
            ->with('success', 'Assignment submitted successfully.');
    }

    public function downloadAssignment(Assignment $assignment, OperationalFileStorage $files): BinaryFileResponse
    {
        $student = Student::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->firstOrFail();

        abort_unless($this->studentCanAccessAssignment($student, $assignment), 404);

        return $files->download($assignment->file_path, $assignment->file_name);
    }

    public function downloadSubmission(AssignmentSubmission $submission, OperationalFileStorage $files): BinaryFileResponse
    {
        $student = Student::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->firstOrFail();

        abort_unless((int) $submission->student_id === (int) $student->id, 404);

        return $files->download($submission->file_path, $submission->file_name);
    }

    public function submitQuiz(Request $request, QuizGroupDay $quizGroupDay): RedirectResponse
    {
        abort_unless((bool) config('school_portal.features.quiz_module'), 404);

        $student = Student::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->firstOrFail();

        abort_unless($this->studentCanAccessQuizGroupDay($student, $quizGroupDay), 404);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'exists:quiz_answers,id'],
        ]);

        $quizGroupDay->load('quiz_quiz_group_days.quiz.quizQuizAnswers');

        $questionIds = $quizGroupDay->quiz_quiz_group_days
            ->pluck('quiz_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $submittedQuestionIds = collect(array_keys($data['answers']))
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($questionIds->isEmpty()
            || $submittedQuestionIds->count() !== $questionIds->count()
            || $submittedQuestionIds->diff($questionIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => 'Answer every question in this quiz before submitting.',
            ]);
        }

        if (StudentQuizAnswer::query()
            ->where('quiz_group_days_id', $quizGroupDay->id)
            ->where('student_id', $student->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'answers' => 'This quiz has already been submitted.',
            ]);
        }

        $answers = [];

        foreach ($quizGroupDay->quiz_quiz_group_days as $quizLink) {
            $quizId = (int) $quizLink->quiz_id;
            $answerId = (int) ($data['answers'][$quizId] ?? 0);

            $answerBelongsToQuiz = $quizLink->quiz?->quizQuizAnswers
                ->contains(fn ($item): bool => (int) $item->answer_id === $answerId) ?? false;

            if (! $answerBelongsToQuiz) {
                throw ValidationException::withMessages([
                    "answers.{$quizId}" => 'Select a valid answer for this question.',
                ]);
            }

            $answers[] = [
                'quiz_group_days_id' => $quizGroupDay->id,
                'quiz_id' => $quizId,
                'student_id' => $student->id,
                'answer_id' => $answerId,
            ];
        }

        try {
            DB::transaction(function () use ($answers): void {
                foreach ($answers as $answer) {
                    StudentQuizAnswer::create($answer);
                }
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages([
                    'answers' => 'This quiz has already been submitted.',
                ]);
            }

            throw $exception;
        }

        return redirect()
            ->route('student.dashboard', ['tab' => 'quiz'])
            ->with('success', 'Quiz submitted successfully.');
    }

    private function studentCanAccessAssignment(Student $student, Assignment $assignment): bool
    {
        $academicContext = app(StudentAcademicContextResolver::class)->resolve($student);

        if (! $academicContext->isHighSchool()
            || (int) $academicContext->highSchoolClass?->class_id !== (int) $assignment->class_id) {
            return false;
        }

        return Assignment::query()
            ->whereKey($assignment->id)
            ->whereNotNull('sent_at')
            ->whereHas('class', fn ($query) => $query->where('enable_assignments', true))
            ->exists();
    }

    private function studentCanAccessQuizGroupDay(Student $student, QuizGroupDay $quizGroupDay): bool
    {
        $quizGroupDay->loadMissing('quizGroup');
        $academicContext = app(StudentAcademicContextResolver::class)->resolve($student);

        if (! $academicContext->isHighSchool()) {
            return false;
        }

        return (int) $academicContext->highSchoolClass?->class?->grade_id === (int) $quizGroupDay->quizGroup?->grade_id
            && (int) $academicContext->highSchoolClass?->school_year_id === (int) $quizGroupDay->quizGroup?->school_year_id;
    }
}
