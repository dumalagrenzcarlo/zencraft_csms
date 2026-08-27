<?php

namespace App\MoonShine\Resources\StudentQuizAnswer;

use App\Models\StudentQuizAnswer;
use App\MoonShine\Fields\StudentBelongsTo;
use App\MoonShine\Resources\StudentQuizAnswer\Pages\StudentQuizAnswerDetailPage;
use App\MoonShine\Resources\StudentQuizAnswer\Pages\StudentQuizAnswerFormPage;
use App\MoonShine\Resources\StudentQuizAnswer\Pages\StudentQuizAnswerIndexPage;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;

/**
 * Auto-generated MoonShine resource
 */
class StudentQuizAnswerResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = StudentQuizAnswer::class;

    public function getTitle(): string
    {
        return 'Student Quiz Answers';
    }

    protected function pages(): array
    {
        return [
            StudentQuizAnswerIndexPage::class,
            StudentQuizAnswerFormPage::class,
            StudentQuizAnswerDetailPage::class,
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Quiz Group Day'), 'quizGroupDay', resource: \App\MoonShine\Resources\QuizGroupDay\QuizGroupDayResource::class),
            BelongsTo::make(__('Quiz Id'), 'quiz', resource: \App\MoonShine\Resources\Quiz\QuizResource::class),
            BelongsTo::make(__('Answer'), 'answer', resource: \App\MoonShine\Resources\QuizAnswer\QuizAnswerResource::class),
            BelongsTo::make(__('Student Id'), 'student', resource: \App\MoonShine\Resources\Student\StudentResource::class),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Quiz Group Day'), 'quizGroupDay', resource: \App\MoonShine\Resources\QuizGroupDay\QuizGroupDayResource::class),
            BelongsTo::make(__('Quiz Id'), 'quiz', resource: \App\MoonShine\Resources\Quiz\QuizResource::class),
            BelongsTo::make(__('Answer'), 'answer', resource: \App\MoonShine\Resources\QuizAnswer\QuizAnswerResource::class),
            StudentBelongsTo::make(__('Student')),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Quiz Group Day'), 'quizGroupDay', resource: \App\MoonShine\Resources\QuizGroupDay\QuizGroupDayResource::class),
            BelongsTo::make(__('Quiz Id'), 'quiz', resource: \App\MoonShine\Resources\Quiz\QuizResource::class),
            BelongsTo::make(__('Answer'), 'answer', resource: \App\MoonShine\Resources\QuizAnswer\QuizAnswerResource::class),
            BelongsTo::make(__('Student Id'), 'student', resource: \App\MoonShine\Resources\Student\StudentResource::class),
        ];
    }
}
