<?php

namespace App\MoonShine\Resources\QuizQuizAnswer;

use \App\Models\QuizQuizAnswer;
use App\MoonShine\Resources\QuizQuizAnswer\Pages\QuizQuizAnswerIndexPage;
use App\MoonShine\Resources\QuizQuizAnswer\Pages\QuizQuizAnswerFormPage;
use App\MoonShine\Resources\QuizQuizAnswer\Pages\QuizQuizAnswerDetailPage;
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
class QuizQuizAnswerResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = QuizQuizAnswer::class;

    
    public function getTitle(): string
    {
        return 'Quiz Quiz Answers';
    }


    protected function pages(): array
    {
        return [
            QuizQuizAnswerIndexPage::class,
            QuizQuizAnswerFormPage::class,
            QuizQuizAnswerDetailPage::class
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Quiz Id'), 'quiz', resource: \App\MoonShine\Resources\Quiz\QuizResource::class),
            BelongsTo::make(__('Answer Id'), 'answer', resource: \App\MoonShine\Resources\QuizAnswer\QuizAnswerResource::class),
            Checkbox::make(__('Is Correct Answer'), 'is_correct_answer'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Quiz Id'), 'quiz', resource: \App\MoonShine\Resources\Quiz\QuizResource::class),
            BelongsTo::make(__('Answer Id'), 'answer', resource: \App\MoonShine\Resources\QuizAnswer\QuizAnswerResource::class),
            Checkbox::make(__('Is Correct Answer'), 'is_correct_answer'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Quiz Id'), 'quiz', resource: \App\MoonShine\Resources\Quiz\QuizResource::class),
            BelongsTo::make(__('Answer Id'), 'answer', resource: \App\MoonShine\Resources\QuizAnswer\QuizAnswerResource::class),
            Checkbox::make(__('Is Correct Answer'), 'is_correct_answer'),
        ];
    }
}
