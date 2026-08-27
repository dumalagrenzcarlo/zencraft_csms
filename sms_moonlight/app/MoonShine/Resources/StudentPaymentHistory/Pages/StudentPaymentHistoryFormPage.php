<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\StudentPaymentHistory\Pages;

use App\MoonShine\Resources\StudentPaymentHistory\StudentPaymentHistoryResource;
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
 * @extends FormPage<StudentPaymentHistoryResource>
 */
class StudentPaymentHistoryFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        $formFields = $this->getResource()->formFields();
        $half = (int) ceil(count($formFields) / 2);

        return [
            Box::make([
                Grid::make([
                    Column::make(array_slice($formFields, 0, $half)),
                    Column::make(array_slice($formFields, $half)),
                ]),
            ]),
        ];
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons()
            ->add(ActionButton::make(__('Back'), $this->getResource()->getIndexPageUrl()));
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
