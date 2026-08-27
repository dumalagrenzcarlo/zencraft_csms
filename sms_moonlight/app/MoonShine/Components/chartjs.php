<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\AssetManager\Js;
use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make()
 */
final class chartjs extends MoonShineComponent
{
    protected string $view = 'admin.components.chartjs';

    public array $chartData = [];

    public array $chartOptions = [];

    public string $type = 'line';

    public int $columnSpan = 12;

    public string $uniqueId;

    public function __construct()
    {
        parent::__construct();

        $this->uniqueId = 'chartjs-'.uniqid();
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function chartData(array $data): static
    {
        $this->chartData = $data;

        return $this;
    }

    public function chartOptions(array $options): static
    {
        $this->chartOptions = $options;

        return $this;
    }

    public function columnSpan(int $span): static
    {
        $this->columnSpan = $span;

        return $this;
    }

    protected function viewData(): array
    {
        return [
            'id' => $this->uniqueId,
            'type' => $this->type,
            'chartData' => $this->chartData,
            'chartOptions' => $this->chartOptions,
            'columnSpan' => $this->columnSpan,
        ];
    }

    protected function assets(): array
    {
        return [
            Js::make(asset('vendor/chartjs/chart.umd.js')),
        ];
    }
}
