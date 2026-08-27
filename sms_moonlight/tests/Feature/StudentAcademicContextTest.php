<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClassStudent;
use App\Models\Student;
use App\MoonShine\Resources\ClassesModel\ClassesModelResource;
use App\Support\StudentAcademicContext;
use App\Support\StudentAcademicContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use Tests\TestCase;

class StudentAcademicContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_college_field_is_removed_from_classes(): void
    {
        $this->assertFalse(Schema::hasColumn('classes', 'is_college'));

        $columns = app(ClassesModelResource::class)
            ->getFormFields()
            ->filter(fn ($field) => method_exists($field, 'getColumn'))
            ->map(fn ($field) => $field->getColumn())
            ->all();

        $this->assertNotContains('is_college', $columns);
    }

    public function test_resolver_uses_only_active_enrollments_and_reports_conflicts(): void
    {
        $records = $this->createAcademicRecords();
        $student = Student::query()->findOrFail($records['student_id']);
        $resolver = app(StudentAcademicContextResolver::class);

        $highSchool = $resolver->resolve($student);
        $this->assertSame(StudentAcademicContext::HIGH_SCHOOL, $highSchool->type);
        $this->assertSame($records['class_id'], $highSchool->highSchoolClass?->class_id);

        DB::table('college_enrollments')->insert([
            'student_id' => $records['student_id'],
            'program_id' => $records['program_id'],
            'school_year_id' => $records['school_year_id'],
            'semester' => 1,
            'year_level' => 1,
            'status' => 'enrolled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $conflict = $resolver->resolve($student);
        $this->assertSame(StudentAcademicContext::CONFLICT, $conflict->type);
        $this->assertStringContainsString('Both a high-school class and a college enrollment', (string) $conflict->conflictReason);

        DB::table('classes')->where('id', $records['class_id'])->update(['active' => false]);

        $college = $resolver->resolve($student);
        $this->assertSame(StudentAcademicContext::COLLEGE, $college->type);
        $this->assertSame($records['school_year_id'], $college->collegeEnrollment?->school_year_id);

        DB::table('school_year')->where('id', $records['school_year_id'])->update(['active' => false]);

        $this->assertSame(StudentAcademicContext::NONE, $resolver->resolve($student)->type);
    }

    public function test_resolver_ignores_college_enrollments_when_college_module_is_disabled(): void
    {
        $records = $this->createAcademicRecords();
        DB::table('classes')->where('id', $records['class_id'])->update(['active' => false]);
        DB::table('college_enrollments')->insert([
            'student_id' => $records['student_id'],
            'program_id' => $records['program_id'],
            'school_year_id' => $records['school_year_id'],
            'semester' => 1,
            'year_level' => 1,
            'status' => 'enrolled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        config()->set('school_portal.features.college_module', false);

        $context = app(StudentAcademicContextResolver::class)
            ->resolve(Student::query()->findOrFail($records['student_id']));

        $this->assertSame(StudentAcademicContext::NONE, $context->type);
        $this->assertNull($context->collegeEnrollment);
    }

    public function test_student_cannot_have_two_high_school_classes_in_one_school_year(): void
    {
        $records = $this->createAcademicRecords();
        $secondClassId = DB::table('classes')->insertGetId([
            'adviser_id' => $records['adviser_id'],
            'grade_id' => $records['grade_id'],
            'section' => 'B',
            'school_year_id' => $records['school_year_id'],
            'grading_period_count' => 4,
            'status' => 'active',
            'active' => true,
            'enable_assignments' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        ClassStudent::query()->create([
            'class_id' => $secondClassId,
            'student_id' => $records['student_id'],
            'school_year_id' => $records['school_year_id'],
        ]);
    }

    public function test_student_sync_prefers_the_username_matched_moonshine_user(): void
    {
        DB::table('moonshine_user_roles')->updateOrInsert(
            ['id' => MoonshineUserRole::DEFAULT_ROLE_ID],
            ['name' => 'Admin', 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('moonshine_user_roles')->updateOrInsert(
            ['id' => 3],
            ['name' => 'Student', 'created_at' => now(), 'updated_at' => now()]
        );

        $matchedUser = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 3,
            'username' => '108226160086',
            'email' => '108226160086@localhost',
            'name' => 'Existing Student',
            'password' => Hash::make('student123'),
        ]);
        $matchedUser->save();

        $staleUser = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 3,
            'username' => 'stale.student',
            'email' => 'stale.student@localhost',
            'name' => 'Stale Student',
            'password' => Hash::make('student123'),
        ]);
        $staleUser->save();

        $student = Student::query()->create([
            'user_id' => $staleUser->id,
            'lrn' => '108226160086',
            'lastname' => 'Cater',
            'firstname' => 'Aira',
            'middlename' => 'Viloria',
            'gender' => 'Female',
            'dob' => '2010-07-12',
            'address' => 'Sample City',
            'birthplace' => 'Sample City',
            'profile_photo' => '',
            'parent_guardian' => 'Guardian',
            'parent_guardian_address' => 'Sample City',
            'parent_guardian_relationship' => 'Mother',
            'is_4ps_member' => false,
        ]);

        $this->assertSame($matchedUser->id, $student->refresh()->user_id);
        $this->assertSame('108226160086@localhost', $matchedUser->refresh()->email);
        $this->assertSame('stale.student@localhost', $staleUser->refresh()->email);
    }

    /**
     * @return array<string, int>
     */
    private function createAcademicRecords(): array
    {
        $now = now();
        $schoolYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2026-2027',
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $gradeId = DB::table('grade')->insertGetId([
            'grade' => 'Grade 8',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $adviserId = DB::table('advisers')->insertGetId([
            'user_id' => null,
            'name' => 'Teacher One',
            'rank' => 'Teacher I',
            'major' => 'Mathematics',
            'staff_type' => 'teacher',
            'profile_photo' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $studentId = DB::table('students')->insertGetId([
            'user_id' => null,
            'lrn' => 'STUDENT-0001',
            'lastname' => 'Dela Cruz',
            'firstname' => 'Juan',
            'middlename' => 'Santos',
            'gender' => 'Male',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $classId = DB::table('classes')->insertGetId([
            'adviser_id' => $adviserId,
            'grade_id' => $gradeId,
            'section' => 'A',
            'school_year_id' => $schoolYearId,
            'grading_period_count' => 4,
            'status' => 'active',
            'active' => true,
            'enable_assignments' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ClassStudent::query()->create([
            'class_id' => $classId,
            'student_id' => $studentId,
            'school_year_id' => $schoolYearId,
        ]);

        $programId = DB::table('college_programs')->insertGetId([
            'code' => 'BSIT',
            'name' => 'Information Technology',
            'duration_years' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return [
            'school_year_id' => $schoolYearId,
            'grade_id' => $gradeId,
            'adviser_id' => $adviserId,
            'student_id' => $studentId,
            'class_id' => $classId,
            'program_id' => $programId,
        ];
    }
}
