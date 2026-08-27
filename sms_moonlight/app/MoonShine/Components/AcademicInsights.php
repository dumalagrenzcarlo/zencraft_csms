<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(array $summary, array $students)
 */
final class AcademicInsights extends MoonShineComponent
{
    protected string $view = 'admin.components.academic-insights';

    /**
     * @param  array{expected_grade_records: int, grade_records: int, grade_coverage: float, at_risk_students: int, quiz_enabled: bool, quiz_participants: int, enrolled_students: int, quiz_participation: float, quiz_answers: int, quiz_accuracy: ?float}  $summary
     * @param  list<array{name: string, class: string, average: float, status: string, tone: string, url: string}>  $students
     */
    public function __construct(
        public array $summary,
        public array $students,
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'summary' => $this->summary,
            'students' => $this->students,
        ];
    }
}
