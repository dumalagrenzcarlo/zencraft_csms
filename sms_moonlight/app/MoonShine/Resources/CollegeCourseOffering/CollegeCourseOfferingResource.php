<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CollegeCourseOffering;

use App\Models\CollegeCourseOffering;
use App\Models\CollegeProgram;
use App\Models\CollegeProgramCourse;
use App\Models\SchoolYear;
use App\MoonShine\Resources\CollegeCourseOffering\Pages\CollegeCourseOfferingIndexPage;
use App\MoonShine\Resources\CollegeEnrollmentCourse\CollegeEnrollmentCourseResource;
use App\MoonShine\Resources\CollegeProgramCourse\CollegeProgramCourseResource;
use App\MoonShine\Resources\Instructor\InstructorResource;
use App\MoonShine\Resources\SchoolYear\SchoolYearResource;
use MoonShine\Crud\Contracts\Page\DetailPageContract;
use MoonShine\Crud\Contracts\Page\FormPageContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

class CollegeCourseOfferingResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    protected string $model = CollegeCourseOffering::class;

    protected string $column = 'display_name';

    protected array $with = [
        'schoolYear',
        'programCourse.program',
        'instructor',
    ];

    public function getTitle(): string
    {
        return 'College Class Schedules';
    }

    protected function pages(): array
    {
        return [
            CollegeCourseOfferingIndexPage::class,
            FormPageContract::class,
            DetailPageContract::class,
        ];
    }

    protected function search(): array
    {
        return ['id', 'section', 'schedule', 'room'];
    }

    protected function filters(): iterable
    {
        return [
            BelongsTo::make('School Year', 'schoolYear', resource: SchoolYearResource::class)->nullable(),
            Select::make('Course', 'program_id')
                ->options(CollegeProgram::query()->orderBy('code')->pluck('name', 'id')->all())
                ->nullable()
                ->onApply(fn ($query, $value) => $query->whereHas('programCourse', fn ($class) => $class->where('program_id', $value))),
            BelongsTo::make('Class', 'programCourse', resource: CollegeProgramCourseResource::class)->nullable(),
            BelongsTo::make('Instructor / Professor', 'instructor', resource: InstructorResource::class)->nullable(),
            Select::make('Active', 'active')
                ->options(['1' => 'Active', '0' => 'Inactive'])
                ->nullable(),
        ];
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            BelongsTo::make('School Year', 'schoolYear', resource: SchoolYearResource::class),
            BelongsTo::make('Class', 'programCourse', resource: CollegeProgramCourseResource::class),
            BelongsTo::make('Instructor / Professor', 'instructor', resource: InstructorResource::class),
            Text::make('Section', 'section'),
            Text::make('Schedule', 'schedule'),
            Text::make('Room', 'room'),
            Number::make('Capacity', 'capacity'),
            Checkbox::make('Active', 'active'),
        ];
    }

    protected function formFields(): iterable
    {
        $activeSchoolYear = SchoolYear::query()
            ->where('active', true)
            ->first();
        $selectedProgramCourse = CollegeProgramCourse::query()
            ->find(request()->integer('program_course_id'));
        $formatProgramClass = static fn ($programClass): string => (string) $programClass->display_name;
        $formatInstructor = static fn ($instructor): string => collect([
            $instructor->name,
            $instructor->rank,
            $instructor->major,
        ])->filter()->implode(' — ');

        return [
            ID::make('ID', 'id'),
            BelongsTo::make('School Year', 'schoolYear', resource: SchoolYearResource::class)
                ->default($activeSchoolYear)
                ->required(),
            BelongsTo::make(
                'Course / Year / Semester Class',
                'programCourse',
                $formatProgramClass,
                resource: CollegeProgramCourseResource::class
            )
                ->default($selectedProgramCourse)
                ->required()
                ->placeholder('Search by course, class code, year level, or semester')
                ->asyncSearch(
                    column: 'course_code',
                    searchQuery: static function ($query, ?string $term) {
                        $term = trim((string) $term);

                        if ($term === '') {
                            return $query
                                ->orderBy('year_level')
                                ->orderBy('semester')
                                ->orderBy('course_order')
                                ->orderBy('course_code');
                        }

                        $normalized = mb_strtolower($term);
                        $yearLevel = match (true) {
                            preg_match('/\b(?:1st|first)\s+year\b/u', $normalized) === 1 => 1,
                            preg_match('/\b(?:2nd|second)\s+year\b/u', $normalized) === 1 => 2,
                            preg_match('/\b(?:3rd|third)\s+year\b/u', $normalized) === 1 => 3,
                            preg_match('/\b(?:4th|fourth)\s+year\b/u', $normalized) === 1 => 4,
                            default => null,
                        };
                        $semester = match (true) {
                            preg_match('/\b(?:1st|first)\s+semester\b/u', $normalized) === 1 => 1,
                            preg_match('/\b(?:2nd|second)\s+semester\b/u', $normalized) === 1 => 2,
                            default => null,
                        };
                        $textSearch = preg_replace([
                            '/\b(?:1st|first|2nd|second|3rd|third|4th|fourth)\s+year\b/u',
                            '/\b(?:1st|first|2nd|second)\s+semester\b/u',
                        ], ' ', $normalized);
                        $terms = preg_split('/\s+/u', trim((string) $textSearch)) ?: [];

                        return $query
                            ->when($yearLevel, fn ($classes) => $classes->where('year_level', $yearLevel))
                            ->when($semester, fn ($classes) => $classes->where('semester', $semester))
                            ->when($terms !== [], function ($classes) use ($terms): void {
                                $classes->where(function ($textQuery) use ($terms): void {
                                    foreach ($terms as $searchTerm) {
                                        $needle = '%'.$searchTerm.'%';

                                        $textQuery->where(function ($class) use ($needle): void {
                                            $class
                                                ->where('course_code', 'like', $needle)
                                                ->orWhere('description', 'like', $needle)
                                                ->orWhereHas('program', function ($program) use ($needle): void {
                                                    $program
                                                        ->where('code', 'like', $needle)
                                                        ->orWhere('name', 'like', $needle);
                                                });
                                        });
                                    }
                                });
                            })
                            ->orderBy('year_level')
                            ->orderBy('semester')
                            ->orderBy('course_order')
                            ->orderBy('course_code');
                    },
                    formatted: $formatProgramClass,
                    limit: 20,
                )
                ->asyncOnInit(),
            BelongsTo::make(
                'Instructor / Professor',
                'instructor',
                $formatInstructor,
                resource: InstructorResource::class
            )
                ->required()
                ->placeholder('Search instructor or professor by name, rank, or department')
                ->asyncSearch(
                    column: 'name',
                    searchQuery: static function ($query, ?string $term) {
                        $terms = preg_split('/\s+/u', trim((string) $term)) ?: [];

                        foreach ($terms as $searchTerm) {
                            $needle = '%'.$searchTerm.'%';

                            $query->where(function ($instructors) use ($needle): void {
                                $instructors
                                    ->where('name', 'like', $needle)
                                    ->orWhere('rank', 'like', $needle)
                                    ->orWhere('major', 'like', $needle)
                                    ->orWhere('rfid_card_uid', 'like', $needle);
                            });
                        }

                        return $query->orderBy('name');
                    },
                    formatted: $formatInstructor,
                    limit: 20,
                )
                ->asyncOnInit(),
            Text::make('Section', 'section')->required(),
            Text::make('Schedule', 'schedule')->hint('Example: M/W/F 8:00-9:00 AM'),
            Text::make('Room', 'room'),
            Number::make('Capacity', 'capacity')->min(1)->max(999)->default(40)->required(),
            Checkbox::make('Active', 'active')->default(true),
            $this->studentEnrollmentsField(),
        ];
    }

    protected function detailFields(): iterable
    {
        return [
            ...$this->indexFields(),
            $this->studentEnrollmentsField(),
        ];
    }

    private function studentEnrollmentsField(): HasMany
    {
        return HasMany::make(
            'Student Enrollments / Grades',
            'enrollmentCourses',
            resource: CollegeEnrollmentCourseResource::class
        )
            ->creatable()
            ->tabMode();
    }
}
