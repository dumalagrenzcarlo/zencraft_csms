<?php

namespace App\MoonShine\Resources\StudentClass;

use App\Models\StudentClass;
use App\MoonShine\Fields\StudentBelongsTo;
use App\MoonShine\Resources\StudentClass\Pages\StudentClassDetailPage;
use App\MoonShine\Resources\StudentClass\Pages\StudentClassFormPage;
use App\MoonShine\Resources\StudentClass\Pages\StudentClassIndexPage;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * Auto-generated MoonShine resource
 */
class StudentClassResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = StudentClass::class;

    public function getTitle(): string
    {
        return 'Student Classes';
    }

    protected function pages(): array
    {
        return [
            StudentClassIndexPage::class,
            StudentClassFormPage::class,
            StudentClassDetailPage::class,
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Student'), 'student', resource: \App\MoonShine\Resources\Student\StudentResource::class),
            BelongsTo::make(__('Grade'), 'grade', resource: \App\MoonShine\Resources\Grade\GradeResource::class),
            Text::make(__('School Year'), 'school_year'),
            Text::make(__('Section'), 'section'),
            Text::make(__('Status'), 'status'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            StudentBelongsTo::make(__('Student')),
            BelongsTo::make(__('Grade'), 'grade', resource: \App\MoonShine\Resources\Grade\GradeResource::class),
            Text::make(__('School Year'), 'school_year'),
            Text::make(__('Section'), 'section'),
            Text::make(__('Status'), 'status'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Student'), 'student', resource: \App\MoonShine\Resources\Student\StudentResource::class),
            BelongsTo::make(__('Grade'), 'grade', resource: \App\MoonShine\Resources\Grade\GradeResource::class),
            Text::make(__('School Year'), 'school_year'),
            Text::make(__('Section'), 'section'),
            Text::make(__('Status'), 'status'),
        ];
    }
}
