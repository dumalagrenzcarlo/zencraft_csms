<?php

namespace App\MoonShine\Resources\User;

use App\Models\User;
use App\MoonShine\Resources\User\Pages\UserIndexPage;
use App\MoonShine\Resources\User\Pages\UserFormPage;
use App\MoonShine\Resources\User\Pages\UserDetailPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Fields\Relationships\{BelongsTo,HasMany};
use MoonShine\UI\Fields\{
    ID,
    Checkbox,
    Text,
    Textarea,
    Date,
    Time,
    Number,
    Toggle,
    Json,
    Select
};

/**
 * Auto-generated MoonShine resource
 */
class UserResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = User::class;

    
    public function getTitle(): string
    {
        return 'Users';
    }


    protected function pages(): array
    {
        return [
            UserIndexPage::class,
            UserFormPage::class,
            UserDetailPage::class
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Name'), 'name'),
            Text::make(__('Password'), 'password'),
            Text::make(__('Remember Token'), 'remember_token'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Name'), 'name'),
            Text::make(__('Password'), 'password'),
            Text::make(__('Remember Token'), 'remember_token'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Name'), 'name'),
            Text::make(__('Password'), 'password'),
            Text::make(__('Remember Token'), 'remember_token'),
        ];
    }
}
