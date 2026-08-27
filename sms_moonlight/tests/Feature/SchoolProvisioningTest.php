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
                'created_at' => $now,
                'updated_at' => $now,
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
