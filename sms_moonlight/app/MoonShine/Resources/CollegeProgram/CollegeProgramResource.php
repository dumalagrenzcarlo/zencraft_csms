<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CollegeProgram;

use App\Models\CollegeProgram;
use App\Models\CollegeProgramCourse;
use App\MoonShine\Resources\CollegeProgram\Pages\CollegeProgramIndexPage;
use App\MoonShine\Resources\CollegeProgramCourse\CollegeProgramCourseResource;
use MoonShine\Contracts\UI\TableBuilderContract;
use MoonShine\Crud\Contracts\Page\DetailPageContract;
use MoonShine\Crud\Contracts\Page\FormPageContract;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

class CollegeProgramResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    protected string $model = CollegeProgram::class;

    protected string $column = 'display_name';

    public function getTitle(): string
    {
        return 'College Courses';
    }

    protected function pages(): array
    {
        return [
            CollegeProgramIndexPage::class,
            FormPageContract::class,
            DetailPageContract::class,
        ];
    }

    protected function search(): array
    {
        return ['id', 'code', 'name'];
    }

    protected function filters(): iterable
    {
        return [
            Text::make('Course Code', 'code'),
            Text::make('Course Name', 'name'),
            Select::make('Active', 'active')
                ->options(['1' => 'Active', '0' => 'Inactive'])
                ->nullable(),
        ];
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            Text::make('Code', 'code'),
            Text::make('Course Name', 'name'),
            Checkbox::make('Active', 'active'),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            Text::make('Code', 'code')->required(),
            Text::make('Course Name', 'name')->required(),
            Checkbox::make('Active', 'active')->default(true),
            HasMany::make('Classes by Year and Semester', 'courses', resource: CollegeProgramCourseResource::class)
                ->creatable()
                ->modifyTable(
                    static fn (TableBuilderContract $table): TableBuilderContract => $table
                        ->topLeft(static fn (): array => [
                            Flex::make([
                                Select::make('Year Level', 'course_year_filter')
                                    ->options(['' => 'All Years'] + CollegeProgramCourse::YEAR_LEVELS)
                                    ->native()
                                    ->customAttributes(['data-college-course-filter' => 'year']),
                                Select::make('Semester', 'course_semester_filter')
                                    ->options(['' => 'All Semesters'] + CollegeProgramCourse::SEMESTERS)
                                    ->native()
                                    ->customAttributes(['data-college-course-filter' => 'semester']),
                            ])->class('college-course-filters items-end gap-3'),
                        ])
                        ->trAttributes(static fn ($data): array => [
                            'data-college-course-row' => '',
                            'data-course-year' => (string) data_get($data?->getOriginal(), 'year_level'),
                            'data-course-semester' => (string) data_get($data?->getOriginal(), 'semester'),
                        ])
                )
                ->tabMode(),
        ];
    }

    protected function detailFields(): iterable
    {
        return $this->formFields();
    }
}
