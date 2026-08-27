<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PaymentExportController;
use App\MoonShine\Resources\PaymentType\PaymentTypeResource;
use App\MoonShine\Resources\StudentPaymentHistory\Pages\StudentPaymentHistoryIndexPage;
use App\MoonShine\Resources\StudentPaymentHistory\StudentPaymentHistoryResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use ReflectionMethod;
use Tests\TestCase;

class PaymentsConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_defaults_and_student_search_are_configured(): void
    {
        $this->assertDatabaseHas('payment_types', ['name' => 'Cash']);
        $this->assertDatabaseHas('payment_types', ['name' => 'Credit']);

        app(CoreContract::class)->resources([PaymentTypeResource::class]);

        $resource = app(StudentPaymentHistoryResource::class);
        $formFields = collect($resource->formFields());
        $studentField = $formFields->first(
            fn ($field) => $field instanceof BelongsTo && $field->getRelationName() === 'student'
        );

        $this->assertNotNull($studentField);
        $this->assertTrue($studentField->isSearchable());
        $this->assertSame(
            'OR # / Reference #',
            $formFields->first(fn ($field) => $field->getColumn() === 'reference')?->getLabel()
        );

        $paymentDateField = $formFields->first(
            fn ($field) => $field instanceof Date && $field->getColumn() === 'payment_date'
        );

        $this->assertSame('datetime-local', $paymentDateField?->getAttribute('type'));

        $filters = new ReflectionMethod($resource, 'filters');
        $paymentDateFilter = collect($filters->invoke($resource))->first(
            fn ($field) => $field->getColumn() === 'payment_date'
        );

        $this->assertInstanceOf(DateRange::class, $paymentDateFilter);

        $search = new ReflectionMethod($resource, 'search');

        $this->assertSame(
            ['firstname', 'lastname', 'lrn'],
            $search->invoke($resource)['student']
        );
    }

    public function test_payment_export_respects_the_date_range(): void
    {
        config()->set('school_portal.features.payments_module', true);

        DB::table('students')->insert([
            'id' => 101,
            'lrn' => 'LRN-101',
            'lastname' => 'Included',
            'firstname' => 'Student',
            'middlename' => '',
            'gender' => 'female',
        ]);

        DB::table('student_payment_histories')->insert([
            [
                'student_id' => 101,
                'payment_date' => '2026-07-10 09:30:00',
                'amount' => 125.50,
                'reference' => 'INCLUDED-OR',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => 101,
                'payment_date' => '2026-07-12 14:00:00',
                'amount' => 250,
                'reference' => 'EXCLUDED-OR',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/admin/payments/export', 'GET', [
            'filter' => [
                'payment_date' => [
                    'from' => '2026-07-10',
                    'to' => '2026-07-10',
                ],
            ],
        ]);

        $response = app(PaymentExportController::class)($request);

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('INCLUDED-OR', $csv);
        $this->assertStringNotContainsString('EXCLUDED-OR', $csv);
        $this->assertStringContainsString('2026-07-10 09:30:00', $csv);
    }

    public function test_payment_summary_includes_college_students_for_the_selected_school_year(): void
    {
        $now = now();
        $schoolYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2026-2027',
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $studentId = DB::table('students')->insertGetId([
            'lrn' => 'COLLEGE-PAYMENT-001',
            'lastname' => 'Alon',
            'firstname' => 'Lex Robert',
            'middlename' => '',
            'gender' => 'Male',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $programId = DB::table('college_programs')->insertGetId([
            'code' => 'BSIT-PAY',
            'name' => 'Information Technology Payments Test',
            'duration_years' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('college_enrollments')->insert([
            'student_id' => $studentId,
            'program_id' => $programId,
            'school_year_id' => $schoolYearId,
            'semester' => 1,
            'year_level' => 1,
            'status' => 'enrolled',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cashPaymentTypeId = DB::table('payment_types')->where('name', 'Cash')->value('id');

        DB::table('student_payment_histories')->insert([
            [
                'student_id' => $studentId,
                'payment_type_id' => $cashPaymentTypeId,
                'payment_date' => '2026-06-26 11:00:00',
                'amount' => 500,
                'reference' => '3819',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'student_id' => $studentId,
                'payment_type_id' => $cashPaymentTypeId,
                'payment_date' => '2026-06-27 11:03:00',
                'amount' => 2000,
                'reference' => '3827',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'student_id' => $studentId,
                'payment_type_id' => $cashPaymentTypeId,
                'payment_date' => '2026-07-10 14:02:00',
                'amount' => 6000,
                'reference' => '3886',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $page = app(StudentPaymentHistoryIndexPage::class);
        $studentIdsMethod = new ReflectionMethod($page, 'studentIdsForSchoolYear');
        $summaryMethod = new ReflectionMethod($page, 'paymentSummary');
        $studentIds = $studentIdsMethod->invoke($page, $schoolYearId);
        $summary = $summaryMethod->invoke($page, $studentIds);

        $this->assertSame([$studentId], $studentIds->all());
        $this->assertSame(8500.0, $summary['total']);
        $this->assertSame(3, $summary['transactions']);
        $this->assertSame(1, $summary['students']);
        $this->assertSame('Cash — 1', $summary['payment_types']);
    }

    public function test_only_configured_admin_can_open_payment_pages_without_confirmation(): void
    {
        config()->set('school_portal.payments.authorized_admin_username', 'payment-admin');

        $paymentAdmin = $this->createAdmin('payment-admin', 'payment-secret');
        $otherAdmin = $this->createAdmin('other-admin', 'other-secret');
        $paymentUrl = route('moonshine.crud.index', [
            'resourceUri' => 'student-payment-history-resource',
        ]);

        $this->actingAs($otherAdmin, 'moonshine')
            ->get($paymentUrl)
            ->assertRedirect(route('admin.payments.authorization'));

        $allowedResponse = $this->actingAs($paymentAdmin, 'moonshine')
            ->get($paymentUrl);

        $this->assertNotSame(
            route('admin.payments.authorization'),
            $allowedResponse->headers->get('location')
        );
    }

    public function test_other_admin_can_unlock_payment_pages_with_configured_admin_password(): void
    {
        config()->set('school_portal.payments.authorized_admin_username', 'payment-admin');

        $this->createAdmin('payment-admin', 'payment-secret');
        $otherAdmin = $this->createAdmin('other-admin', 'other-secret');
        $paymentUrl = route('moonshine.crud.index', [
            'resourceUri' => 'student-payment-history-resource',
        ]);

        $this->actingAs($otherAdmin, 'moonshine')
            ->withSession(['payments.intended_url' => $paymentUrl])
            ->post(route('admin.payments.authorize'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->actingAs($otherAdmin, 'moonshine')
            ->withSession(['payments.intended_url' => $paymentUrl])
            ->post(route('admin.payments.authorize'), ['password' => 'payment-secret'])
            ->assertRedirect($paymentUrl)
            ->assertSessionHas('payments.authorized_user_id');

        $unlockedResponse = $this->get($paymentUrl);

        $this->assertNotSame(
            route('admin.payments.authorization'),
            $unlockedResponse->headers->get('location')
        );
    }

    private function createAdmin(string $username, string $password): MoonshineUser
    {
        $user = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
            'username' => $username,
            'email' => $username.'@example.test',
            'name' => $username,
            'password' => Hash::make($password),
        ]);
        $user->save();

        return $user;
    }
}
