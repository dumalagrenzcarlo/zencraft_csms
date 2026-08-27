<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(array $actions)
 */
final class DashboardQuickActions extends MoonShineComponent
{
    protected string $view = 'admin.components.dashboard-quick-actions';

    /**
     * @param  list<array{label: string, description: string, url: string, icon: string, tone: string}>  $actions
     */
    public function __construct(public array $actions)
    {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return ['actions' => $this->actions];
    }
}
