<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\StudentPaymentHistory\Pages;

use App\Models\ClassStudent;
use App\Models\CollegeEnrollment;
use App\Models\SchoolYear;
use App\Models\StudentPaymentHistory;
use App\MoonShine\Components\SchoolYearSelector;
use App\MoonShine\Resources\StudentPaymentHistory\StudentPaymentHistoryResource;
use Illuminate\Support\Collection;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;

/**
 * @extends IndexPage<StudentPaymentHistoryResource>
 */
class StudentPaymentHistoryIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return $this->getResource()->indexFields();
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function topLeftButtons(): ListOf
    {
        return parent::topLeftButtons()
            ->add(
                ActionButton::make(__('Export'))
                    ->icon('arrow-down-tray')
                    ->setUrl(route('admin.payments.export', request()->query()))
            );
    }

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [];
    }

    /**
     * @return list<Metric>
     */
    protected function metrics(): array
    {
        return [];
    }

    protected function modifyListComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     */
    protected function mainLayer(): array
    {
        $schoolYears = SchoolYear::query()
            ->orderByDesc('active')
            ->orderByDesc('id')
            ->pluck('school_year', 'id')
            ->all();
        $activeSchoolYearId = SchoolYear::query()->where('active', true)->value('id');
        $selectedSchoolYearId = $this->selectedSchoolYearId($schoolYears, $activeSchoolYearId);
        $studentIds = $this->studentIdsForSchoolYear($selectedSchoolYearId);
        $paymentSummary = $this->paymentSummary($studentIds);

        return [
            Grid::make([
                SchoolYearSelector::make($schoolYears, $selectedSchoolYearId),
            ]),
            Box::make('Payment Summary', [
                Grid::make([
                    ValueMetric::make('Total Collected')
                        ->value(fn () => $paymentSummary['total'])
                        ->icon('banknotes')
                        ->valueFormat(fn ($value) => 'PHP '.number_format((float) $value, 2))
                        ->customAttributes(['class' => 'compact-dashboard-metric'])
                        ->columnSpan(3, 6),

                    ValueMetric::make('Transactions')
                        ->value(fn () => $paymentSummary['transactions'])
                        ->icon('receipt-percent')
                        ->valueFormat(fn ($value) => number_format((int) $value))
                        ->customAttributes(['class' => 'compact-dashboard-metric'])
                        ->columnSpan(3, 6),

                    ValueMetric::make('Paying Students')
                        ->value(fn () => $paymentSummary['students'])
                        ->icon('user-group')
                        ->valueFormat(fn ($value) => number_format((int) $value))
                        ->customAttributes(['class' => 'compact-dashboard-metric'])
                        ->columnSpan(3, 6),

                    ValueMetric::make('Students by Payment Type')
                        ->value(fn () => $paymentSummary['payment_types'])
                        ->icon('credit-card')
                        ->customAttributes(['class' => 'compact-dashboard-metric compact-dashboard-metric--text'])
                        ->columnSpan(3, 6),
                ], gap: 2),
            ]),
            ...parent::mainLayer(),
        ];
    }

    /**
     * @param  array<int, string>  $schoolYears
     */
    private function selectedSchoolYearId(array $schoolYears, int|string|null $activeSchoolYearId = null): ?int
    {
        $requested = request()->integer('school_year_id');

        if ($requested > 0 && array_key_exists($requested, $schoolYears)) {
            return $requested;
        }

        if ($activeSchoolYearId !== null && array_key_exists((int) $activeSchoolYearId, $schoolYears)) {
            return (int) $activeSchoolYearId;
        }

        $firstSchoolYearId = array_key_first($schoolYears);

        return $firstSchoolYearId === null ? null : (int) $firstSchoolYearId;
    }

    private function studentIdsForSchoolYear(?int $schoolYearId): Collection
    {
        if ($schoolYearId === null) {
            return collect();
        }

        $classStudentIds = ClassStudent::query()
            ->where('school_year_id', $schoolYearId)
            ->distinct()
            ->pluck('student_id');

        $collegeStudentIds = CollegeEnrollment::query()
            ->where('school_year_id', $schoolYearId)
            ->distinct()
            ->pluck('student_id');

        return $classStudentIds
            ->merge($collegeStudentIds)
            ->map(fn ($studentId) => (int) $studentId)
            ->unique()
            ->values();
    }

    /**
     * @return array{total: float, transactions: int, students: int, payment_types: string}
     */
    private function paymentSummary(Collection $studentIds): array
    {
        if ($studentIds->isEmpty()) {
            return [
                'total' => 0.0,
                'transactions' => 0,
                'students' => 0,
                'payment_types' => 'No payment records',
            ];
        }

        $summary = StudentPaymentHistory::query()
            ->whereIn('student_id', $studentIds->all())
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->selectRaw('COUNT(*) as transactions')
            ->selectRaw('COUNT(DISTINCT student_id) as students')
            ->first();

        $paymentTypes = StudentPaymentHistory::query()
            ->leftJoin('payment_types', 'payment_types.id', '=', 'student_payment_histories.payment_type_id')
            ->whereIn('student_payment_histories.student_id', $studentIds->all())
            ->selectRaw("COALESCE(payment_types.name, 'Unspecified') as payment_type")
            ->selectRaw('COUNT(DISTINCT student_payment_histories.student_id) as student_count')
            ->groupBy('student_payment_histories.payment_type_id', 'payment_types.name')
            ->orderBy('payment_types.name')
            ->get()
            ->map(fn ($row) => $row->payment_type.' — '.number_format((int) $row->student_count))
            ->implode(', ');

        return [
            'total' => (float) $summary->total,
            'transactions' => (int) $summary->transactions,
            'students' => (int) $summary->students,
            'payment_types' => $paymentTypes !== '' ? $paymentTypes : 'No payment records',
        ];
    }
}
