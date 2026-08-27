<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\QuizGroupDay\Pages;

use App\MoonShine\Resources\QuizGroupDay\QuizGroupDayResource;
use App\MoonShine\Resources\QuizQuizGroupDay\QuizQuizGroupDayResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Fields\Relationships\RelationRepeater;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<QuizGroupDayResource>
 */
class QuizGroupDayFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                Grid::make([
                    Column::make([
                        Box::make(__('Settings'), [
                            Hidden::make(__('Quiz Group Id'), 'quiz_group_id'),
                            Text::make(__('Title'), 'title'),
                            Select::make(__('Day'), 'day')
                                ->options([
                                    'Monday' => 'Monday',
                                    'Tuesday' => 'Tuesday',
                                    'Wednesday' => 'Wednesday',
                                    'Thursday' => 'Thursday',
                                    'Friday' => 'Friday',
                                ]),
                            Number::make(__('Duration (seconds)'), 'quiz_duration_seconds'),
                        ]),
                    ]),
                    Column::make([
                        Box::make(__('Questions'), [
                            tap(
                                RelationRepeater::make(__('Questions'), 'quiz_quiz_group_days', resource: QuizQuizGroupDayResource::class)
                                    ->creatable(
                                        button: ActionButton::make(__('Add Questions'))->icon('plus-circle')->primary()
                                    )
                                    ->removable(),
                                function (RelationRepeater $field): void {
                                    if ($this->getResource()->isItemExists()) {
                                        $field->reorderable(
                                            fn (): string => route('admin.quiz-group-days.questions.sort', [
                                                'quizGroupDay' => $this->getResource()->getItemID(),
                                            ])
                                        );
                                    }
                                }
                            ),
                        ]),
                    ]),
                ]),
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
