<?php

namespace App\MoonShine\Resources\ClassStudent;

use App\Models\ClassStudent;
use App\MoonShine\Fields\StudentBelongsTo;
use App\MoonShine\Resources\ClassesModel\ClassesModelResource;
use App\MoonShine\Resources\ClassStudent\Pages\ClassStudentDetailPage;
use App\MoonShine\Resources\ClassStudent\Pages\ClassStudentFormPage;
use App\MoonShine\Resources\ClassStudent\Pages\ClassStudentIndexPage;
use App\MoonShine\Resources\Student\StudentResource;
use App\Services\MoonShine\ClassStudentSaveHandler;
use MoonShine\Crud\Attributes\SaveHandler;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Traits\Resource\ResourceWithParent;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Textarea;

/**
 * Auto-generated MoonShine resource
 */
#[SaveHandler(ClassStudentSaveHandler::class)]
class ClassStudentResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    use ResourceWithParent;

    public string $model = ClassStudent::class;

    public function getTitle(): string
    {
        return 'Class Students';
    }

    protected function pages(): array
    {
        return [
            ClassStudentIndexPage::class,
            ClassStudentFormPage::class,
            ClassStudentDetailPage::class,
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Class'), 'class', resource: ClassesModelResource::class),
            BelongsTo::make(
                __('Student'),
                'student',
                fn ($item) => "$item->firstname $item->lastname",
                resource: StudentResource::class
            ),
            Checkbox::make(__('Hide Grade from Student'), 'hidden_grade'),
            Textarea::make(__('Notes'), 'notes'),
        ];
    }

    public function formFields(): array
    {
        $isHasManyModal = request()->filled('_relation');
        $isEdit = $isHasManyModal
            ? request()->filled('_key')
            : filled(moonshineRequest()->getItemID());

        return [
            ID::make(__('Id'), 'id'),
            Hidden::make(__('Class'), 'class_id')
                ->default($this->getParentId()),
            $isEdit
                ? StudentBelongsTo::make(__('Student'))
                : Select::make(__('Students'), 'student_ids')
                    ->options(
                        \App\Models\Student::query()
                            ->active()
                            ->orderBy('lastname')
                            ->orderBy('firstname')
                            ->get()
                            ->mapWithKeys(fn ($student) => [
                                $student->id => trim(($student->firstname ?? '').' '.($student->lastname ?? '')),
                            ])
                            ->toArray()
                    )
                    ->multiple()
                    ->searchable()
                    ->placeholder('--Search student by LRN/student number/name--'),
            Checkbox::make(__('Hide Grade from Student'), 'hidden_grade'),
            Textarea::make(__('Notes'), 'notes'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Class'), 'class', resource: ClassesModelResource::class),
            BelongsTo::make(
                __('Student'),
                'student',
                fn ($item) => "$item->firstname $item->lastname",
                resource: StudentResource::class
            ),
            Checkbox::make(__('Hide Grade from Student'), 'hidden_grade'),
            Textarea::make(__('Notes'), 'notes'),
        ];
    }

    protected function getParentResourceClassName(): string
    {
        return ClassesModelResource::class;
    }

    protected function getParentRelationName(): string
    {
        return 'class';
    }
}
