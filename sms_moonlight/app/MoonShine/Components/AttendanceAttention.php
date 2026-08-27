<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(array $students, int $recordedDays, float $threshold)
 */
final class AttendanceAttention extends MoonShineComponent
{
    protected string $view = 'admin.components.attendance-attention';

    /**
     * @param  list<array{name: string, lrn: string, class: string, present: int, absent: int, rate: float, last_attendance: string, url: string}>  $students
     */
    public function __construct(
        public array $students,
        public int $recordedDays,
        public float $threshold = 80.0,
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'students' => $this->students,
            'recordedDays' => $this->recordedDays,
            'threshold' => $this->threshold,
        ];
    }
}
