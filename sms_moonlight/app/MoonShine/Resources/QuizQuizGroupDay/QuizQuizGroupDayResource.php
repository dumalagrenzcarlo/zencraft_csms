<?php

namespace App\MoonShine\Resources\QuizQuizGroupDay;

use \App\Models\QuizQuizGroupDay;
use App\MoonShine\Resources\QuizQuizGroupDay\Pages\QuizQuizGroupDayIndexPage;
use App\MoonShine\Resources\QuizQuizGroupDay\Pages\QuizQuizGroupDayFormPage;
use App\MoonShine\Resources\QuizQuizGroupDay\Pages\QuizQuizGroupDayDetailPage;
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
class QuizQuizGroupDayResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = QuizQuizGroupDay::class;

    
    public function getTitle(): string
    {
        return 'Quiz Quiz Group Days';
    }


    protected function pages(): array
    {
        return [
            QuizQuizGroupDayIndexPage::class,
            QuizQuizGroupDayFormPage::class,
            QuizQuizGroupDayDetailPage::class
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Quiz Id'), 'quiz', resource: \App\MoonShine\Resources\Quiz\QuizResource::class),
            BelongsTo::make(__('Quiz Group Day'), 'quizGroupDay', resource: \App\MoonShine\Resources\QuizGroupDay\QuizGroupDayResource::class),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Quiz Id'), 'quiz', resource: \App\MoonShine\Resources\Quiz\QuizResource::class),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Quiz Id'), 'quiz', resource: \App\MoonShine\Resources\Quiz\QuizResource::class),
        ];
    }
}
