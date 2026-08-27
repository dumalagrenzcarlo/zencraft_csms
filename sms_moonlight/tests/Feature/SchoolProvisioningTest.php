<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MoonshineUser;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private array $tenantsToDelete = [];

    protected function tearDown(): void
    {
        tenancy()->end();

        foreach ($this->tenantsToDelete as $tenantId) {
            Tenant::query()->find($tenantId)?->delete();
        }

        parent::tearDown();
    }

    public function test_owner_can_provision_an_isolated_school_workspace(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'active' => true]);
        $plan = $this->plan();

        $response = $this->actingAs($owner)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->post('/platform/schools', $this->schoolPayload($plan));

        $school = Tenant::query()->where('slug', 'sample-academy')->firstOrFail();
        $this->tenantsToDelete[] = $school->id;

        $response->assertRedirect(route('platform.schools.show', $school));
        $this->assertSame(4, $school->domains()->count());
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $school->id,
            'plan_id' => $plan->id,
            'status' => 'trial',
        ]);

        $school->run(function (): void {
            $this->assertDatabaseHas('moonshine_users', [
                'username' => 'admin',
                'email' => 'admin@sample.test',
                'must_change_password' => 1,
            ]);
            $this->assertSame('Sample Academy', DB::table('settings')->where('settingName', 'school_name')->value('settingValue'));
        });
    }

    public function test_school_databases_do_not_share_records(): void
    {
        $plan = $this->plan();
        $provisioner = app(SchoolProvisioner::class);
        $first = $provisioner->create($this->schoolPayload($plan));
        $second = $provisioner->create([
            ...$this->schoolPayload($plan),
            'name' => 'Second School',
            'slug' => 'second-school',
            'admin_email' => 'admin@second.test',
        ]);
        $this->tenantsToDelete = [$first->id, $second->id];

        $first->run(function (): void {
            DB::table('settings')->updateOrInsert(
                ['settingName' => 'isolation_marker'],
                ['settingValue' => 'first', 'settingType' => 'text']
            );

            $now = now();
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
            $adviserId = DB::table('advisers')->insertGetId([
                'user_id' => null,
                'name' => 'Tenant One Teacher',
                'rank' => 'Teacher I',
                'major' => 'Mathematics',
                'staff_type' => 'teacher',
                'profile_photo' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $studentId = DB::table('students')->insertGetId([
                'user_id' => null,
                'lrn' => 'TENANT-ONE',
                'lastname' => 'Student',
                'firstname' => 'First',
                'middlename' => '',
                'gender' => 'Male',
                'archived' => false,
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
            $classId = DB::table('classes')->insertGetId([
                'adviser_id' => $adviserId,
                'grade_id' => $gradeId,
                'section' => 'Tenant One',
                'school_year_id' => $schoolYearId,
                'grading_period_count' => 4,
                'status' => 'active',
                'active' => true,
                'enable_assignments' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('class_subjects')->insert([
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('class_students')->insert([
                'class_id' => $classId,
                'student_id' => $studentId,
                'school_year_id' => $schoolYearId,
                'hidden_grade' => false,
                'grades_submitted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('class_student_grades')->insert([
                'class_id' => $classId,
                'student_id' => $studentId,
                'grade_id' => $gradeId,
                'subject_id' => $subjectId,
                'q1' => 90,
                'q2' => 91,
                'q3' => 92,
                'q4' => 93,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('attendance_record')->insert([
                'student_id' => $studentId,
                'amlogin' => '07:55:00',
                'amlogout' => '00:00:00',
                'pmlogin' => '00:00:00',
                'pmlogout' => '00:00:00',
                'currentdate' => '2026-08-27',
                'logged_time' => '07:55:00',
                'source' => 'manual',
                'source_event_id' => 'tenant-one-attendance-event',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $assignmentId = DB::table('assignments')->insertGetId([
                'class_id' => $classId,
                'adviser_id' => $adviserId,
                'title' => 'Tenant One Assignment',
                'notes' => null,
                'file_path' => 'assignments/tenant-one.pdf',
                'file_name' => 'tenant-one.pdf',
                'deadline' => $now->copy()->addWeek(),
                'sent_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('assignment_submissions')->insert([
                'assignment_id' => $assignmentId,
                'student_id' => $studentId,
                'file_path' => 'assignment-submissions/tenant-one.pdf',
                'file_name' => 'tenant-one.pdf',
                'notes' => null,
                'submitted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('announcements')->insert([
                'title' => 'Tenant One Announcement',
                'content' => 'Visible only inside the first tenant.',
                'expiry_date' => $now->copy()->addWeek(),
                'target_audience' => 'both',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $programId = DB::table('college_programs')->insertGetId([
                'code' => 'TENANT-ONE-BSIT',
                'name' => 'Tenant One Information Technology',
                'duration_years' => 4,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $programCourseId = DB::table('college_curriculum_subjects')->insertGetId([
                'program_id' => $programId,
                'course_code' => 'IT101',
                'description' => 'Tenant One Computing',
                'year_level' => 1,
                'semester' => 1,
                'units' => 3,
                'course_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $offeringId = DB::table('college_course_offerings')->insertGetId([
                'school_year_id' => $schoolYearId,
                'program_course_id' => $programCourseId,
                'instructor_id' => $adviserId,
                'section' => 'Tenant One College',
                'capacity' => 40,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $collegeEnrollmentId = DB::table('college_enrollments')->insertGetId([
                'student_id' => $studentId,
                'program_id' => $programId,
                'school_year_id' => $schoolYearId,
                'semester' => 1,
                'year_level' => 1,
                'status' => 'enrolled',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('college_enrollment_courses')->insert([
                'enrollment_id' => $collegeEnrollmentId,
                'program_course_id' => $programCourseId,
                'offering_id' => $offeringId,
                'prelim_grade' => 90,
                'midterm_grade' => 91,
                'prefinal_grade' => 92,
                'final_grade' => 93,
                'remarks' => 'Passed',
                'grades_submitted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('student_payment_histories')->insert([
                'student_id' => $studentId,
                'payment_type_id' => DB::table('payment_types')->where('name', 'Cash')->value('id'),
                'payment_date' => $now,
                'amount' => 1000,
                'reference' => 'TENANT-ONE-OR',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $quizGroupId = DB::table('quiz_group')->insertGetId([
                'school_year_id' => $schoolYearId,
                'grade_id' => $gradeId,
                'week' => 'Week 1',
                'record_created' => $now,
                'record_updated' => $now,
            ]);
            $quizDayId = DB::table('quiz_group_days')->insertGetId([
                'title' => 'Tenant One Quiz',
                'quiz_group_id' => $quizGroupId,
                'day' => 'Monday',
                'quiz_duration_seconds' => 600,
                'record_created' => $now,
                'record_updated' => $now,
            ]);
            $quizId = DB::table('quizzes')->insertGetId([
                'question' => 'Tenant one question?',
                'record_created' => $now,
                'record_updated' => $now,
            ]);
            $answerId = DB::table('quiz_answers')->insertGetId([
                'answer' => 'Tenant one answer',
                'record_created' => $now,
                'record_updated' => $now,
            ]);
            DB::table('quiz_quiz_answers')->insert([
                'quiz_id' => $quizId,
                'answer_id' => $answerId,
                'is_correct_answer' => true,
                'record_created' => $now,
                'record_updated' => $now,
            ]);
            DB::table('quiz_quiz_group_days')->insert([
                'quiz_id' => $quizId,
                'quiz_group_days_id' => $quizDayId,
                'record_order' => 1,
                'record_created' => $now,
                'record_updated' => $now,
            ]);
            DB::table('student_quiz_answers')->insert([
                'quiz_group_days_id' => $quizDayId,
                'quiz_id' => $quizId,
                'answer_id' => $answerId,
                'student_id' => $studentId,
                'record_created' => $now,
                'record_updated' => $now,
            ]);
        });

        $second->run(function (): void {
            $this->assertNull(DB::table('settings')->where('settingName', 'isolation_marker')->value('settingValue'));
            $this->assertSame('admin@second.test', MoonshineUser::query()->where('username', 'admin')->value('email'));
            $this->assertSame(0, DB::table('school_year')->count());
            $this->assertSame(0, DB::table('advisers')->count());
            $this->assertSame(0, DB::table('students')->count());
            $this->assertSame(0, DB::table('classes')->count());
            $this->assertSame(0, DB::table('class_students')->count());
            $this->assertSame(0, DB::table('class_student_grades')->count());
            $this->assertSame(0, DB::table('attendance_record')->count());
            $this->assertSame(0, DB::table('assignments')->count());
            $this->assertSame(0, DB::table('assignment_submissions')->count());
            $this->assertSame(0, DB::table('announcements')->count());
            $this->assertSame(0, DB::table('college_programs')->count());
            $this->assertSame(0, DB::table('college_enrollments')->count());
            $this->assertSame(0, DB::table('college_enrollment_courses')->count());
            $this->assertSame(0, DB::table('student_payment_histories')->count());
            $this->assertSame(0, DB::table('quiz_group')->count());
            $this->assertSame(0, DB::table('student_quiz_answers')->count());
        });
    }

    public function test_suspended_school_is_blocked_before_portal_access(): void
    {
        $school = app(SchoolProvisioner::class)->create($this->schoolPayload($this->plan()));
        $this->tenantsToDelete[] = $school->id;
        $school->forceFill(['status' => Tenant::STATUS_SUSPENDED, 'suspended_at' => now()])->save();

        $this->get('http://sample-academy.localhost/')
            ->assertStatus(423);
    }

    private function plan(): Plan
    {
        return Plan::query()->firstOrCreate(
            ['slug' => 'starter'],
            ['name' => 'Starter', 'included_users' => 500, 'max_users' => 500, 'monthly_price_cents' => 500000, 'active' => true]
        );
    }

    private function schoolPayload(Plan $plan): array
    {
        return [
            'name' => 'Sample Academy',
            'slug' => 'sample-academy',
            'timezone' => 'Asia/Manila',
            'plan_id' => $plan->id,
            'admin_name' => 'School Admin',
            'admin_email' => 'admin@sample.test',
            'admin_password' => 'Temporary123!',
            'admin_password_confirmation' => 'Temporary123!',
        ];
    }
}
