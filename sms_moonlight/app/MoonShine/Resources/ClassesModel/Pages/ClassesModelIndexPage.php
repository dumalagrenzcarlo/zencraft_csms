<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\ClassesModel\Pages;

use App\Models\ClassesModel;
use App\MoonShine\Resources\ClassesModel\ClassesModelResource;
use App\Services\StudentArchiver;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Components\Table\TableBuilder;
use Throwable;

/**
 * @extends IndexPage<ClassesModelResource>
 */
class ClassesModelIndexPage extends IndexPage
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
        return parent::buttons()->add(
            ActionButton::make('')
                ->icon('archive-box')
                ->customAttributes([
                    'title' => __('Archive all students in class'),
                    'aria-label' => __('Archive all students in class'),
                ])
                ->method('archiveClassStudents', events: [$this->getListEventName()])
                ->withConfirm(
                    title: __('Archive Class Students'),
                    content: static fn (ClassesModel $class): string => __('Archive every active student in :section and disable their portal access?', [
                        'section' => $class->section,
                    ]),
                    button: __('Archive Students'),
                    name: static fn (ClassesModel $class): string => 'archive-class-students-'.$class->getKey(),
                )
        );
    }

    #[AsyncMethod]
    public function archiveClassStudents(CrudRequestContract $request): void
    {
        $class = ClassesModel::query()->findOrFail($request->getItemID());
        $count = app(StudentArchiver::class)->archiveClass($class);

        toast(trans_choice(
            '{0} No active students to archive.|{1} 1 student archived and portal access disabled.|[2,*] :count students archived and portal access disabled.',
            $count,
            ['count' => $count],
        ), ToastType::SUCCESS);
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
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     *
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer(),
        ];
    }

    /**
     * @return list<ComponentContract>
     *
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer(),
        ];
    }

    /**
     * @return list<ComponentContract>
     *
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer(),
        ];
    }
}
