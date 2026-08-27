<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(array $summary, array $students)
 */
final class RecordCompleteness extends MoonShineComponent
{
    protected string $view = 'admin.components.record-completeness';

    /**
     * @param  array{students: int, rfid_enabled: bool, missing_rfid: int, missing_photo: int, missing_dob: int, missing_guardian: int}  $summary
     * @param  list<array{name: string, class: string, issues: string, issue_count: int, url: string}>  $students
     */
    public function __construct(
        public array $summary,
        public array $students,
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return ['summary' => $this->summary, 'students' => $this->students];
    }
}
