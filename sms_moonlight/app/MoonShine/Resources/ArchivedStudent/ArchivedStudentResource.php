<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\ArchivedStudent;

use App\Models\ClassesModel;
use App\Models\Student;
use App\MoonShine\Resources\ArchivedStudent\Pages\ArchivedStudentIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

final class ArchivedStudentResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = Student::class;

    protected string $column = 'lrn';

    protected array $with = ['classStudents.class.grade', 'classStudents.class.schoolYear'];

    public function getTitle(): string
    {
        return 'Archived Students';
    }

    public function uriKey(): string
    {
        return 'archived-students';
    }

    protected function pages(): array
    {
        return [ArchivedStudentIndexPage::class];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->empty();
    }

    protected function search(): array
    {
        return ['lrn', 'firstname', 'middlename', 'lastname'];
    }

    protected function filters(): iterable
    {
        return [
            Select::make(__('Class'), 'class_id')
                ->options($this->classOptions())
                ->nullable()
                ->searchable()
                ->onApply(fn ($query, $value) => $query->whereHas(
                    'classStudents',
                    fn ($classStudents) => $classStudents->where('class_id', $value),
                )),
            Select::make(__('Gender'), 'gender')
                ->options(['male' => 'Male', 'female' => 'Female'])
                ->nullable(),
            DateRange::make(__('Archived On'), 'archive_date'),
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $search = trim((string) request()->query('search', ''));

        return $builder
            ->archived()
            ->when($search !== '', static function ($query) use ($search): void {
                $query->where(static function ($query) use ($search): void {
                    $query->where('lrn', 'like', "%{$search}%")
                        ->orWhere('firstname', 'like', "%{$search}%")
                        ->orWhere('middlename', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('archive_date')
            ->orderBy('lastname');
    }

    protected function modifyItemQueryBuilder(Builder $builder): Builder
    {
        return $this->modifyQueryBuilder($builder);
    }

    public function indexFields(): array
    {
        return [
            Text::make(__('Student Number'), 'lrn'),
            Text::make(__('Name'), 'full_name', static fn (Student $student): string => trim(
                $student->lastname.', '.$student->firstname.' '.$student->middlename,
            )),
            Text::make(__('Last Class'), 'last_class', static function (Student $student): string {
                $enrollment = $student->classStudents->sortByDesc('id')->first();

                if (! $enrollment?->class) {
                    return '—';
                }

                $class = $enrollment->class;
                $label = trim(($class->grade?->grade ?? '').' — '.$class->section, ' —');
                $schoolYear = $class->schoolYear?->school_year;

                return $schoolYear ? "{$label} ({$schoolYear})" : $label;
            }),
            Date::make(__('Archived On'), 'archive_date')
                ->withTime()
                ->format('M d, Y h:i A'),
        ];
    }

    /** @return array<int, string> */
    private function classOptions(): array
    {
        return ClassesModel::query()
            ->with(['grade', 'schoolYear'])
            ->whereHas('classStudents.student', static fn ($query) => $query->archived())
            ->orderByDesc('school_year_id')
            ->orderBy('section')
            ->get()
            ->mapWithKeys(static fn (ClassesModel $class): array => [
                $class->id => trim(($class->grade?->grade ?? '').' — '.$class->section, ' —')
                    .' ('.($class->schoolYear?->school_year ?? 'No school year').')',
            ])
            ->all();
    }
}
