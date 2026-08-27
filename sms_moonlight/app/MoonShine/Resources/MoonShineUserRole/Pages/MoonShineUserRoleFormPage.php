<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MoonShineUserRole\Pages;

use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Models\MoonshineUserRole;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<MoonShineUserRoleResource, MoonshineUserRole>
 */
final class MoonShineUserRoleFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make(__('moonshine::ui.resource.role_name'), 'name')
                    ->required(),
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
        return [
            'name' => ['required', 'min:5'],
        ];
    }
}