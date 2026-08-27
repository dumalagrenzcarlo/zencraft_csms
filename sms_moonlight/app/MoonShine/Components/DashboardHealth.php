<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(string $schoolYear, array $items)
 */
final class DashboardHealth extends MoonShineComponent
{
    protected string $view = 'admin.components.dashboard-health';

    /**
     * @param  list<array{tone: string, label: string, value: string, description: string, action_label: string, action_url: string}>  $items
     */
    public function __construct(
        public string $schoolYear,
        public array $items,
    ) {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'schoolYear' => $this->schoolYear,
            'items' => $this->items,
        ];
    }
}
