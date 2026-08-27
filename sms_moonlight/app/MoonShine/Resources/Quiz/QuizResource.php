<?php

namespace App\MoonShine\Resources\Quiz;

use \App\Models\Quiz;
use App\MoonShine\Resources\Quiz\Pages\QuizIndexPage;
use App\MoonShine\Resources\Quiz\Pages\QuizFormPage;
use App\MoonShine\Resources\Quiz\Pages\QuizDetailPage;
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
class QuizResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = Quiz::class;

    public function getTitle(): string
    {
        return 'Quizzes';
    }

    protected function pages(): array
    {
        return [
            QuizIndexPage::class,
            QuizFormPage::class,
            QuizDetailPage::class
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Textarea::make(__('Question'), 'question'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Textarea::make(__('Question'), 'question'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Textarea::make(__('Question'), 'question'),
        ];
    }
}
