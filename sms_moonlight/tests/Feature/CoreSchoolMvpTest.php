<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\TenantRouteContext;
use App\Models\Adviser;
use App\Models\ClassesModel;
use App\Models\ClassStudent;
use App\Models\ClassStudentGrade;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use MoonShine\Laravel\Models\MoonshineUser;
use Tests\TestCase;

class CoreSchoolMvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_specialized_domains_cannot_serve_another_portal(): void
    {
        $this->withoutMiddleware([
            TenantRouteContext::class,
            EnsureTenantActive::class,
        ]);

        $this->get('http://student.school.test/teacher/login')->assertNotFound();
        $this->get('http://teacher.school.test/student/login')->assertNotFound();
        $this->get('http://admin.school.test/teacher/login')->assertNotFound();

        $this->get('http://student.school.test/student/login')->assertOk();
        $this->get('http://teacher.school.test/teacher/login')->assertOk();
    }

    public function test_teacher_enrollment_uses_the_owned_class_school_year(): void
    {
        $records = $this->coreRecords();
        $otherYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2027-2028',
            'active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($records['teacher_user'], 'moonshine')
            ->post(route('teacher.students.store'), [
                'student_ids' => [$records['student']->id],
                'class_id' => $records['class']->id,
                'school_year_id' => $otherYearId,
            ]);

        $response->assertRedirect(route('teacher.dashboard', [
            'tab' => 'students',
            'class_id' => $records['class']->id,
            'school_year_id' => $records['school_year_id'],
        ]));
        $this->assertDatabaseHas('class_students', [
            'class_id' => $records['class']->id,
            'student_id' => $records['student']->id,
            'school_year_id' => $records['school_year_id'],
        ]);
    }

    public function test_teacher_can_only_grade_enrolled_students_and_assigned_subjects(): void
    {
        $records = $this->coreRecords(enrollStudent: true);
        $unassignedSubjectId = DB::table('subjects')->insertGetId([
            'subject' => 'Unassigned Subject',
            'include_in_average' => true,
            'record_order' => 2,
            'record_orders' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($records['teacher_user'], 'moonshine')
            ->post(route('teacher.students.grades.save', $records['class_student']), [
                'action' => 'save',
                'grades' => [[
                    'subject_id' => $unassignedSubjectId,
                    'q1' => 90,
                    'q2' => 90,
                    'q3' => 90,
                    'q4' => 90,
                ]],
            ])
            ->assertSessionHasErrors('grades.0.subject_id');

        $this->assertDatabaseCount('class_student_grades', 0);

        $this->expectException(ValidationException::class);

        ClassStudentGrade::query()->create([
            'class_id' => $records['class']->id,
            'student_id' => $records['other_student_id'],
            'grade_id' => $records['grade_id'],
            'subject_id' => $records['subject_id'],
            'q1' => 90,
            'q2' => 90,
            'q3' => 90,
            'q4' => 90,
        ]);
    }

    public function test_only_submitted_visible_grades_reach_the_student_portal(): void
    {
        $records = $this->coreRecords(enrollStudent: true);
        $studentGrade = ClassStudentGrade::query()->create([
            'class_id' => $records['class']->id,
            'student_id' => $records['student']->id,
            'grade_id' => $records['grade_id'],
            'subject_id' => $records['subject_id'],
            'q1' => 91,
            'q2' => 92,
            'q3' => 93,
            'q4' => 94,
        ]);

        $draftDashboard = $this->actingAs($records['student_user'], 'moonshine')
            ->get(route('student.dashboard'));

        $draftDashboard->assertOk();
        $this->assertTrue($draftDashboard->viewData('grades')->isEmpty());
        $this->get(route('student.classes.grades.modal', $records['class_student']))
            ->assertNotFound();

        $records['class_student']->forceFill([
            'grades_submitted_at' => now(),
            'grades_submitted_by' => $records['teacher_user']->id,
        ])->save();

        $releasedDashboard = $this->get(route('student.dashboard'));
        $releasedDashboard->assertOk();
        $this->assertSame([$studentGrade->id], $releasedDashboard->viewData('grades')->pluck('id')->all());
        $this->get(route('student.classes.grades.modal', $records['class_student']))
            ->assertOk()
            ->assertSee('91.00');

        $records['class_student']->update(['hidden_grade' => true]);

        $hiddenDashboard = $this->get(route('student.dashboard'));
        $hiddenDashboard->assertOk();
        $this->assertTrue($hiddenDashboard->viewData('grades')->isEmpty());
        $this->get(route('student.classes.grades.modal', $records['class_student']))
            ->assertNotFound();
        $this->get(route('student.classes.grades.download', $records['class_student']))
            ->assertNotFound();
    }

    public function test_teacher_and_student_portals_only_show_attendance_for_their_roster(): void
    {
        $records = $this->coreRecords(enrollStudent: true);
        $now = now();

        DB::table('attendance_record')->insert([
            [
                'student_id' => $records['student']->id,
                'amlogin' => '07:50:00',
                'amlogout' => '00:00:00',
                'pmlogin' => '00:00:00',
                'pmlogout' => '00:00:00',
                'currentdate' => $now->toDateString(),
                'logged_time' => '07:50:00',
                'source' => 'manual',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'student_id' => $records['other_student_id'],
                'amlogin' => '08:10:00',
                'amlogout' => '00:00:00',
                'pmlogin' => '00:00:00',
                'pmlogout' => '00:00:00',
                'currentdate' => $now->toDateString(),
                'logged_time' => '08:10:00',
                'source' => 'manual',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $studentDashboard = $this->actingAs($records['student_user'], 'moonshine')
            ->get(route('student.dashboard', ['tab' => 'attendance']));

        $studentDashboard->assertOk()->assertSee('Grade 8');
        $this->assertSame(
            [$records['student']->id],
            $studentDashboard->viewData('attendance')->pluck('student_id')->unique()->values()->all()
        );

        $teacherDashboard = $this->actingAs($records['teacher_user'], 'moonshine')
            ->get(route('teacher.dashboard', ['tab' => 'attendance']));

        $teacherDashboard->assertOk()->assertSee('Student One');
        $this->assertSame(
            [$records['student']->id],
            $teacherDashboard->viewData('attendance')->pluck('student_id')->unique()->values()->all()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function coreRecords(bool $enrollStudent = false): array
    {
        $this->role(2, 'Teacher');
        $this->role(3, 'Student');

        $teacherUser = $this->portalUser(2, 'teacher.one');
        $studentUser = $this->portalUser(3, 'student.one');
        $otherStudentUser = $this->portalUser(3, 'student.two');
        $now = now();

        $teacherId = DB::table('advisers')->insertGetId([
            'user_id' => $teacherUser->id,
            'name' => 'Teacher One',
            'rank' => 'Teacher I',
            'major' => 'Mathematics',
            'staff_type' => Adviser::TYPE_TEACHER,
            'profile_photo' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $studentId = $this->student('LRN-0001', $studentUser->id, 'Student', 'One');
        $otherStudentId = $this->student('LRN-0002', $otherStudentUser->id, 'Student', 'Two');

        DB::table('student_access')->insert([
            'student_id' => $studentId,
            'user_id' => $studentUser->id,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $schoolYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2026-2027',
            'active' => true,
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $gradeId = DB::table('grade')->insertGetId([
            'grade' => 'Grade 8',
            'status' => 'active',
            'term_count' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $subjectId = DB::table('subjects')->insertGetId([
            'subject' => 'Mathematics',
            'include_in_average' => true,
            'record_order' => 1,
            'record_orders' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $class = ClassesModel::query()->create([
            'adviser_id' => $teacherId,
            'grade_id' => $gradeId,
            'section' => 'Integrity',
            'school_year_id' => $schoolYearId,
            'start_time' => '08:00',
            'end_time' => '15:00',
            'grading_period_count' => 4,
            'status' => 'active',
            'active' => true,
            'enable_assignments' => false,
        ]);

        DB::table('class_subjects')->insert([
            'class_id' => $class->id,
            'subject_id' => $subjectId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $classStudent = $enrollStudent
            ? ClassStudent::query()->create([
                'class_id' => $class->id,
                'student_id' => $studentId,
                'school_year_id' => $schoolYearId,
                'hidden_grade' => false,
            ])
            : null;

        return [
            'teacher_user' => $teacherUser,
            'student_user' => $studentUser,
            'student' => Student::query()->findOrFail($studentId),
            'other_student_id' => $otherStudentId,
            'school_year_id' => $schoolYearId,
            'grade_id' => $gradeId,
            'subject_id' => $subjectId,
            'class' => $class,
            'class_student' => $classStudent,
        ];
    }

    private function role(int $id, string $name): void
    {
        DB::table('moonshine_user_roles')->updateOrInsert(
            ['id' => $id],
            ['name' => $name, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function portalUser(int $roleId, string $username): MoonshineUser
    {
        $user = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => $roleId,
            'username' => $username,
            'email' => $username.'@example.test',
            'name' => $username,
            'password' => Hash::make('Temporary123!'),
            'must_change_password' => false,
        ]);
        $user->save();

        return $user;
    }

    private function student(string $lrn, int $userId, string $firstName, string $lastName): int
    {
        return DB::table('students')->insertGetId([
            'user_id' => $userId,
            'lrn' => $lrn,
            'lastname' => $lastName,
            'firstname' => $firstName,
            'middlename' => '',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'address' => 'Sample City',
            'birthplace' => 'Sample City',
            'profile_photo' => '',
            'parent_guardian' => 'Guardian',
            'parent_guardian_address' => 'Sample City',
            'parent_guardian_relationship' => 'Parent',
            'is_4ps_member' => false,
            'archived' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
