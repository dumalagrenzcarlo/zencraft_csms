<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(array $schoolYears, ?int $selectedSchoolYearId)
 */
final class SchoolYearSelector extends MoonShineComponent
{
    protected string $view = 'admin.components.school-year-selector';

    /**
     * @param array<int, string> $schoolYears
     */
    public function __construct(
        public array $schoolYears,
        public ?int $selectedSchoolYearId,
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'schoolYears' => $this->schoolYears,
            'selectedSchoolYearId' => $this->selectedSchoolYearId,
        ];
    }
}
