<?php

namespace Tests\Feature;

use App\Models\ClassesModel;
use App\Models\ClassStudent;
use App\Models\Grade;
use App\Models\SchoolYear;
use App\MoonShine\Resources\PaymentType\PaymentTypeResource;
use App\MoonShine\Resources\SchoolYear\SchoolYearResource;
use App\MoonShine\Resources\Student\StudentResource;
use App\MoonShine\Resources\StudentPaymentHistory\Pages\StudentPaymentHistoryIndexPage;
use App\MoonShine\Resources\StudentPaymentHistory\StudentPaymentHistoryResource;
use App\Services\Exports\StudentGradesWorkbookExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use ReflectionMethod;
use Tests\TestCase;

class StudentDetailsAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_details_include_academic_and_payment_relationships(): void
    {
        config()->set('school_portal.features.payments_module', true);
        config()->set('school_portal.payments.authorized_admin_username', 'payment-admin');

        $paymentAdmin = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
            'username' => 'payment-admin',
            'email' => 'payment-admin@example.test',
            'name' => 'Payment Admin',
            'password' => Hash::make('payment-secret'),
        ]);
        $paymentAdmin->save();
        auth('moonshine')->setUser($paymentAdmin);

        app(CoreContract::class)->resources([
            PaymentTypeResource::class,
            StudentPaymentHistoryResource::class,
        ]);

        $relations = collect(app(StudentResource::class)->detailsFields())
            ->filter(fn ($field) => $field instanceof HasMany)
            ->map(fn (HasMany $field) => $field->getRelationName())
            ->values()
            ->all();

        $this->assertContains('classStudents', $relations);
        $this->assertContains('studentClasses', $relations);
        $this->assertContains('classStudentGrades', $relations);
        $this->assertContains('paymentHistories', $relations);
    }

    public function test_disabled_modules_hide_student_payment_and_quiz_relationships(): void
    {
        config()->set('school_portal.features.payments_module', false);
        config()->set('school_portal.features.quiz_module', false);

        $relations = collect(app(StudentResource::class)->detailsFields())
            ->filter(fn ($field) => $field instanceof HasMany)
            ->map(fn (HasMany $field) => $field->getRelationName())
            ->values()
            ->all();

        $this->assertNotContains('paymentHistories', $relations);
        $this->assertNotContains('studentQuizAnswers', $relations);
    }

    public function test_tshirt_size_setting_controls_the_extra_information_field(): void
    {
        $this->assertDatabaseHas('settings', [
            'settingName' => 'tshirt_size_enabled',
            'settingValue' => '1',
            'settingType' => 'boolean',
        ]);

        config()->set('school.tshirt_size_enabled', '0');

        $disabledColumns = collect(app(StudentResource::class)->extraInformationFields())
            ->map(fn ($field) => $field->getColumn())
            ->all();

        $this->assertNotContains('tshirt_size', $disabledColumns);

        config()->set('school.tshirt_size_enabled', '1');

        $enabledColumns = collect(app(StudentResource::class)->extraInformationFields())
            ->map(fn ($field) => $field->getColumn())
            ->all();

        $this->assertContains('tshirt_size', $enabledColumns);
    }

    public function test_elementary_fields_setting_controls_student_form_and_details(): void
    {
        $this->assertDatabaseHas('settings', [
            'settingName' => 'elementary_fields_enabled',
            'settingValue' => '1',
            'settingType' => 'boolean',
        ]);

        $elementaryColumns = [
            'elementary_school_name',
            'elementary_school_id',
            'elementary_school_address',
            'elementary_school_grade',
            'elementary_school_citation',
            'deworming_grade_7',
            'deworming_grade_8',
            'deworming_grade_9',
            'deworming_grade_10',
        ];

        config()->set('school.use_jhs_fields', '0');
        config()->set('school.elementary_fields_enabled', '0');

        $resource = app(StudentResource::class);
        $disabledFormColumns = collect($resource->formFields())
            ->map(fn ($field) => $field->getColumn())
            ->all();
        $disabledDetailColumns = collect($resource->detailsFields())
            ->map(fn ($field) => $field->getColumn())
            ->all();

        foreach ($elementaryColumns as $column) {
            $this->assertNotContains($column, $disabledFormColumns);
            $this->assertNotContains($column, $disabledDetailColumns);
        }

        config()->set('school.elementary_fields_enabled', '1');

        $enabledFormColumns = collect($resource->formFields())
            ->map(fn ($field) => $field->getColumn())
            ->all();
        $enabledDetailColumns = collect($resource->detailsFields())
            ->map(fn ($field) => $field->getColumn())
            ->all();

        foreach ($elementaryColumns as $column) {
            $this->assertContains($column, $enabledFormColumns);
            $this->assertContains($column, $enabledDetailColumns);
        }
    }

    public function test_student_payments_index_summary_aggregates_selected_students(): void
    {
        DB::table('student_payment_histories')->insert([
            ['student_id' => 10, 'amount' => 100, 'created_at' => now(), 'updated_at' => now()],
            ['student_id' => 10, 'amount' => 150.50, 'created_at' => now(), 'updated_at' => now()],
            ['student_id' => 20, 'amount' => 200, 'created_at' => now(), 'updated_at' => now()],
            ['student_id' => 30, 'amount' => 999, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $method = new ReflectionMethod(StudentPaymentHistoryIndexPage::class, 'paymentSummary');
        $summary = $method->invoke(app(StudentPaymentHistoryIndexPage::class), collect([10, 20]));

        $this->assertSame(450.50, $summary['total']);
        $this->assertSame(3, $summary['transactions']);
        $this->assertSame(2, $summary['students']);
        $this->assertSame('Unspecified — 2', $summary['payment_types']);
    }

    public function test_payment_summary_component_is_only_on_student_payments_index(): void
    {
        $dashboard = file_get_contents(app_path('MoonShine/Pages/Dashboard.php'));
        $paymentsIndex = file_get_contents(
            app_path('MoonShine/Resources/StudentPaymentHistory/Pages/StudentPaymentHistoryIndexPage.php')
        );

        $this->assertStringNotContainsString("Box::make('Payment Summary'", $dashboard);
        $this->assertStringContainsString("Box::make('Payment Summary'", $paymentsIndex);
        $this->assertStringContainsString('SchoolYearSelector::make', $paymentsIndex);
    }

    public function test_only_one_school_year_remains_active(): void
    {
        $first = SchoolYear::query()->create([
            'school_year' => '2025-2026',
            'active' => true,
        ]);
        $second = SchoolYear::query()->create([
            'school_year' => '2026-2027',
            'active' => true,
        ]);

        $this->assertFalse((bool) $first->fresh()->active);
        $this->assertTrue((bool) $second->fresh()->active);
        $this->assertSame(1, SchoolYear::query()->where('active', true)->count());
    }

    public function test_school_year_supports_attendance_date_boundaries(): void
    {
        $columns = collect(app(SchoolYearResource::class)->formFields())
            ->map(fn ($field) => $field->getColumn())
            ->all();

        $this->assertContains('start_date', $columns);
        $this->assertContains('end_date', $columns);

        $schoolYear = SchoolYear::query()->create([
            'school_year' => '2027-2028',
            'start_date' => '2027-06-01',
            'end_date' => '2028-05-31',
            'active' => false,
        ]);

        $this->assertSame('2027-06-01', $schoolYear->start_date?->format('Y-m-d'));
        $this->assertSame('2028-05-31', $schoolYear->end_date?->format('Y-m-d'));
    }

    public function test_class_grading_period_count_controls_student_grade_terms(): void
    {
        $grade = new Grade(['term_count' => 2]);
        $class = new ClassesModel(['grading_period_count' => 3]);
        $class->setRelation('grade', $grade);

        $classStudent = new ClassStudent;
        $classStudent->setRelation('class', $class);

        $this->assertSame(3, $classStudent->termCount());
        $this->assertSame(['q1', 'q2', 'q3'], $classStudent->termKeys());
    }

    public function test_grade_workbook_tabs_are_named_for_each_class_history(): void
    {
        $grade = new Grade(['grade' => 'Grade 8']);
        $class = new ClassesModel(['section' => 'Rizal']);
        $class->setRelation('grade', $grade);

        $schoolYear = new SchoolYear(['school_year' => '2026-2027']);
        $classStudent = new ClassStudent(['id' => 42]);
        $classStudent->setRelation('class', $class);
        $classStudent->setRelation('schoolYear', $schoolYear);

        $method = new ReflectionMethod(StudentGradesWorkbookExporter::class, 'sheetName');
        $sheetName = $method->invoke(
            app(StudentGradesWorkbookExporter::class),
            $classStudent,
            'DELA CRUZ, JUAN'
        );

        $this->assertStringContainsString('2026-2027', $sheetName);
        $this->assertStringContainsString('GRADE 8', $sheetName);
        $this->assertStringEndsWith('#42', $sheetName);
        $this->assertLessThanOrEqual(31, strlen($sheetName));
    }
}
