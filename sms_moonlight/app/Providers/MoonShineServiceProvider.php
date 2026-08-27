<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Pages\RfidChecker;
use App\MoonShine\Pages\StaffAttendanceDashboard;
use App\MoonShine\Pages\Student;
use App\MoonShine\Pages\StudentAttendanceDashboard;
use App\MoonShine\Pages\Teacher;
use App\MoonShine\Resources\Adviser\AdviserResource;
use App\MoonShine\Resources\Announcement\AnnouncementResource;
use App\MoonShine\Resources\ArchivedStudent\ArchivedStudentResource;
use App\MoonShine\Resources\AttendanceRecord\AttendanceRecordResource;
use App\MoonShine\Resources\ClassAdviserSchedule\ClassAdviserScheduleResource;
use App\MoonShine\Resources\ClassesModel\ClassesModelResource;
use App\MoonShine\Resources\ClassStudent\ClassStudentResource;
use App\MoonShine\Resources\ClassStudentGrade\ClassStudentGradeResource;
use App\MoonShine\Resources\ClassSubject\ClassSubjectResource;
use App\MoonShine\Resources\CollegeCourseOffering\CollegeCourseOfferingResource;
use App\MoonShine\Resources\CollegeEnrollment\CollegeEnrollmentResource;
use App\MoonShine\Resources\CollegeEnrollmentCourse\CollegeEnrollmentCourseResource;
use App\MoonShine\Resources\CollegeProgram\CollegeProgramResource;
use App\MoonShine\Resources\CollegeProgramCourse\CollegeProgramCourseResource;
use App\MoonShine\Resources\CollegeStudentQuick\CollegeStudentQuickResource;
use App\MoonShine\Resources\Grade\GradeResource;
use App\MoonShine\Resources\Instructor\InstructorResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\MoonShine\Resources\Notification\NotificationResource;
use App\MoonShine\Resources\PaymentType\PaymentTypeResource;
use App\MoonShine\Resources\Quiz\QuizResource;
use App\MoonShine\Resources\QuizAnswer\QuizAnswerResource;
use App\MoonShine\Resources\QuizGroup\QuizGroupResource;
use App\MoonShine\Resources\QuizGroupDay\QuizGroupDayResource;
use App\MoonShine\Resources\QuizQuizAnswer\QuizQuizAnswerResource;
use App\MoonShine\Resources\QuizQuizGroupDay\QuizQuizGroupDayResource;
use App\MoonShine\Resources\SchoolYear\SchoolYearResource;
use App\MoonShine\Resources\Setting\SettingResource;
use App\MoonShine\Resources\Staff\StaffResource;
use App\MoonShine\Resources\Student\StudentResource;
use App\MoonShine\Resources\StudentAccess\StudentAccessResource;
use App\MoonShine\Resources\StudentClass\StudentClassResource;
use App\MoonShine\Resources\StudentDocument\StudentDocumentResource;
use App\MoonShine\Resources\StudentPaymentHistory\StudentPaymentHistoryResource;
use App\MoonShine\Resources\StudentQuizAnswer\StudentQuizAnswerResource;
use App\MoonShine\Resources\Subject\SubjectResource;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        $collegeResources = config('school_portal.features.college_module')
            ? [
                InstructorResource::class,
                CollegeProgramResource::class,
                CollegeProgramCourseResource::class,
                CollegeStudentQuickResource::class,
                CollegeCourseOfferingResource::class,
                CollegeEnrollmentResource::class,
                CollegeEnrollmentCourseResource::class,
            ]
            : [];

        $quizResources = config('school_portal.features.quiz_module')
            ? [
                QuizResource::class,
                QuizAnswerResource::class,
                QuizGroupResource::class,
                QuizGroupDayResource::class,
                QuizQuizAnswerResource::class,
                QuizQuizGroupDayResource::class,
                StudentQuizAnswerResource::class,
            ]
            : [];

        $paymentResources = config('school_portal.features.payments_module')
            ? [
                PaymentTypeResource::class,
                StudentPaymentHistoryResource::class,
            ]
            : [];

        $staffResources = config('school_portal.features.staff_module')
            ? [StaffResource::class]
            : [];

        $staffAttendancePages = config('school_portal.features.teacher_staff_attendance')
            ? [StaffAttendanceDashboard::class]
            : [];

        $core
            ->resources([
                MoonShineUserResource::class,
                MoonShineUserRoleResource::class,
                AdviserResource::class,
                ...$staffResources,
                AnnouncementResource::class,
                SubjectResource::class,
                GradeResource::class,
                ClassesModelResource::class,
                AttendanceRecordResource::class,
                ClassAdviserScheduleResource::class,
                ClassStudentResource::class,
                ClassStudentGradeResource::class,
                ClassSubjectResource::class,
                ...$collegeResources,
                NotificationResource::class,
                ...$quizResources,
                SchoolYearResource::class,
                SettingResource::class,
                ...$paymentResources,
                StudentResource::class,
                ArchivedStudentResource::class,
                StudentDocumentResource::class,
                StudentAccessResource::class,
                StudentClassResource::class,
                UserResource::class,
            ])
            ->pages([
                ...$core->getConfig()->getPages(),
                Student::class,
                Teacher::class,
                RfidChecker::class,
                StudentAttendanceDashboard::class,
                ...$staffAttendancePages,
            ]);
    }
}
