<?php

namespace App\MoonShine\Resources\QuizGroupDay;

use \App\Models\QuizGroupDay;
use App\MoonShine\Resources\QuizGroupDay\Pages\QuizGroupDayIndexPage;
use App\MoonShine\Resources\QuizGroupDay\Pages\QuizGroupDayFormPage;
use App\MoonShine\Resources\QuizGroupDay\Pages\QuizGroupDayDetailPage;
use App\MoonShine\Resources\QuizQuizGroupDay\QuizQuizGroupDayResource;
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
class QuizGroupDayResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = QuizGroupDay::class;

    
    public function getTitle(): string
    {
        return 'Quiz Group Days';
    }


    protected function pages(): array
    {
        return [
            QuizGroupDayIndexPage::class,
            QuizGroupDayFormPage::class,
            QuizGroupDayDetailPage::class
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Title'), 'title'),
            BelongsTo::make(__('Quiz Group'), 'quiz_group', resource: \App\MoonShine\Resources\QuizGroup\QuizGroupResource::class),
            Text::make(__('Day'), 'day'),
            Number::make(__('Duration (seconds)'), 'quiz_duration_seconds'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Hidden::make(__('Quiz Group Id'), 'quiz_group_id'),
            Text::make(__('Title'), 'title'),
            Select::make(__('Day'), 'day')
                ->options([
                    'Monday' => 'Monday',
                    'Tuesday' => 'Tuesday',
                    'Wednesday' => 'Wednesday',
                    'Thursday' => 'Thursday',
                    'Friday' => 'Friday',
                ]),
            Number::make(__('Duration (seconds)'), 'quiz_duration_seconds'),
            HasMany::make(__('Questions'), 'quiz_quiz_group_days', resource: QuizQuizGroupDayResource::class)
                ->creatable()
                ->searchable()
                ->tabMode(),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Title'), 'title'),
            BelongsTo::make(__('Quiz Group'), 'quiz_group', resource: \App\MoonShine\Resources\QuizGroup\QuizGroupResource::class),
            Text::make(__('Day'), 'day'),
            Number::make(__('Duration (seconds)'), 'quiz_duration_seconds'),
            HasMany::make(__('Questions'), 'quiz_quiz_group_days', resource: QuizQuizGroupDayResource::class)
                ->tabMode()
                ->searchable(),
        ];
    }
}
