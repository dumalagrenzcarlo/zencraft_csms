<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(array $metrics)
 */
final class AttendanceSummary extends MoonShineComponent
{
    protected string $view = 'admin.components.attendance-summary';

    /**
     * @param  list<array{label: string, value: string}>  $metrics
     */
    public function __construct(public array $metrics)
    {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return ['metrics' => $this->metrics];
    }
}
