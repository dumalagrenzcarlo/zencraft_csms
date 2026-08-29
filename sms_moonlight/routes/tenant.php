<?php

use App\Http\Controllers\Admin\CollegeEnrollmentImportController;
use App\Http\Controllers\Admin\CollegeExportController;
use App\Http\Controllers\Admin\PaymentAuthorizationController;
use App\Http\Controllers\Admin\PaymentExportController;
use App\Http\Controllers\Admin\QuizGroupDayQuestionSortController;
use App\Http\Controllers\Admin\QuizGroupScoreController;
use App\Http\Controllers\Admin\StaffAttendanceExportController;
use App\Http\Controllers\Admin\StudentArchiveController;
use App\Http\Controllers\Admin\StudentImportExportController;
use App\Http\Controllers\Auth\StudentAuthController;
use App\Http\Controllers\Auth\TeacherAuthController;
use App\Http\Controllers\Portal\StudentPortalController;
use App\Http\Controllers\Portal\TeacherPortalController;
use App\Models\Adviser;
use App\Models\StudentAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

$portalForHost = static function (string $host): ?string {
    foreach (config('school_portal.domains') as $portal => $domain) {
        if ($domain && strcasecmp($host, $domain) === 0) {
            return $portal;
        }

        if (str_starts_with($host, $portal.'.')) {
            return $portal;
        }
    }

    return null;
};

$registerPortalRoutes = static function (string $portal, Closure $routes): void {
    // Every school owns dynamic portal domains (for example
    // teacher.school.example.com). Keeping the routes path-prefixed lets the
    // same route set work on those domains and on approved custom domains.
    Route::prefix($portal)
        ->middleware('portal.domain')
        ->group($routes);
};

$serveUpload = static function (string $path) {
    $path = ltrim(str_replace('\\', '/', $path), '/');

    if ($path === '' || str_contains($path, '..')) {
        abort(404);
    }

    $privatePrefixes = [
        'assignments/',
        'assignment-submissions/',
        'application-updates/',
    ];

    foreach ($privatePrefixes as $prefix) {
        abort_if(str_starts_with(strtolower($path), $prefix), 404);
    }

    $candidates = [
        Storage::disk('public')->path($path),
        public_path('uploads/'.$path),
        storage_path('app/public/'.$path),
    ];

    foreach ($candidates as $candidate) {
        $realPath = realpath($candidate);

        if ($realPath !== false && is_file($realPath)) {
            return response()->file($realPath);
        }
    }

    abort(404);
};

