<?php

namespace App\MoonShine\Resources\ClassesModel;

use App\Models\Adviser;
use App\Models\ClassesModel;
use App\Models\SchoolYear;
use App\MoonShine\Fields\StudentBelongsTo;
use App\MoonShine\Resources\Adviser\AdviserResource;
use App\MoonShine\Resources\ClassesModel\Pages\ClassesModelDetailPage;
use App\MoonShine\Resources\ClassesModel\Pages\ClassesModelFormPage;
use App\MoonShine\Resources\ClassesModel\Pages\ClassesModelIndexPage;
use App\MoonShine\Resources\Student\StudentResource;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * Auto-generated MoonShine resource
 */
class ClassesModelResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = ClassesModel::class;

    public function getTitle(): string
    {
        return 'Classes Models';
    }

    protected function pages(): array
    {
        return [
            ClassesModelIndexPage::class,
            ClassesModelFormPage::class,
            ClassesModelDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'section',
        ];
    }

    protected function filters(): iterable
    {
        return [
            Select::make('Adviser', 'adviser_id')
                ->options(
                    \App\Models\Adviser::query()
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->nullable(),

            Select::make('Grade', 'grade_id')
                ->options(
                    \App\Models\Grade::query()
                        ->pluck('grade', 'id')
                        ->toArray()
                )
                ->nullable(),

            Text::make('Section', 'section'),

            Select::make('School Year', 'school_year_id')
                ->options(
                    \App\Models\SchoolYear::query()
                        ->pluck('school_year', 'id')
                        ->toArray()
                )
                ->nullable(),

            Checkbox::make('Active', 'active'),
            Checkbox::make('Enable Assignments and Activities', 'enable_assignments'),
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(
                'Adviser',
                'adviser',
                fn ($item) => "$item->name"
            ),
            BelongsTo::make(
                __('Grade'),
                'grade',
                fn ($item) => "$item->grade"
            ),
            Text::make(__('Section'), 'section'),
            Date::make(__('Class Start Time'), 'start_time')
                ->inputFormat('H:i')
                ->format('h:i A')
                ->setAttribute('type', 'time'),
            Date::make(__('Class End Time'), 'end_time')
                ->inputFormat('H:i')
                ->format('h:i A')
                ->setAttribute('type', 'time'),
            BelongsTo::make(
                __('School Year'),
                'schoolYear',
                fn ($item) => "$item->school_year"
            ),
            Number::make(__('Number of Grading Periods'), 'grading_period_count'),
            Checkbox::make(__('Active'), 'active'),
            Checkbox::make(__('Enable Assignments and Activities'), 'enable_assignments'),
        ];
    }

    public function formFields(): array
    {
        $classId = (int) moonshineRequest()->getItemID() ?: null;
        $schoolYearId = $this->selectedSchoolYearId($classId);
        $selectedAdviserId = $classId
            ? ClassesModel::query()->whereKey($classId)->value('adviser_id')
            : null;

        $schoolYear = Select::make(__('School Year'), 'school_year_id')
            ->options(
                SchoolYear::query()
                    ->orderByDesc('school_year')
                    ->pluck('school_year', 'id')
                    ->toArray()
            )
            ->default($schoolYearId)
            ->required();

        $adviser = BelongsTo::make(
            __('Adviser'),
            'adviser',
            fn ($item) => $item->name,
            resource: AdviserResource::class,
        )
            ->required()
            ->searchable()
            ->valuesQuery(
                fn (Builder $query): Builder => $this->uniqueAdviserOptions(
                    $query,
                    $selectedAdviserId,
                )
            );

        return [
            ID::make(__('Id'), 'id'),
            $schoolYear,
            $adviser,
            BelongsTo::make(
                __('Grade'),
                'grade',
                fn ($item) => "$item->grade"
            ),
            Text::make(__('Section'), 'section'),
            Date::make(__('Class Start Time'), 'start_time')
                ->inputFormat('H:i')
                ->format('h:i A')
                ->setAttribute('type', 'time')
                ->required()
                ->hint(__('The first student scan after this time is marked late.')),
            Date::make(__('Class End Time'), 'end_time')
                ->inputFormat('H:i')
                ->format('h:i A')
                ->setAttribute('type', 'time')
                ->required(),
            Number::make(__('Number of Grading Periods'), 'grading_period_count')
                ->min(1)
                ->max(4)
                ->default(4)
                ->hint(__('Choose 1-4. Example: 4 = four quarters; 2 = two semesters or terms.')),
            Checkbox::make(__('Active'), 'active'),
            Checkbox::make(__('Enable Assignments and Activities'), 'enable_assignments'),
            HasMany::make(__('Students'), 'classStudents', resource: \App\MoonShine\Resources\ClassStudent\ClassStudentResource::class)
                ->creatable()->tabMode(),
            BelongsToMany::make(
                __('Subjects'),
                'subjects',
                fn ($item) => $item->subject,
                resource: \App\MoonShine\Resources\Subject\SubjectResource::class
            )->horizontalMode(minColWidth: '180px'),
        ];
    }

    private function selectedSchoolYearId(?int $classId): ?int
    {
        $requestedSchoolYearId = request()->integer('school_year_id');

        if ($requestedSchoolYearId > 0) {
            return $requestedSchoolYearId;
        }

        if ($classId) {
            return ClassesModel::query()
                ->whereKey($classId)
                ->value('school_year_id');
        }

        return SchoolYear::query()
            ->where('active', true)
            ->value('id')
            ?? SchoolYear::query()->orderByDesc('school_year')->value('id');
    }

    private function uniqueAdviserOptions(Builder $query, ?int $selectedAdviserId): Builder
    {
        $selectedName = $selectedAdviserId
            ? Adviser::query()->whereKey($selectedAdviserId)->value('name')
            : null;
        $normalizedSelectedName = filled($selectedName)
            ? mb_strtolower(trim((string) $selectedName))
            : null;
        $canonicalAdviserIds = Adviser::query()
            ->teachers()
            ->selectRaw('MIN(id)')
            ->groupByRaw('LOWER(TRIM(name))');

        return $query
            ->teachers()
            ->where(function (Builder $advisers) use (
                $canonicalAdviserIds,
                $normalizedSelectedName,
                $selectedAdviserId,
            ): void {
                $advisers->where(function (Builder $canonical) use (
                    $canonicalAdviserIds,
                    $normalizedSelectedName,
                ): void {
                    $canonical
                        ->whereIn('id', $canonicalAdviserIds)
                        ->when(
                            $normalizedSelectedName,
                            fn (Builder $differentName): Builder => $differentName
                                ->whereRaw('LOWER(TRIM(name)) <> ?', [$normalizedSelectedName])
                        );
                });

                if ($selectedAdviserId) {
                    $advisers->orWhere('id', $selectedAdviserId);
                }
            })
            ->orderBy('name')
            ->orderBy('id');
    }

    public function detailsFields(): array
    {
        $classId = (int) moonshineRequest()->getItemID();

        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(
                'Adviser',
                'adviser',
                fn ($item) => "$item->name"
            ),
            BelongsTo::make(
                __('Grade'),
                'grade',
                fn ($item) => "$item->grade"
            ),
            Text::make(__('Section'), 'section'),
            Date::make(__('Class Start Time'), 'start_time')
                ->inputFormat('H:i')
                ->format('h:i A')
                ->setAttribute('type', 'time'),
            Date::make(__('Class End Time'), 'end_time')
                ->inputFormat('H:i')
                ->format('h:i A')
                ->setAttribute('type', 'time'),
            BelongsTo::make(
                __('School Year'),
                'schoolYear',
                fn ($item) => "$item->school_year"
            ),
            Number::make(__('Number of Grading Periods'), 'grading_period_count'),
            Checkbox::make(__('Active'), 'active'),
            Checkbox::make(__('Enable Assignments and Activities'), 'enable_assignments'),
            HasMany::make(__('Students'), 'classStudents', resource: \App\MoonShine\Resources\ClassStudent\ClassStudentResource::class)
                ->fields([
                    ID::make(__('Id'), 'id'),
                    StudentBelongsTo::make(__('Student')),
                    Checkbox::make(__('Hide Grade from Student'), 'hidden_grade'),
                    Textarea::make(__('Notes'), 'notes'),
                ])
                ->creatable()
                ->modifyItemButtons(function ($detail, $edit, $delete, $massDelete): array {
                    $detail->setUrl(
                        fn ($classStudent): string => app(StudentResource::class)
                            ->getDetailPageUrl($classStudent->student_id)
                    );

                    return [$detail, $edit, $delete, $massDelete];
                })
                ->buttons([
                    ActionButton::make(__('Export'))
                        ->icon('arrow-down-tray')
                        ->setUrl(route('admin.students.export', ['class_id' => $classId])),
                ])
                ->tabMode(),
            HasMany::make(__('Subjects'), 'classSubjects', resource: \App\MoonShine\Resources\ClassSubject\ClassSubjectResource::class)
                ->fields([
                    ID::make(__('Id'), 'id'),
                    BelongsTo::make(__('Subject'), 'subject', fn ($item) => $item->subject),
                ])
                ->creatable()
                ->modifyBuilder(fn ($query) => $query
                    ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
                    ->select('class_subjects.*')
                    ->orderByRaw('subjects.record_order IS NULL')
                    ->orderBy('subjects.record_order')
                    ->orderBy('subjects.subject'))
                ->tabMode(),
        ];
    }
}
