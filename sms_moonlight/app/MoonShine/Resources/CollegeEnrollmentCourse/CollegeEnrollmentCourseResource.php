<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CollegeEnrollmentCourse;

use App\Models\CollegeEnrollmentCourse;
use App\Models\CollegeProgram;
use App\Models\CollegeProgramCourse;
use App\Models\SchoolYear;
use App\MoonShine\Resources\CollegeCourseOffering\CollegeCourseOfferingResource;
use App\MoonShine\Resources\CollegeEnrollment\CollegeEnrollmentResource;
use App\MoonShine\Resources\CollegeEnrollmentCourse\Pages\CollegeEnrollmentCourseIndexPage;
use App\MoonShine\Resources\CollegeProgramCourse\CollegeProgramCourseResource;
use MoonShine\Crud\Contracts\Page\DetailPageContract;
use MoonShine\Crud\Contracts\Page\FormPageContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

class CollegeEnrollmentCourseResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    protected string $model = CollegeEnrollmentCourse::class;

    protected array $with = ['enrollment.student', 'programCourse', 'offering.instructor'];

    public function getTitle(): string
    {
        return 'College Grades';
    }

    protected function pages(): array
    {
        return [
            CollegeEnrollmentCourseIndexPage::class,
            FormPageContract::class,
            DetailPageContract::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'enrollment.student' => ['lrn', 'firstname', 'middlename', 'lastname'],
            'programCourse' => ['course_code', 'description'],
            'offering' => ['section', 'schedule', 'room'],
            'remarks',
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make('Student', 'student_keyword')
                ->hint('Search by student name or Student Number.')
                ->onApply(function ($query, $value) {
                    $keyword = trim((string) $value);

                    if ($keyword === '') {
                        return $query;
                    }

                    return $query->whereHas('enrollment.student', function ($studentQuery) use ($keyword): void {
                        $search = '%'.$keyword.'%';
                        $studentQuery->where(function ($nameQuery) use ($search): void {
                            $nameQuery->where('lrn', 'like', $search)
                                ->orWhere('firstname', 'like', $search)
                                ->orWhere('middlename', 'like', $search)
                                ->orWhere('lastname', 'like', $search);
                        });
                    });
                }),
            Select::make('Course', 'program_id')
                ->options(CollegeProgram::query()->orderBy('code')->pluck('name', 'id')->all())
                ->nullable()
                ->onApply(fn ($query, $value) => $query->whereHas('enrollment', fn ($enrollment) => $enrollment->where('program_id', $value))),
            Select::make('School Year', 'school_year_id')
                ->options(SchoolYear::query()->orderByDesc('id')->pluck('school_year', 'id')->all())
                ->nullable()
                ->onApply(fn ($query, $value) => $query->whereHas('enrollment', fn ($enrollment) => $enrollment->where('school_year_id', $value))),
            Select::make('Year Level', 'year_level')
                ->options(CollegeProgramCourse::YEAR_LEVELS)
                ->nullable()
                ->onApply(fn ($query, $value) => $query->whereHas('enrollment', fn ($enrollment) => $enrollment->where('year_level', $value))),
            Select::make('Semester', 'semester')
                ->options(CollegeProgramCourse::SEMESTERS)
                ->nullable()
                ->onApply(fn ($query, $value) => $query->whereHas('enrollment', fn ($enrollment) => $enrollment->where('semester', $value))),
            BelongsTo::make('Class Schedule', 'offering', resource: CollegeCourseOfferingResource::class)
                ->nullable(),
            Select::make('Remarks', 'remarks')->options($this->remarks())->nullable(),
        ];
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            BelongsTo::make('Enrollment', 'enrollment', resource: CollegeEnrollmentResource::class),
            BelongsTo::make('Class', 'programCourse', resource: CollegeProgramCourseResource::class),
            BelongsTo::make('Class Schedule', 'offering', resource: CollegeCourseOfferingResource::class),
            Number::make('Prelim', 'prelim_grade'),
            Number::make('Midterm', 'midterm_grade'),
            Number::make('Pre-final', 'prefinal_grade'),
            Number::make('Final', 'final_grade'),
            Select::make('Remarks', 'remarks')->options($this->remarks()),
            Text::make('Submitted At', 'grades_submitted_at'),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            BelongsTo::make('Enrollment', 'enrollment', resource: CollegeEnrollmentResource::class)->required(),
            BelongsTo::make('Class', 'programCourse', resource: CollegeProgramCourseResource::class)->required(),
            BelongsTo::make('Class Schedule', 'offering', resource: CollegeCourseOfferingResource::class)->nullable(),
            Number::make('Prelim Grade', 'prelim_grade')->min(0)->max(100)->step(0.01)->nullable(),
            Number::make('Midterm Grade', 'midterm_grade')->min(0)->max(100)->step(0.01)->nullable(),
            Number::make('Pre-final Grade', 'prefinal_grade')->min(0)->max(100)->step(0.01)->nullable(),
            Number::make('Final Grade', 'final_grade')->min(0)->max(100)->step(0.01)->nullable(),
            Select::make('Remarks', 'remarks')->options($this->remarks())->nullable(),
        ];
    }

    protected function detailFields(): iterable
    {
        return $this->indexFields();
    }

    private function remarks(): array
    {
        return [
            'Passed' => 'Passed',
            'Failed' => 'Failed',
            'Incomplete' => 'Incomplete',
            'Dropped' => 'Dropped',
        ];
    }
}
