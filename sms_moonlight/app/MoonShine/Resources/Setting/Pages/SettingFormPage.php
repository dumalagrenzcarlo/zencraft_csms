<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Setting\Pages;

use App\MoonShine\Resources\Setting\SettingResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Box;

/**
 * @extends FormPage<SettingResource>
 */
class SettingFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make($this->getResource()->formFields()),
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
