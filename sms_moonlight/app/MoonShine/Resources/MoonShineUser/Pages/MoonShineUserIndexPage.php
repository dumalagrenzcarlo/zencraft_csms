<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MoonShineUser\Pages;

use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\Support\RoleDefaultPassword;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\Color;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<MoonShineUserResource>
 */
final class MoonShineUserIndexPage extends IndexPage
{
    protected function buttons(): ListOf
    {
        $buttons = parent::buttons();

        $buttons->add(
            ActionButton::make('Reset password')
                ->icon('key')
                ->warning()
                ->method('resetPassword')
                ->withConfirm(
                    title: 'Reset password',
                    content: 'Are you sure you want to reset this user\'s password?',
                    button: 'Reset password',
                )
        );

        return $buttons;
    }

    #[AsyncMethod]
    public function resetPassword(
        CrudRequestContract $request,
        RoleDefaultPassword $defaultPassword
    ): void {
        $user = MoonshineUser::query()->findOrFail($request->getItemID());

        $defaultPassword->reset($user);

        toast(
            sprintf('%s password was reset to the %s default.', $user->name, $user->moonshineUserRole?->name ?? 'role'),
            ToastType::SUCCESS,
        );
    }

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make(
                __('moonshine::ui.resource.role'),
                'moonshineUserRole',
                formatted: static fn (MoonshineUserRole $model) => $model->name,
                resource: MoonShineUserRoleResource::class,
            )->badge(Color::PURPLE),

            Text::make(__('moonshine::ui.resource.name'), 'name'),

            Image::make(__('moonshine::ui.resource.avatar'), 'avatar')->modifyRawValue(fn (
                ?string $raw
            ): string => $raw ?? ''),

            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format("d.m.Y")
                ->sortable(),

            Text::make('Username', 'username')
                ->sortable(),
        ];
    }

    protected function filters(): iterable
    {
        return [
            BelongsTo::make(
                __('moonshine::ui.resource.role'),
                'moonshineUserRole',
                formatted: static fn (MoonshineUserRole $model) => $model->name,
                resource: MoonShineUserRoleResource::class,
            )->valuesQuery(static fn (Builder $q) => $q->select(['id', 'name'])),

            Text::make('Username', 'username'),
        ];
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract $component): TableBuilder
    {
        return $component->columnSelection();
    }
}
