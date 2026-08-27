<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CollegeEnrollmentCourse;
use App\Models\PaymentType;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizGroup;
use App\Models\QuizGroupDay;
use App\Models\QuizQuizAnswer;
use App\Models\QuizQuizGroupDay;
use App\Models\StudentPaymentHistory;
use App\Support\PaymentAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use Tests\TestCase;

class CompleteParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_configurable_module_routes_use_runtime_feature_guards(): void
    {
        $this->assertContains(
            'feature:college_module',
            app('router')->getRoutes()->getByName('admin.college-enrollments.import')->gatherMiddleware()
        );
        $this->assertContains(
            'feature:payments_module',
            app('router')->getRoutes()->getByName('admin.payments.authorization')->gatherMiddleware()
        );
        $this->assertContains(
            'feature:quiz_module',
            app('router')->getRoutes()->getByName('student.quiz.submit')->gatherMiddleware()
        );

        $admin = $this->admin('feature-admin');
        config()->set('school_portal.features.college_module', false);
        config()->set('school_portal.features.payments_module', false);

        $this->actingAs($admin, 'moonshine')
            ->get(route('admin.college-enrollments.template'))
            ->assertNotFound();
        $this->get(route('admin.payments.authorization'))->assertNotFound();
    }

    public function test_payment_records_and_types_are_validated_at_the_model_boundary(): void
    {
        $studentId = DB::table('students')->insertGetId([
            'lrn' => 'PAY-001',
            'lastname' => 'Payer',
            'firstname' => 'Student',
            'middlename' => '',
            'gender' => 'Male',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectValidationException(function () use ($studentId): void {
            StudentPaymentHistory::query()->create([
                'student_id' => $studentId,
                'payment_date' => now(),
                'amount' => 0,
            ]);
        }, 'amount');

        PaymentType::query()->create(['name' => 'Tuition']);

        $this->expectValidationException(
            fn () => PaymentType::query()->create(['name' => ' tuition ']),
            'name'
        );
    }

    public function test_submitted_college_grades_are_locked_at_the_model_boundary(): void
    {
        $course = new CollegeEnrollmentCourse;
        $course->setRawAttributes([
            'id' => 999,
            'enrollment_id' => 1,
            'program_course_id' => 1,
            'offering_id' => 1,
            'prelim_grade' => 90,
            'midterm_grade' => 90,
            'prefinal_grade' => 90,
            'final_grade' => 90,
            'remarks' => 'Passed',
            'grades_submitted_at' => now()->toDateTimeString(),
        ], true);
        $course->exists = true;
        $course->prelim_grade = 75;

        $this->expectValidationException(fn () => $course->save(), 'grades');
    }

    public function test_payment_unlock_is_temporary_and_bound_to_the_current_admin(): void
    {
        config()->set('school_portal.payments.authorized_admin_username', 'payment-admin');
        config()->set('school_portal.payments.unlock_minutes', 15);

        $paymentAdmin = $this->admin('payment-admin');
        $requestingAdmin = $this->admin('requesting-admin');
        $otherAdmin = $this->admin('other-admin');
        $session = app('session')->driver();
        $request = Request::create('/admin/payments/authorize');
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $requestingAdmin);

        PaymentAccess::unlock($request, $paymentAdmin);

        $this->assertTrue(PaymentAccess::isSessionUnlocked($request));

        $request->setUserResolver(fn () => $otherAdmin);
        $this->assertFalse(PaymentAccess::isSessionUnlocked($request));

        $request->setUserResolver(fn () => $requestingAdmin);
        $session->put(PaymentAccess::SESSION_EXPIRES_AT, now()->subMinute()->timestamp);
        $this->assertFalse(PaymentAccess::isSessionUnlocked($request));
    }

    public function test_quiz_submission_is_complete_atomic_and_one_time(): void
    {
        config()->set('school_portal.features.quiz_module', true);
        [$studentUser, $quizDay, $questions] = $this->quizRecords();

        $this->actingAs($studentUser, 'moonshine')
            ->post(route('student.quiz.submit', $quizDay), [
                'answers' => [$questions[0]['quiz']->id => $questions[0]['answer']->id],
            ])
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('student_quiz_answers', 0);

        $validAnswers = [
            $questions[0]['quiz']->id => $questions[0]['answer']->id,
            $questions[1]['quiz']->id => $questions[1]['answer']->id,
        ];

        $this->post(route('student.quiz.submit', $quizDay), ['answers' => $validAnswers])
            ->assertRedirect(route('student.dashboard', ['tab' => 'quiz']));

        $this->assertDatabaseCount('student_quiz_answers', 2);

        $this->post(route('student.quiz.submit', $quizDay), ['answers' => $validAnswers])
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('student_quiz_answers', 2);
    }

    private function quizRecords(): array
    {
        $now = now();
        DB::table('moonshine_user_roles')->updateOrInsert(
            ['id' => 3],
            ['name' => 'Student', 'created_at' => $now, 'updated_at' => $now]
        );
        $studentUser = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 3,
            'username' => 'QUIZ-001',
            'email' => 'quiz-001@example.test',
            'name' => 'Quiz Student',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $studentUser->save();
        $schoolYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2026-2027',
            'active' => true,
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
            'name' => 'Quiz Teacher',
            'rank' => 'Teacher I',
            'major' => 'Science',
            'staff_type' => 'teacher',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $studentId = DB::table('students')->insertGetId([
            'user_id' => $studentUser->id,
            'lrn' => 'QUIZ-001',
            'lastname' => 'Student',
            'firstname' => 'Quiz',
            'middlename' => '',
            'gender' => 'Male',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $classId = DB::table('classes')->insertGetId([
            'adviser_id' => $adviserId,
            'grade_id' => $gradeId,
            'section' => 'Quiz Section',
            'school_year_id' => $schoolYearId,
            'grading_period_count' => 4,
            'status' => 'active',
            'active' => true,
            'enable_assignments' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('class_students')->insert([
            'class_id' => $classId,
            'student_id' => $studentId,
            'school_year_id' => $schoolYearId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('student_access')->insert([
            'student_id' => $studentId,
            'user_id' => $studentUser->id,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $quizGroup = QuizGroup::query()->create([
            'school_year_id' => $schoolYearId,
            'grade_id' => $gradeId,
            'week' => 'Week 1',
        ]);
        $quizDay = QuizGroupDay::query()->create([
            'title' => 'Monday Quiz',
            'quiz_group_id' => $quizGroup->id,
            'day' => 'Monday',
            'quiz_duration_seconds' => 600,
        ]);

        $questions = collect(['First question?', 'Second question?'])
            ->map(function (string $text, int $index) use ($quizDay): array {
                $quiz = Quiz::query()->create(['question' => $text]);
                $answer = QuizAnswer::query()->create(['answer' => 'Answer '.($index + 1)]);
                QuizQuizAnswer::query()->create([
                    'quiz_id' => $quiz->id,
                    'answer_id' => $answer->id,
                    'is_correct_answer' => true,
                ]);
                QuizQuizGroupDay::query()->create([
                    'quiz_id' => $quiz->id,
                    'quiz_group_days_id' => $quizDay->id,
                    'record_order' => $index + 1,
                ]);

                return compact('quiz', 'answer');
            })
            ->all();

        return [$studentUser, $quizDay, $questions];
    }

    private function admin(string $username): MoonshineUser
    {
        $user = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
            'username' => $username,
            'email' => $username.'@example.test',
            'name' => $username,
            'password' => Hash::make('password'),
        ]);
        $user->save();

        return $user;
    }

    private function expectValidationException(callable $callback, string $field): void
    {
        try {
            $callback();
            $this->fail('Expected validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
