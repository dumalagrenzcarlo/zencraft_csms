<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\QuizGroup\Pages;

use App\MoonShine\Resources\QuizGroup\QuizGroupResource;
use MoonShine\UI\Components\ActionButton;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\ID;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Modal;
use Throwable;

/**
 * @extends IndexPage<QuizGroupResource>
 */
class QuizGroupIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return $this->getResource()->indexFields(); // reuse dynamic fields
    }

    protected function buttons(): ListOf
    {
        $buttons = parent::buttons();

        $buttons->add(
            ActionButton::make('')
                ->icon('chart-bar')
                ->primary()
                ->async()
                ->inModal(
                    title: static fn ($item, $ctx) => $item ? 'Quiz Group Scores' : 'Quiz Group Scores',
                    content: '',
                    name: static fn ($item, $ctx) => 'quiz-group-scores-' . ($item?->getKey() ?? 'default'),
                    builder: static fn (Modal $modal, $ctx): Modal => $modal->wide()->closeOutside(false)
                )
                ->setUrl(static fn ($item): string => route('admin.quiz-groups.scores', ['quizGroup' => $item]))
        );

        return $buttons;
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [];
    }

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [];
    }

    /**
     * @return list<Metric>
     */
    protected function metrics(): array
    {
        return [];
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}
