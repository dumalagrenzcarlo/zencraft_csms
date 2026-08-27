<?php

namespace App\MoonShine\Resources\ClassSubject;

use App\Models\ClassSubject;
use App\MoonShine\Resources\ClassSubject\Pages\ClassSubjectDetailPage;
use App\MoonShine\Resources\ClassSubject\Pages\ClassSubjectFormPage;
use App\MoonShine\Resources\ClassSubject\Pages\ClassSubjectIndexPage;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;

/**
 * Auto-generated MoonShine resource
 */
class ClassSubjectResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = ClassSubject::class;

    public function getTitle(): string
    {
        return 'Class Subjects';
    }

    protected function pages(): array
    {
        return [
            ClassSubjectIndexPage::class,
            ClassSubjectFormPage::class,
            ClassSubjectDetailPage::class,
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Class'), 'class', resource: \App\MoonShine\Resources\ClassesModel\ClassesModelResource::class),
            BelongsTo::make(
                'Subject',
                'subject',
                fn ($item) => $item->subject
            )->searchable(),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Class'), 'class', resource: \App\MoonShine\Resources\ClassesModel\ClassesModelResource::class),
            BelongsTo::make(
                'Subject',
                'subject',
                fn ($item) => $item->subject
            )->searchable(),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Class'), 'class', resource: \App\MoonShine\Resources\ClassesModel\ClassesModelResource::class),
            BelongsTo::make(
                'Subject',
                'subject',
                fn ($item) => $item->subject
            )->searchable(),
        ];
    }
}
