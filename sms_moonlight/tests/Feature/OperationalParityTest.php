<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Imports\StudentImport;
use App\Models\Adviser;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassesModel;
use App\Models\ClassStudent;
use App\Models\Student;
use App\Support\CsvCell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use MoonShine\Laravel\Models\MoonshineUser;
use Tests\TestCase;

class OperationalParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_assignment_files_are_private_and_follow_class_authorization(): void
    {
        $records = $this->assignmentRecords();
        $assignment = $this->assignment($records);
        Storage::disk('local')->put($assignment->file_path, 'assignment-content');
        Storage::disk('public')->put('assignments/exposed.pdf', 'must-not-be-public');

        $this->actingAs($records['student_user'], 'moonshine')
            ->get(route('student.assignments.download', $assignment))
            ->assertOk();

        $this->actingAs($records['other_student_user'], 'moonshine')
            ->get(route('student.assignments.download', $assignment))
            ->assertNotFound();

        $this->actingAs($records['teacher_user'], 'moonshine')
            ->get(route('teacher.assignments.download', $assignment))
            ->assertOk();

        $this->actingAs($records['other_teacher_user'], 'moonshine')
            ->get(route('teacher.assignments.download', $assignment))
            ->assertNotFound();

        $this->get('/uploaded-files/assignments/exposed.pdf')->assertNotFound();
        $this->get('/uploads/assignment-submissions/exposed.pdf')->assertNotFound();
    }

    public function test_student_submission_is_private_replaceable_and_limited_to_the_roster(): void
    {
        $records = $this->assignmentRecords();
        $assignment = $this->assignment($records);
        Storage::disk('local')->put($assignment->file_path, 'assignment-content');

        $this->actingAs($records['student_user'], 'moonshine')
            ->post(route('student.assignments.submit', $assignment), [
                'notes' => 'First version',
                'file' => UploadedFile::fake()->create('answer.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('student.dashboard', ['tab' => 'assignments']));

        $submission = AssignmentSubmission::query()->firstOrFail();
        $firstPath = $submission->file_path;
        Storage::disk('local')->assertExists($firstPath);
        Storage::disk('public')->assertMissing($firstPath);

        $this->post(route('student.assignments.submit', $assignment), [
            'notes' => 'Replacement',
            'file' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('student.dashboard', ['tab' => 'assignments']));

        $submission->refresh();
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($submission->file_path);

        $this->actingAs($records['other_student_user'], 'moonshine')
            ->get(route('student.assignment-submissions.download', $submission))
            ->assertNotFound();

        $this->actingAs($records['teacher_user'], 'moonshine')
            ->get(route('teacher.assignment-submissions.download', $submission))
            ->assertOk();

        $this->actingAs($records['other_teacher_user'], 'moonshine')
            ->get(route('teacher.assignment-submissions.download', $submission))
            ->assertNotFound();
    }

    public function test_expired_assignment_rejects_new_submissions(): void
    {
        $records = $this->assignmentRecords();
        $assignment = $this->assignment($records, deadline: now()->subMinute());

        $this->actingAs($records['student_user'], 'moonshine')
            ->post(route('student.assignments.submit', $assignment), [
                'file' => UploadedFile::fake()->create('late.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('assignment_submissions', 0);
    }

    public function test_teacher_bulk_archive_is_limited_to_an_owned_class(): void
    {
        $records = $this->assignmentRecords();

        $this->actingAs($records['teacher_user'], 'moonshine')
            ->post(route('teacher.students.archive'), ['class_id' => $records['class']->id])
            ->assertSessionHas('success');

        $this->assertTrue($records['student']->refresh()->archived);
        $this->assertDatabaseHas('student_access', [
            'student_id' => $records['student']->id,
            'active' => 0,
        ]);

        $this->actingAs($records['other_teacher_user'], 'moonshine')
            ->post(route('teacher.students.archive'), ['class_id' => $records['class']->id])
            ->assertNotFound();
    }

    public function test_student_import_cannot_take_over_a_non_student_account(): void
    {
        $this->role(1, 'Admin');
        $admin = $this->portalUser(1, 'ADMIN-001');
        $rows = collect([
            collect(['LRN', 'Firstname', 'Lastname', 'Middlename', 'Gender', 'Birthday']),
            collect(['ADMIN-001', 'Imported', 'Student', '', 'Male', '2012-01-01']),
        ]);

        try {
            (new StudentImport)->collection($rows);
            $this->fail('The conflicting import should have failed.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('non-student account', $exception->getMessage());
        }

        $this->assertSame(1, (int) $admin->refresh()->moonshine_user_role_id);
        $this->assertDatabaseMissing('students', ['lrn' => 'ADMIN-001']);
    }

    public function test_csv_exports_neutralize_spreadsheet_formulas(): void
    {
        $this->assertSame(
            ["'=2+2", "'+cmd", "'-10", "'@SUM(A1:A2)", 'Normal', 42],
            CsvCell::row(['=2+2', '+cmd', '-10', '@SUM(A1:A2)', 'Normal', 42])
        );
    }

    public function test_announcements_are_validated_and_scoped_by_audience_and_expiry(): void
    {
        Announcement::query()->create([
            'title' => 'Everyone',
            'content' => '<p>Visible</p>',
            'target_audience' => 'both',
        ]);
        Announcement::query()->create([
            'title' => 'Teachers only',
            'content' => '<p>Teachers</p>',
            'target_audience' => 'teachers',
        ]);
        Announcement::query()->create([
            'title' => 'Expired student notice',
            'content' => '<p>Expired</p>',
            'target_audience' => 'students',
            'expiry_date' => now()->subDay(),
        ]);

        $this->assertSame(
            ['Everyone'],
            Announcement::query()->forAudience('students')->active()->pluck('title')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['Everyone', 'Teachers only'],
            Announcement::query()->forAudience('teachers')->active()->pluck('title')->all()
        );

        $this->expectException(ValidationException::class);

        Announcement::query()->create([
            'title' => 'Invalid audience',
            'content' => 'No',
            'target_audience' => 'public',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentRecords(): array
    {
        $this->role(2, 'Teacher');
        $this->role(3, 'Student');
        $teacherUser = $this->portalUser(2, 'teacher.operations');
        $otherTeacherUser = $this->portalUser(2, 'teacher.other');
        $studentUser = $this->portalUser(3, 'student.operations');
        $otherStudentUser = $this->portalUser(3, 'student.other');
        $now = now();
        $teacherId = $this->teacher($teacherUser->id, 'Operations Teacher');
        $this->teacher($otherTeacherUser->id, 'Other Teacher');
        $studentId = $this->student($studentUser->id, 'OPS-001', 'Operations', 'Student');
        $otherStudentId = $this->student($otherStudentUser->id, 'OPS-002', 'Other', 'Student');

        foreach ([[$studentId, $studentUser->id], [$otherStudentId, $otherStudentUser->id]] as [$studentIdValue, $userId]) {
            DB::table('student_access')->insert([
                'student_id' => $studentIdValue,
                'user_id' => $userId,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $schoolYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2026-2027',
            'active' => true,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(8)->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $gradeId = DB::table('grade')->insertGetId([
            'grade' => 'Grade 9',
            'status' => 'active',
            'term_count' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $class = ClassesModel::query()->create([
            'adviser_id' => $teacherId,
            'grade_id' => $gradeId,
            'section' => 'Operations',
            'school_year_id' => $schoolYearId,
            'start_time' => '08:00',
            'end_time' => '15:00',
            'grading_period_count' => 4,
            'status' => 'active',
            'active' => true,
            'enable_assignments' => true,
        ]);
        ClassStudent::query()->create([
            'class_id' => $class->id,
            'student_id' => $studentId,
            'school_year_id' => $schoolYearId,
            'hidden_grade' => false,
        ]);

        return [
            'teacher_user' => $teacherUser,
            'other_teacher_user' => $otherTeacherUser,
            'student_user' => $studentUser,
            'other_student_user' => $otherStudentUser,
            'student' => Student::query()->findOrFail($studentId),
            'class' => $class,
        ];
    }

    private function assignment(array $records, mixed $deadline = null): Assignment
    {
        $teacherId = Adviser::query()->where('user_id', $records['teacher_user']->id)->value('id');

        return Assignment::query()->create([
            'class_id' => $records['class']->id,
            'adviser_id' => $teacherId,
            'title' => 'Operational Assignment',
            'notes' => 'Complete the attached work.',
            'file_path' => 'assignments/operational-assignment.pdf',
            'file_name' => 'operational-assignment.pdf',
            'deadline' => $deadline ?? now()->addDay(),
            'sent_at' => now(),
        ]);
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

    private function teacher(int $userId, string $name): int
    {
        return DB::table('advisers')->insertGetId([
            'user_id' => $userId,
            'name' => $name,
            'rank' => 'Teacher I',
            'major' => 'Operations',
            'staff_type' => Adviser::TYPE_TEACHER,
            'profile_photo' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function student(int $userId, string $lrn, string $firstName, string $lastName): int
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
