<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\MoonShine\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class DashboardAcademicInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_and_record_completeness_insights_respect_the_selected_students(): void
    {
        config()->set('school_portal.features.quiz_module', true);

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
            'term_count' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $adviserId = DB::table('advisers')->insertGetId([
            'name' => 'Teacher One',
            'rank' => 'Teacher I',
            'major' => 'Mathematics',
            'staff_type' => 'teacher',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $classId = DB::table('classes')->insertGetId([
            'adviser_id' => $adviserId,
            'grade_id' => $gradeId,
            'section' => 'Rizal',
            'school_year_id' => $schoolYearId,
            'grading_period_count' => 4,
            'status' => 'active',
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $subjectId = DB::table('subjects')->insertGetId([
            'subject' => 'Mathematics',
            'include_in_average' => true,
            'record_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('class_subjects')->insert([
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $firstStudentId = $this->insertStudent('STUDENT-001', 'Ana', 'Reyes', true);
        $secondStudentId = $this->insertStudent('STUDENT-002', 'Ben', 'Santos', false);

        foreach ([$firstStudentId, $secondStudentId] as $studentId) {
            DB::table('class_students')->insert([
                'class_id' => $classId,
                'student_id' => $studentId,
                'school_year_id' => $schoolYearId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('class_student_grades')->insert([
            'class_id' => $classId,
            'student_id' => $firstStudentId,
            'grade_id' => $gradeId,
            'subject_id' => $subjectId,
            'q1' => 70,
            'q2' => 70,
            'q3' => 70,
            'q4' => 70,
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
        $quizGroupDayId = DB::table('quiz_group_days')->insertGetId([
            'title' => 'Monday Quiz',
            'quiz_group_id' => $quizGroupId,
            'day' => 'Monday',
            'quiz_duration_seconds' => 60,
            'record_created' => $now,
            'record_updated' => $now,
        ]);
        $quizId = DB::table('quizzes')->insertGetId([
            'question' => 'Two plus two?',
            'record_created' => $now,
            'record_updated' => $now,
        ]);
        $answerId = DB::table('quiz_answers')->insertGetId([
            'answer' => 'Four',
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
        DB::table('student_quiz_answers')->insert([
            'quiz_group_days_id' => $quizGroupDayId,
            'quiz_id' => $quizId,
            'answer_id' => $answerId,
            'student_id' => $firstStudentId,
            'record_created' => $now,
            'record_updated' => $now,
        ]);

        $dashboard = app(Dashboard::class);
        $classIds = collect([$classId]);
        $studentIds = collect([$firstStudentId, $secondStudentId]);
        $academicMethod = new ReflectionMethod(Dashboard::class, 'academicInsights');
        $academic = $academicMethod->invoke($dashboard, $schoolYearId, $classIds, $studentIds);

        $this->assertSame(2, $academic['summary']['expected_grade_records']);
        $this->assertSame(1, $academic['summary']['grade_records']);
        $this->assertSame(50.0, $academic['summary']['grade_coverage']);
        $this->assertSame(1, $academic['summary']['at_risk_students']);
        $this->assertSame(50.0, $academic['summary']['quiz_participation']);
        $this->assertSame(100.0, $academic['summary']['quiz_accuracy']);
        $this->assertSame(70.0, $academic['subject_performance']->first()['average']);

        $recordMethod = new ReflectionMethod(Dashboard::class, 'recordCompleteness');
        $records = $recordMethod->invoke($dashboard, $schoolYearId, $classIds, $studentIds);

        $this->assertSame(2, $records['summary']['students']);
        $this->assertSame(1, $records['summary']['missing_rfid']);
        $this->assertSame(1, $records['summary']['missing_photo']);
        $this->assertSame(1, $records['summary']['missing_dob']);
        $this->assertSame(1, $records['summary']['missing_guardian']);
    }

    private function insertStudent(string $lrn, string $firstname, string $lastname, bool $complete): int
    {
        return DB::table('students')->insertGetId([
            'lrn' => $lrn,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'middlename' => 'Test',
            'gender' => 'Male',
            'rfid_card_uid' => $complete ? 'RFID-'.$lrn : null,
            'profile_photo' => $complete ? 'students/test.jpg' : null,
            'dob' => $complete ? '2012-01-01' : null,
            'parent_guardian' => $complete ? 'Parent Test' : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
