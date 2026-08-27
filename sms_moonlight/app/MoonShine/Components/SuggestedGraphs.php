<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(array $items)
 */
final class SuggestedGraphs extends MoonShineComponent
{
    protected string $view = 'admin.components.suggested-graphs';

    /**
     * @param list<array{title: string, description: string}> $items
     */
    public function __construct(public array $items)
    {
        parent::__construct();
    }

    protected function viewData(): array
    {
        return [
            'items' => $this->items,
        ];
    }
}
