<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CollegeEnrollment;

use App\Models\CollegeEnrollment;
use App\Models\CollegeProgramCourse;
use App\MoonShine\Fields\StudentBelongsTo;
use App\MoonShine\Resources\CollegeEnrollment\Pages\CollegeEnrollmentIndexPage;
use App\MoonShine\Resources\CollegeEnrollmentCourse\CollegeEnrollmentCourseResource;
use App\MoonShine\Resources\CollegeProgram\CollegeProgramResource;
use App\MoonShine\Resources\CollegeStudentQuick\CollegeStudentQuickResource;
use App\MoonShine\Resources\SchoolYear\SchoolYearResource;
use App\Support\CollegeEnrollmentCourseAssigner;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Crud\Contracts\Page\DetailPageContract;
use MoonShine\Crud\Contracts\Page\FormPageContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Modal;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;

class CollegeEnrollmentResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    protected string $model = CollegeEnrollment::class;

    protected string $column = 'display_name';

    protected array $with = ['student', 'program', 'schoolYear'];

    public function getTitle(): string
    {
        return 'College Enrollments';
    }

    protected function pages(): array
    {
        return [
            CollegeEnrollmentIndexPage::class,
            FormPageContract::class,
            DetailPageContract::class,
        ];
    }

    protected function filters(): iterable
    {
        return [
            StudentBelongsTo::make(resource: CollegeStudentQuickResource::class),
            BelongsTo::make('Course', 'program', resource: CollegeProgramResource::class)
                ->nullable(),
            BelongsTo::make('School Year', 'schoolYear', resource: SchoolYearResource::class)
                ->nullable(),
            Select::make('Semester', 'semester')
                ->options(CollegeProgramCourse::SEMESTERS)
                ->nullable(),
            Select::make('Year Level', 'year_level')
                ->options($this->yearLevels())
                ->nullable(),
            Select::make('Status', 'status')
                ->options(CollegeEnrollment::STATUSES)
                ->nullable(),
        ];
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            BelongsTo::make('Student', 'student', fn ($student) => trim("$student->lastname, $student->firstname"), CollegeStudentQuickResource::class),
            BelongsTo::make('Course', 'program', resource: CollegeProgramResource::class),
            BelongsTo::make('School Year', 'schoolYear', resource: SchoolYearResource::class),
            Select::make('Semester', 'semester')->options(CollegeProgramCourse::SEMESTERS),
            Select::make('Year Level', 'year_level')->options($this->yearLevels()),
            Select::make('Status', 'status')->options(CollegeEnrollment::STATUSES),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            StudentBelongsTo::make(resource: CollegeStudentQuickResource::class)
                ->required()
                ->nullable()
                ->creatable(button: ActionButton::make('Add New Student')),
            BelongsTo::make('Course', 'program', resource: CollegeProgramResource::class)->required(),
            BelongsTo::make('School Year', 'schoolYear', resource: SchoolYearResource::class)->required(),
            Select::make('Semester', 'semester')->options(CollegeProgramCourse::SEMESTERS)->required(),
            Select::make('Year Level', 'year_level')->options($this->yearLevels())->required(),
            $this->statusField(),
            HasMany::make('Enrolled Classes / Grades', 'courses', resource: CollegeEnrollmentCourseResource::class)
                ->creatable()
                ->tabMode(),
        ];
    }

    protected function detailFields(): iterable
    {
        return $this->formFields();
    }

    protected function afterCreated(DataWrapperContract $item): DataWrapperContract
    {
        /** @var CollegeEnrollment $enrollment */
        $enrollment = $item->getOriginal()->refresh();
        app(CollegeEnrollmentCourseAssigner::class)->assignAvailableCourses($enrollment);

        return $item;
    }

    protected function afterUpdated(DataWrapperContract $item): DataWrapperContract
    {
        /** @var CollegeEnrollment $enrollment */
        $enrollment = $item->getOriginal()->refresh();
        app(CollegeEnrollmentCourseAssigner::class)->assignAvailableCourses($enrollment);

        return $item;
    }

    private function yearLevels(): array
    {
        return CollegeProgramCourse::YEAR_LEVELS;
    }

    private function statusField(): Select
    {
        $statusGuide = ActionButton::make('')
            ->icon('information-circle')
            ->customAttributes([
                'class' => 'enrolment-status-guide-trigger',
                'aria-label' => 'Open enrolment status explanation',
                'title' => 'Open status explanation',
            ])
            ->inModal(
                'Enrolment Status Guide',
                view('admin.college-enrollments.status-explanation')->render(),
                builder: static fn (Modal $modal): Modal => $modal
                    ->closeOutside(false)
                    ->autoClose(false),
            );

        $label = view('admin.college-enrollments.status-field-label', [
            'statusGuide' => (string) $statusGuide,
        ])->render();

        return Select::make($label, 'status')
            ->options(CollegeEnrollment::STATUSES)
            ->default('enrolled')
            ->required();
    }
}
