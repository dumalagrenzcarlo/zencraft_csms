<?php

namespace App\MoonShine\Resources\QuizAnswer;

use \App\Models\QuizAnswer;
use App\MoonShine\Resources\QuizAnswer\Pages\QuizAnswerIndexPage;
use App\MoonShine\Resources\QuizAnswer\Pages\QuizAnswerFormPage;
use App\MoonShine\Resources\QuizAnswer\Pages\QuizAnswerDetailPage;
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
class QuizAnswerResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = QuizAnswer::class;

    public function getTitle(): string
    {
        return 'Quiz Answers';
    }

    protected function pages(): array
    {
        return [
            QuizAnswerIndexPage::class,
            QuizAnswerFormPage::class,
            QuizAnswerDetailPage::class
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Textarea::make(__('Answer'), 'answer'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Textarea::make(__('Answer'), 'answer'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Textarea::make(__('Answer'), 'answer'),
        ];
    }
}
