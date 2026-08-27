<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CollegeProgramCourse;

use App\Models\CollegeProgramCourse;
use App\Models\SchoolYear;
use App\MoonShine\Resources\CollegeCourseOffering\CollegeCourseOfferingResource;
use App\MoonShine\Resources\CollegeProgram\CollegeProgramResource;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

class CollegeProgramCourseResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    protected string $model = CollegeProgramCourse::class;

    protected string $column = 'display_name';

    protected array $with = ['program', 'prerequisiteProgramCourse'];

    public function getTitle(): string
    {
        return 'College Classes';
    }

    protected function search(): array
    {
        return ['id', 'course_code', 'description'];
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            BelongsTo::make('Program', 'program', resource: CollegeProgramResource::class),
            Text::make('Class Code', 'course_code'),
            Text::make('Description', 'description'),
            Select::make('Year Level', 'year_level')->options($this->yearLevels()),
            Select::make('Semester', 'semester')->options(CollegeProgramCourse::SEMESTERS),
            Number::make('Units', 'units'),
            Number::make('Order', 'course_order'),
            BelongsTo::make('Prerequisite Class', 'prerequisiteProgramCourse', resource: self::class),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            BelongsTo::make('Course', 'program', resource: CollegeProgramResource::class)
                ->nullable()
                ->placeholder('Select a course')
                ->native()
                ->required(),
            Text::make('Class Code', 'course_code')->required(),
            Textarea::make('Description', 'description')->required(),
            Select::make('Year Level', 'year_level')
                ->options($this->yearLevels())
                ->nullable()
                ->placeholder('Select a year level')
                ->native()
                ->required(),
            Select::make('Semester', 'semester')
                ->options(CollegeProgramCourse::SEMESTERS)
                ->nullable()
                ->placeholder('Select a semester')
                ->native()
                ->required(),
            Number::make('Units', 'units')->min(0)->max(12)->step(0.25)->default(3)->required(),
            Number::make('Order', 'course_order')
                ->min(0)
                ->max(999)
                ->default(0)
                ->hint('Controls the class order within the year and semester.'),
            BelongsTo::make('Prerequisite Class', 'prerequisiteProgramCourse', resource: self::class)
                ->nullable()
                ->placeholder('Select a prerequisite class (optional)'),
        ];
    }

    protected function detailFields(): iterable
    {
        return $this->indexFields();
    }

    public function getRedirectAfterSave(): ?string
    {
        if ($this->isRecentlyCreated()) {
            return $this->classScheduleFormUrl();
        }

        return parent::getRedirectAfterSave();
    }

    public function modifySaveResponse(JsonResponse $response): JsonResponse
    {
        if ($this->isRecentlyCreated()) {
            return $response->redirect($this->classScheduleFormUrl());
        }

        return parent::modifySaveResponse($response);
    }

    private function classScheduleFormUrl(): string
    {
        $params = [
            'program_course_id' => $this->getCastedData()?->getKey(),
            'school_year_id' => SchoolYear::query()->where('active', true)->value('id'),
        ];

        return app(CollegeCourseOfferingResource::class)->getFormPageUrl(
            params: array_filter($params, static fn ($value): bool => filled($value))
        );
    }

    private function yearLevels(): array
    {
        return CollegeProgramCourse::YEAR_LEVELS;
    }
}
