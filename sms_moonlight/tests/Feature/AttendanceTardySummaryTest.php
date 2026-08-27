<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClassesModel;
use App\Support\AttendanceTardySummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceTardySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_late_counts_use_the_first_daily_scan_and_class_start_time(): void
    {
        $now = now();
        $schoolYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2026-2027',
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $gradeId = DB::table('grade')->insertGetId([
            'grade' => 'Grade 7',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $adviserId = DB::table('advisers')->insertGetId([
            'user_id' => null,
            'name' => 'Teacher One',
            'rank' => 'Teacher I',
            'major' => 'English',
            'staff_type' => 'teacher',
            'profile_photo' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $studentIds = collect([
            $this->insertStudent('STUDENT-001', 'Ana', 'Reyes'),
            $this->insertStudent('STUDENT-002', 'Ben', 'Santos'),
        ]);
        $classId = DB::table('classes')->insertGetId([
            'adviser_id' => $adviserId,
            'grade_id' => $gradeId,
            'section' => 'Rizal',
            'school_year_id' => $schoolYearId,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'grading_period_count' => 4,
            'status' => 'active',
            'active' => true,
            'enable_assignments' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($studentIds as $studentId) {
            DB::table('class_students')->insert([
                'class_id' => $classId,
                'student_id' => $studentId,
                'school_year_id' => $schoolYearId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->insertAttendance($studentIds[0], '2026-07-26', '07:55:00');
        $this->insertAttendance($studentIds[0], '2026-07-26', '09:30:00');
        $this->insertAttendance($studentIds[1], '2026-07-26', '08:01:00');
        $this->insertAttendance($studentIds[1], '2026-07-26', '12:00:00');
        $this->insertAttendance($studentIds[1], '2026-07-27', '08:15:00');

        $class = ClassesModel::query()->findOrFail($classId);

        $this->assertSame(
            1,
            AttendanceTardySummary::countForClassDate($class, $studentIds, '2026-07-26'),
        );

        $perClass = AttendanceTardySummary::perClass($schoolYearId);

        $this->assertSame('Grade 7 - Rizal', $perClass->first()['label']);
        $this->assertSame(2, $perClass->first()['total']);

        $filtered = AttendanceTardySummary::perClass(
            $schoolYearId,
            collect([$classId]),
            '2026-07-27',
            '2026-07-27',
        );

        $this->assertSame(1, $filtered->first()['total']);
        $this->assertTrue(AttendanceTardySummary::perClass($schoolYearId, collect())->isEmpty());
    }

    public function test_a_class_without_a_start_time_does_not_mark_students_late(): void
    {
        $class = new ClassesModel(['start_time' => null]);

        $this->assertSame(
            0,
            AttendanceTardySummary::countForClassDate($class, collect([1]), '2026-07-26'),
        );
    }

    private function insertStudent(string $lrn, string $firstname, string $lastname): int
    {
        return DB::table('students')->insertGetId([
            'user_id' => null,
            'lrn' => $lrn,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'middlename' => 'Test',
            'gender' => 'Male',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertAttendance(int $studentId, string $date, string $time): void
    {
        DB::table('attendance_record')->insert([
            'student_id' => $studentId,
            'adviser_id' => null,
            'amlogin' => '00:00:00',
            'amlogout' => '00:00:00',
            'pmlogin' => '00:00:00',
            'pmlogout' => '00:00:00',
            'currentdate' => $date,
            'logged_time' => $time,
            'source' => 'scanner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
