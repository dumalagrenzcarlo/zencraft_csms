<?php

namespace App\MoonShine\Resources\QuizGroup;

use \App\Models\QuizGroup;
use App\Models\SchoolYear;
use App\MoonShine\Resources\QuizGroup\Pages\QuizGroupIndexPage;
use App\MoonShine\Resources\QuizGroup\Pages\QuizGroupFormPage;
use App\MoonShine\Resources\QuizGroup\Pages\QuizGroupDetailPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Fields\Relationships\{BelongsTo,HasMany};
use MoonShine\UI\Fields\{
    ID,
    Checkbox,
    Hidden,
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
class QuizGroupResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = QuizGroup::class;

    public function getTitle(): string
    {
        return 'Quiz Groups';
    }

    protected function pages(): array
    {
        return [
            QuizGroupIndexPage::class,
            QuizGroupFormPage::class,
            QuizGroupDetailPage::class
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('School Year'), 'school_year', resource: \App\MoonShine\Resources\SchoolYear\SchoolYearResource::class),
            BelongsTo::make(__('Grade Level'), 'grade', resource: \App\MoonShine\Resources\Grade\GradeResource::class),
            Text::make(__('Week'), 'week'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Hidden::make(__('School Year Id'), 'school_year_id')
                ->setValue((int) (SchoolYear::query()->where('active', 1)->value('id') ?? SchoolYear::query()->value('id') ?? 0)),
            BelongsTo::make(__('Grade Level'), 'grade', resource: \App\MoonShine\Resources\Grade\GradeResource::class),
            Text::make(__('Week'), 'week')
                ->placeholder('Aug 11 - Aug 15, 2025'),
            HasMany::make(__('Quiz Days'), 'quizGroupDays', resource: \App\MoonShine\Resources\QuizGroupDay\QuizGroupDayResource::class)
                ->creatable()
                ->tabMode()
                ->searchable(),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('School Year'), 'school_year', resource: \App\MoonShine\Resources\SchoolYear\SchoolYearResource::class),
            BelongsTo::make(__('Grade Level'), 'grade', resource: \App\MoonShine\Resources\Grade\GradeResource::class),
            Text::make(__('Week'), 'week'),
            HasMany::make(__('Quiz Days'), 'quizGroupDays', resource: \App\MoonShine\Resources\QuizGroupDay\QuizGroupDayResource::class)
                ->tabMode()
                ->searchable(),
        ];
    }
}
