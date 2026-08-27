<?php

namespace App\MoonShine\Resources\ClassAdviserSchedule;

use App\Models\ClassAdviserSchedule;
use App\MoonShine\Resources\ClassAdviserSchedule\Pages\ClassAdviserScheduleDetailPage;
use App\MoonShine\Resources\ClassAdviserSchedule\Pages\ClassAdviserScheduleFormPage;
use App\MoonShine\Resources\ClassAdviserSchedule\Pages\ClassAdviserScheduleIndexPage;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * Auto-generated MoonShine resource
 */
class ClassAdviserScheduleResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = ClassAdviserSchedule::class;

    public function getTitle(): string
    {
        return 'Class Adviser Schedules';
    }

    protected function pages(): array
    {
        return [
            ClassAdviserScheduleIndexPage::class,
            ClassAdviserScheduleFormPage::class,
            ClassAdviserScheduleDetailPage::class,
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Adviser Id'), 'adviser', resource: \App\MoonShine\Resources\Adviser\AdviserResource::class),
            BelongsTo::make(__('Class'), 'class', resource: \App\MoonShine\Resources\ClassesModel\ClassesModelResource::class),
            Text::make(__('Day'), 'day'),
            Text::make(__('Section'), 'section'),
            Text::make(__('Time Frame'), 'time_frame'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Adviser Id'), 'adviser', resource: \App\MoonShine\Resources\Adviser\AdviserResource::class),
            BelongsTo::make(__('Class'), 'class', resource: \App\MoonShine\Resources\ClassesModel\ClassesModelResource::class)->nullable(),
            Text::make(__('Day'), 'day'),
            Text::make(__('Section'), 'section'),
            Text::make(__('Time Frame'), 'time_frame'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Adviser Id'), 'adviser', resource: \App\MoonShine\Resources\Adviser\AdviserResource::class),
            BelongsTo::make(__('Class'), 'class', resource: \App\MoonShine\Resources\ClassesModel\ClassesModelResource::class),
            Text::make(__('Day'), 'day'),
            Text::make(__('Section'), 'section'),
            Text::make(__('Time Frame'), 'time_frame'),
        ];
    }
}
