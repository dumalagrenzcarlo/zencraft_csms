<?php

namespace App\MoonShine\Resources\StudentAccess;

use App\Models\StudentAccess;
use App\MoonShine\Fields\StudentBelongsTo;
use App\MoonShine\Resources\StudentAccess\Pages\StudentAccessDetailPage;
use App\MoonShine\Resources\StudentAccess\Pages\StudentAccessFormPage;
use App\MoonShine\Resources\StudentAccess\Pages\StudentAccessIndexPage;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;

/**
 * Auto-generated MoonShine resource
 */
class StudentAccessResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = StudentAccess::class;

    public function getTitle(): string
    {
        return 'Student Access';
    }

    protected function pages(): array
    {
        return [
            StudentAccessIndexPage::class,
            StudentAccessFormPage::class,
            StudentAccessDetailPage::class,
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Student Id'), 'student', resource: \App\MoonShine\Resources\Student\StudentResource::class),
            BelongsTo::make(__('User Id'), 'user', resource: \App\MoonShine\Resources\MoonShineUser\MoonShineUserResource::class),
            Checkbox::make(__('Active'), 'active'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            StudentBelongsTo::make(__('Student')),
            BelongsTo::make(__('User Id'), 'user', resource: \App\MoonShine\Resources\MoonShineUser\MoonShineUserResource::class),
            Checkbox::make(__('Active'), 'active'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Student Id'), 'student', resource: \App\MoonShine\Resources\Student\StudentResource::class),
            BelongsTo::make(__('User Id'), 'user', resource: \App\MoonShine\Resources\MoonShineUser\MoonShineUserResource::class),
            Checkbox::make(__('Active'), 'active'),
        ];
    }
}
