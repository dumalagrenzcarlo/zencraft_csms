<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\QuizGroup\Pages;

use App\Models\SchoolYear;
use App\MoonShine\Resources\QuizGroup\QuizGroupResource;
use App\MoonShine\Resources\Grade\GradeResource;
use App\MoonShine\Resources\QuizGroupDay\QuizGroupDayResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<QuizGroupResource>
 */
class QuizGroupFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make(__('Assign Quizzes to Week'), [
                Grid::make([
                    Column::make([
                        Hidden::make(__('School Year Id'), 'school_year_id')
                            ->setValue((int) (SchoolYear::query()->where('active', 1)->value('id') ?? SchoolYear::query()->value('id') ?? 0)),
                        BelongsTo::make(__('Grade Level'), 'grade', resource: GradeResource::class),
                    ]),
                    Column::make([
                        Text::make(__('Week (Monday to Friday)'), 'week')
                            ->placeholder('Aug 11 - Aug 15, 2025'),
                    ]),
                ]),
            ]),
            Box::make(__('Monday to Friday Quizzes'), [
                HasMany::make(__('Quiz Days'), 'quizGroupDays', resource: QuizGroupDayResource::class)
                    ->creatable()
                    ->tabMode()
                    ->searchable(),
            ]),
        ];
    }

    protected function formButtons(): ListOf
    {
        $buttons = parent::formButtons();

        $buttons->add(
            ActionButton::make(__('Back'), $this->getResource()->getIndexPageUrl())
        );

        return $buttons;
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [];
    }

    protected function modifyFormComponent(FormBuilderContract $component): FormBuilderContract
    {
        return $component;
    }
}
