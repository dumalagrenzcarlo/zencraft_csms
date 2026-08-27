<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\StudentDocument\Pages;

use App\MoonShine\Resources\StudentDocument\StudentDocumentResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Box;

/**
 * @extends FormPage<StudentDocumentResource>
 */
class StudentDocumentFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [Box::make($this->getResource()->formFields())];
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons()
            ->add(ActionButton::make(__('Back'), $this->getResource()->getIndexPageUrl()));
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'file' => [$item->getKey() ? 'sometimes' : 'required'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
