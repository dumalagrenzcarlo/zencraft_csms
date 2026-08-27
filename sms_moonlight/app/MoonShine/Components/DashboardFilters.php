<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(array $schoolYears, array $grades, array $classes, array $selected, ?string $boundaryMessage)
 */
final class DashboardFilters extends MoonShineComponent
{
    protected string $view = 'admin.components.dashboard-filters';

    /**
     * @param  array<int, string>  $schoolYears
     * @param  array<int, string>  $grades
     * @param  array<int, string>  $classes
     * @param  array{school_year_id: ?int, grade_id: ?int, class_id: ?int, date_from: ?string, date_to: ?string}  $selected
     */
    public function __construct(
        public array $schoolYears,
        public array $grades,
        public array $classes,
        public array $selected,
        public ?string $boundaryMessage = null,
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'schoolYears' => $this->schoolYears,
            'grades' => $this->grades,
            'classes' => $this->classes,
            'selected' => $this->selected,
            'boundaryMessage' => $this->boundaryMessage,
        ];
    }
}
