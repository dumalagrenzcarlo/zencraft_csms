<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClassesModel;
use App\Models\ClassStudent;
use App\Models\Student;
use App\Models\StudentAccess;
use App\MoonShine\Resources\ArchivedStudent\ArchivedStudentResource;
use App\Services\StudentArchiver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use Tests\TestCase;

final class StudentArchivingTest extends TestCase
{
    use RefreshDatabase;

    public function test_archiving_a_student_disables_portal_access_and_preserves_records(): void
    {
        [$student, $user] = $this->createStudent('100000000001');

        $this->assertTrue(app(StudentArchiver::class)->archive($student));

        $this->assertDatabaseHas('students', ['id' => $student->id, 'archived' => 1]);
        $this->assertNotNull($student->fresh()->archive_date);
        $this->assertDatabaseHas('student_access', ['student_id' => $student->id, 'active' => 0]);

        $this->actingAs($user, 'moonshine')
            ->get(route('student.dashboard'))
            ->assertRedirect(route('student.login'));

        $this->assertGuest('moonshine');

        $this->post(route('student.login.submit'), [
            'lrn' => $student->lrn,
            'password' => 'student-password',
        ])->assertSessionHasErrors('lrn');
    }

    public function test_archiving_a_class_archives_each_active_student_in_that_class_only(): void
    {
        [$first] = $this->createStudent('100000000002');
        [$second] = $this->createStudent('100000000003');
        [$other] = $this->createStudent('100000000004');
        $class = $this->createClass();

        foreach ([$first, $second] as $student) {
            ClassStudent::query()->create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'school_year_id' => $class->school_year_id,
            ]);
        }

        $this->assertSame(2, app(StudentArchiver::class)->archiveClass($class));
        $this->assertTrue($first->fresh()->archived);
        $this->assertTrue($second->fresh()->archived);
        $this->assertFalse((bool) $other->fresh()->archived);
        $this->assertSame(2, ClassStudent::query()->where('class_id', $class->id)->count());
    }

    public function test_admin_can_view_archived_students_and_restore_portal_access(): void
    {
        [$student] = $this->createStudent('100000000005');
        app(StudentArchiver::class)->archive($student);
        $admin = $this->createUser(1, 'archive-admin', 'admin-password');

        $this->actingAs($admin, 'moonshine')
            ->get(app(ArchivedStudentResource::class)->getIndexPageUrl())
            ->assertOk()
            ->assertSee('Archived Students')
            ->assertSee($student->lrn)
            ->assertSee('Class to archive')
            ->assertSee('Search archived students')
            ->assertSee('Export')
            ->assertSee('js-table-builder-container', false)
            ->assertSee('filter[class_id]', false)
            ->assertSee('filter[archive_date]', false);

        $this->actingAs($admin, 'moonshine')
            ->get(route('admin.students.archived.export', ['search' => $student->lrn]))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($admin, 'moonshine')
            ->post(route('admin.student-archive.restore', $student))
            ->assertSessionHas('status');

        $this->assertFalse($student->fresh()->archived);
        $this->assertNull($student->fresh()->archive_date);
        $this->assertDatabaseHas('student_access', ['student_id' => $student->id, 'active' => 1]);
    }

    /** @return array{Student, MoonshineUser} */
    private function createStudent(string $lrn): array
    {
        $user = $this->createUser(3, $lrn, 'student-password');
        $student = new Student;
        $student->forceFill([
            'user_id' => $user->id,
            'lrn' => $lrn,
            'firstname' => 'Test',
            'lastname' => 'Student',
            'middlename' => 'Archive',
            'gender' => 'male',
            'dob' => '2010-01-01',
            'address' => 'Test Address',
            'birthplace' => 'Test City',
            'profile_photo' => '',
            'parent_guardian' => 'Test Guardian',
            'parent_guardian_address' => 'Test Address',
            'parent_guardian_relationship' => 'Parent',
            'is_4ps_member' => false,
            'archived' => false,
        ]);
        $student->saveQuietly();

        StudentAccess::query()->create([
            'student_id' => $student->id,
            'user_id' => $user->id,
            'active' => 1,
        ]);

        return [$student, $user];
    }

    private function createUser(int $roleId, string $username, string $password): MoonshineUser
    {
        if (! MoonshineUserRole::query()->whereKey($roleId)->exists()) {
            $role = new MoonshineUserRole;
            $role->forceFill([
                'id' => $roleId,
                'name' => $roleId === 1 ? 'Admin' : 'Student',
            ]);
            $role->save();
        }

        $user = new MoonshineUser;
        $user->forceFill([
            'moonshine_user_role_id' => $roleId,
            'username' => $username,
            'email' => $username.'@example.test',
            'name' => $username,
            'password' => Hash::make($password),
        ]);
        $user->save();

        return $user;
    }

    private function createClass(): ClassesModel
    {
        $schoolYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2026-2027',
            'active' => 1,
        ]);
        $gradeId = DB::table('grade')->insertGetId([
            'grade' => 'Grade 10',
            'status' => 'active',
        ]);
        $adviserId = DB::table('advisers')->insertGetId([
            'name' => 'Archive Adviser',
            'rank' => 'Teacher I',
            'major' => 'General',
            'staff_type' => 'teacher',
        ]);
        $classId = DB::table('classes')->insertGetId([
            'adviser_id' => $adviserId,
            'grade_id' => $gradeId,
            'section' => 'Archive Section',
            'school_year_id' => $schoolYearId,
            'status' => 'active',
            'active' => 1,
        ]);

        return ClassesModel::query()->findOrFail($classId);
    }
}