Route::middleware([
    'web',
    'tenant.context',
    'tenant.active',
    'portal.domain',
])->group(function () use ($serveUpload, $portalForHost, $registerPortalRoutes): void {
    Route::get('/uploaded-files/{path}', $serveUpload)
        ->where('path', '.*')
        ->name('uploaded-files.show');

    Route::get('/uploads/{path}', $serveUpload)
        ->where('path', '.*')
        ->name('uploads.fallback');

    Route::middleware('portal.detect')->get('/', function () use ($portalForHost) {

        $host = request()->getHost();
        $user = Auth::guard('moonshine')->user();
        $portal = $portalForHost($host);

        if ($portal === 'student') {

            if (! $user) {
                return redirect()->route('student.login');
            }

            $hasAccess = StudentAccess::where('user_id', $user->id)
                ->activeForPortal()
                ->exists();

            return $hasAccess
                ? redirect()->route('student.dashboard')
                : redirect()->route('student.login');
        }

        if ($portal === 'teacher') {

            if (! $user) {
                return redirect()->route('teacher.login');
            }

            $isTeacher = Adviser::where('user_id', $user->id)
                ->whereIn('staff_type', [Adviser::TYPE_TEACHER, Adviser::TYPE_INSTRUCTOR])
                ->exists();

            return $isTeacher
                ? redirect()->route('teacher.dashboard')
                : redirect()->route('teacher.login');
        }

        if ($portal === 'admin') {
            return redirect()->route('moonshine.index');
        }

        return view('welcome');
    })->name('portal.selection');

    $studentRoutes = function (): void {

        Route::get('/login', [StudentAuthController::class, 'showLoginForm'])
            ->name('student.login');

        Route::post('/login', [StudentAuthController::class, 'login'])
            ->name('student.login.submit');

        Route::middleware(['auth:moonshine', 'student.auth', 'portal.password.changed:student'])->group(function (): void {

            Route::get('/dashboard', [StudentPortalController::class, 'dashboard'])
                ->name('student.dashboard');

            Route::get('/profile', [StudentPortalController::class, 'profile'])
                ->name('student.profile');

            Route::get('/classes/{classStudent}/grades-modal', [StudentPortalController::class, 'gradesModal'])
                ->name('student.classes.grades.modal');

            Route::middleware('feature:college_module')->group(function (): void {
                Route::get('/college-classes/{collegeEnrollmentCourse}/grades-modal', [StudentPortalController::class, 'collegeGradesModal'])
                    ->name('student.college-classes.grades.modal');
            });

            Route::get('/classes/{classStudent}/download-grades', [StudentPortalController::class, 'downloadGrades'])
                ->name('student.classes.grades.download');

            Route::get('/assignments/{assignment}/download', [StudentPortalController::class, 'downloadAssignment'])
                ->name('student.assignments.download');

            Route::post('/assignments/{assignment}/submit', [StudentPortalController::class, 'submitAssignment'])
                ->name('student.assignments.submit');

            Route::get('/assignment-submissions/{submission}/download', [StudentPortalController::class, 'downloadSubmission'])
                ->name('student.assignment-submissions.download');

            Route::middleware('feature:quiz_module')->group(function (): void {
                Route::post('/quiz-group-days/{quizGroupDay}/submit', [StudentPortalController::class, 'submitQuiz'])
                    ->name('student.quiz.submit');
            });

            Route::get('/change-password', [StudentAuthController::class, 'showChangePasswordForm'])
                ->name('student.password.form');

            Route::post('/change-password', [StudentAuthController::class, 'changePassword'])
                ->name('student.password.update');

            Route::post('/logout', [StudentAuthController::class, 'logout'])
                ->name('student.logout');
        });
    };

    $registerPortalRoutes('student', function () use ($studentRoutes): void {
        Route::get('/', function () {
            session(['portal_parent' => 'student']);

            $user = Auth::guard('moonshine')->user();

            if (! $user) {
                return redirect()->route('student.login');
            }

            $hasAccess = StudentAccess::where('user_id', $user->id)
                ->activeForPortal()
                ->exists();

            return $hasAccess
                ? redirect()->route('student.dashboard')
                : redirect()->route('student.login');
        });

        $studentRoutes();
    });

    $teacherRoutes = function (): void {

        Route::get('/login', [TeacherAuthController::class, 'showLoginForm'])
            ->name('teacher.login');

        Route::post('/login', [TeacherAuthController::class, 'login'])
            ->name('teacher.login.submit');

        Route::middleware(['auth:moonshine', 'teacher.auth', 'portal.password.changed:teacher'])->group(function (): void {

            Route::get('/dashboard', [TeacherPortalController::class, 'dashboard'])
                ->name('teacher.dashboard');

            Route::get('/profile', [TeacherPortalController::class, 'profile'])
                ->name('teacher.profile');

            // STUDENTS
            Route::get('/students/create', [TeacherPortalController::class, 'createStudent'])
                ->name('teacher.students.create');

            Route::post('/students/store', [TeacherPortalController::class, 'storeStudent'])
                ->name('teacher.students.store');

            Route::get('/students/export', [TeacherPortalController::class, 'exportStudents'])
                ->name('teacher.students.export');

            Route::get('/students/export-qr', [TeacherPortalController::class, 'exportStudentQrCodes'])
                ->name('teacher.students.export-qr');

            Route::get('/students/export-grades', [TeacherPortalController::class, 'exportStudentGrades'])
                ->name('teacher.students.export-grades');

            Route::post('/students/archive', [TeacherPortalController::class, 'archiveStudents'])
                ->name('teacher.students.archive');

            Route::get('/students/{classStudent}/edit', [TeacherPortalController::class, 'editStudent'])
                ->name('teacher.students.edit');

            Route::put('/students/{classStudent}/update', [TeacherPortalController::class, 'updateStudent'])
                ->name('teacher.students.update');

            Route::delete('/students/{classStudent}/delete', [TeacherPortalController::class, 'deleteStudent'])
                ->name('teacher.students.delete');

            Route::get('/students/{classStudent}/grades', [TeacherPortalController::class, 'manageGrades'])
                ->name('teacher.students.grades');

            Route::post('/students/{classStudent}/grades', [TeacherPortalController::class, 'saveGrades'])
                ->name('teacher.students.grades.save');

            Route::get('/students/{id}/grades-modal', [TeacherPortalController::class, 'gradesModal'])
                ->name('teacher.students.grades.modal');

            Route::middleware('feature:college_module')->group(function (): void {
                Route::get('/college-grades/{collegeEnrollmentCourse}', [TeacherPortalController::class, 'collegeGradesModal'])
                    ->name('teacher.college-grades.modal');

                Route::post('/college-grades/{collegeEnrollmentCourse}', [TeacherPortalController::class, 'saveCollegeGrades'])
                    ->name('teacher.college-grades.save');
            });

            Route::post('/assignments', [TeacherPortalController::class, 'storeAssignment'])
                ->name('teacher.assignments.store');

            Route::put('/assignments/{assignment}', [TeacherPortalController::class, 'updateAssignment'])
                ->name('teacher.assignments.update');

            Route::delete('/assignments/{assignment}', [TeacherPortalController::class, 'deleteAssignment'])
                ->name('teacher.assignments.delete');

            Route::post('/assignments/{assignment}/send', [TeacherPortalController::class, 'sendAssignment'])
                ->name('teacher.assignments.send');

            Route::get('/assignments/{assignment}/summary', [TeacherPortalController::class, 'assignmentSummary'])
                ->name('teacher.assignments.summary');

            Route::get('/assignments/{assignment}/download', [TeacherPortalController::class, 'downloadAssignment'])
                ->name('teacher.assignments.download');

            Route::get('/assignment-submissions/{submission}/download', [TeacherPortalController::class, 'downloadSubmission'])
                ->name('teacher.assignment-submissions.download');

            // SCHEDULES
            Route::get('/schedules/create', [TeacherPortalController::class, 'createSchedule'])
                ->name('teacher.schedules.create');

            Route::post('/schedules/store', [TeacherPortalController::class, 'storeSchedule'])
                ->name('teacher.schedules.store');

            // PASSWORD
            Route::get('/change-password', [TeacherAuthController::class, 'showChangePasswordForm'])
                ->name('teacher.password.form');

            Route::post('/change-password', [TeacherAuthController::class, 'changePassword'])
                ->name('teacher.password.update');

            Route::post('/logout', [TeacherAuthController::class, 'logout'])
                ->name('teacher.logout');
        });
    };

    $registerPortalRoutes('teacher', function () use ($teacherRoutes): void {
        Route::get('/', function () {
            session(['portal_parent' => 'teacher']);

            $user = Auth::guard('moonshine')->user();

            if (! $user) {
                return redirect()->route('teacher.login');
            }

            $isTeacher = Adviser::where('user_id', $user->id)
                ->whereIn('staff_type', [Adviser::TYPE_TEACHER, Adviser::TYPE_INSTRUCTOR])
                ->exists();

            return $isTeacher
                ? redirect()->route('teacher.dashboard')
                : redirect()->route('teacher.login');
        });

        $teacherRoutes();
    });

    $adminRoutes = function (): void {

        Route::middleware(['moonshine.auth'])->group(function (): void {

            Route::middleware('feature:quiz_module')->group(function (): void {
                Route::get('/quiz-groups/{quizGroup}/scores', [QuizGroupScoreController::class, 'show'])
                    ->name('admin.quiz-groups.scores');

                Route::post('/quiz-group-days/{quizGroupDay}/questions/sort', [QuizGroupDayQuestionSortController::class, 'store'])
                    ->name('admin.quiz-group-days.questions.sort');
            });

            /*
            |--------------------------------------------------------------------------
            | STUDENT IMPORT / EXPORT
            |--------------------------------------------------------------------------
            */

            Route::get('/students/import', [StudentImportExportController::class, 'showImportForm'])
                ->name('admin.students.import.form');

            Route::post('/students/import', [StudentImportExportController::class, 'import'])
                ->name('admin.students.import');

            Route::get('/students/template', function () {
                return response()->download(
                    resource_path('templates/import-student-template.xlsx'),
                    'student-import-template.xlsx'
                );
            })->name('admin.students.template');

            Route::get('/students/export', [StudentImportExportController::class, 'export'])
                ->name('admin.students.export');

            Route::get('/students/export-qr', [StudentImportExportController::class, 'exportQr'])
                ->name('admin.students.export-qr');

            Route::get('/archived-students/export', [StudentImportExportController::class, 'exportArchived'])
                ->name('admin.students.archived.export');

            Route::get('/students/{student}/download-grades', [StudentImportExportController::class, 'downloadGrades'])
                ->name('admin.students.download-grades');

            Route::post('/students/{student}/archive', [StudentArchiveController::class, 'archive'])
                ->name('admin.student-archive.student');

            Route::post('/classes/{class}/archive-students', [StudentArchiveController::class, 'archiveClass'])
                ->name('admin.student-archive.class');

            Route::post('/classes/archive-students', [StudentArchiveController::class, 'archiveSelectedClass'])
                ->name('admin.student-archive.class-selected');

            Route::post('/archived-students/{student}/restore', [StudentArchiveController::class, 'restore'])
                ->name('admin.student-archive.restore');

            Route::middleware('feature:college_module')->group(function (): void {
                Route::post('/college-enrolments/import', [CollegeEnrollmentImportController::class, 'import'])
                    ->name('admin.college-enrollments.import');

                Route::get('/college-enrolments/template', [CollegeEnrollmentImportController::class, 'template'])
                    ->name('admin.college-enrollments.template');

                Route::get('/college-grades/export', [CollegeExportController::class, 'grades'])
                    ->name('admin.college-grades.export');

                Route::get('/college-class-schedules/export', [CollegeExportController::class, 'schedules'])
                    ->name('admin.college-class-schedules.export');

                Route::get('/college-courses/export', [CollegeExportController::class, 'courses'])
                    ->name('admin.college-courses.export');
            });

            Route::get('/advisers/export', [StudentImportExportController::class, 'exportAdvisers'])
                ->name('admin.advisers.export');

            Route::middleware('feature:staff_module')->group(function (): void {
                Route::get('/staff/export', [StudentImportExportController::class, 'exportStaff'])
                    ->name('admin.staff.export');
            });

            Route::middleware(['feature:staff_module', 'feature:teacher_staff_attendance'])->group(function (): void {
                Route::get('/staff-attendance/export', StaffAttendanceExportController::class)
                    ->name('admin.staff-attendance.export');
            });

            Route::middleware('feature:payments_module')->group(function (): void {
                Route::get('/payments/authorize', [PaymentAuthorizationController::class, 'show'])
                    ->name('admin.payments.authorization');

                Route::post('/payments/authorize', [PaymentAuthorizationController::class, 'authorize'])
                    ->name('admin.payments.authorize');

                Route::get('/payments/export', PaymentExportController::class)
                    ->name('admin.payments.export');
            });
        });
    };

    $registerPortalRoutes('admin', function () use ($adminRoutes): void {
        $adminRoutes();
    });
});
