<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\ClassesModel\Pages;

use App\MoonShine\Resources\ClassesModel\ClassesModelResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;

/**
 * @extends FormPage<ClassesModelResource>
 */
class ClassesModelFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        $formFields = $this->getResource()->formFields();

        // Keep the class start and end time fields together in the left column.
        $half = 7;

        $leftFields = array_slice($formFields, 0, $half);
        $rightFields = array_slice($formFields, $half);

        return [
            Box::make([
                Grid::make([
                    Column::make($leftFields)
                        ->columnSpan(6),

                    Column::make($rightFields)
                        ->columnSpan(6),
                ]),
            ]),
        ];
    }

    protected function formButtons(): ListOf
    {
        $buttons = parent::formButtons();

        $buttons->add(
            ActionButton::make(
                __('Back'),
                $this->getResource()->getIndexPageUrl()
            )
        );

        return $buttons;
    }

    protected function rules(DataWrapperContract $item): array
    {
        $schoolYearId = request()->integer('school_year_id');
        $adviserRules = [
            'required',
            'integer',
            'exists:advisers,id',
        ];

        return [
            'school_year_id' => ['required', 'integer', 'exists:school_year,id'],
            'adviser_id' => $adviserRules,
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'grading_period_count' => ['nullable', 'integer', 'between:1,4'],
        ];
    }

    protected function modifyFormComponent(FormBuilderContract $component): FormBuilderContract
    {
        return $component;
    }
}
