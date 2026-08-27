<?php

namespace App\MoonShine\Resources\Grade;

use \App\Models\Grade;
use App\MoonShine\Resources\Grade\Pages\GradeIndexPage;
use App\MoonShine\Resources\Grade\Pages\GradeFormPage;
use App\MoonShine\Resources\Grade\Pages\GradeDetailPage;
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
class GradeResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = Grade::class;

    public function getTitle(): string
    {
        return 'Grades';
    }

    protected function pages(): array
    {
        return [
            GradeIndexPage::class,
            GradeFormPage::class,
            GradeDetailPage::class
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Grade'), 'grade'),
            Checkbox::make(__('Active'), 'status')
                ->onValue('active')
                ->offValue('inactive'),
            Number::make(__('Terms'), 'term_count'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Grade'), 'grade'),
            Checkbox::make(__('Active'), 'status')
                ->onValue('active')
                ->offValue('inactive'),
            Number::make(__('Terms'), 'term_count')
                ->min(1)
                ->max(4)
                ->hint(__('Choose 1-4. Example: 4 = four quarters; 2 = two semesters or terms.')),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Grade'), 'grade'),
            Checkbox::make(__('Active'), 'status')
                ->onValue('active')
                ->offValue('inactive'),
            Number::make(__('Terms'), 'term_count'),
        ];
    }
}
