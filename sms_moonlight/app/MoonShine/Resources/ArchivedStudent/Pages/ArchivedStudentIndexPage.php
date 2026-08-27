<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\ArchivedStudent\Pages;

use App\Models\ClassesModel;
use App\Models\Student;
use App\MoonShine\Resources\ArchivedStudent\ArchivedStudentResource;
use App\Services\StudentArchiver;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FlexibleRender;

/** @extends IndexPage<ArchivedStudentResource> */
final class ArchivedStudentIndexPage extends IndexPage
{
    protected bool $isLazy = false;

    /** @return list<FieldContract> */
    protected function fields(): iterable
    {
        return $this->getResource()->indexFields();
    }

    protected function buttons(): ListOf
    {
        return parent::buttons()->add(
            ActionButton::make(__('Restore'))
                ->icon('arrow-path')
                ->method('restoreStudent', events: [$this->getListEventName()])
                ->withConfirm(
                    title: __('Restore Student'),
                    content: static fn (Student $student): string => __('Restore :name and re-enable their portal access?', [
                        'name' => trim($student->firstname.' '.$student->lastname),
                    ]),
                    button: __('Restore Student'),
                    name: static fn (Student $student): string => 'restore-student-'.$student->getKey(),
                )
        );
    }

    #[AsyncMethod]
    public function restoreStudent(CrudRequestContract $request): void
    {
        $student = Student::query()->archived()->findOrFail($request->getItemID());
        app(StudentArchiver::class)->restore($student);

        toast(__('Student restored and portal access re-enabled.'), ToastType::SUCCESS);
    }

    /** @return list<ComponentContract> */
    protected function mainLayer(): array
    {
        $classes = ClassesModel::query()
            ->with(['grade', 'schoolYear'])
            ->whereHas('classStudents.student', static fn ($query) => $query->active())
            ->withCount(['classStudents as active_student_count' => static function ($query): void {
                $query->whereHas('student', static fn ($studentQuery) => $studentQuery->active());
            }])
            ->orderByDesc('school_year_id')
            ->orderBy('section')
            ->get();

        return [
            FlexibleRender::make(view('admin.students.archive-class-form', compact('classes'))),
            FlexibleRender::make(view('admin.students.archived-toolbar')),
            ...parent::mainLayer(),
        ];
    }
}
