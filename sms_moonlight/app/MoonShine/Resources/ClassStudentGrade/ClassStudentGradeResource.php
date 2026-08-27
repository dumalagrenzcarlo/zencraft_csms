<?php

namespace App\MoonShine\Resources\ClassStudentGrade;

use App\Models\ClassStudentGrade;
use App\MoonShine\Fields\StudentBelongsTo;
use App\MoonShine\Resources\ClassStudentGrade\Pages\ClassStudentGradeDetailPage;
use App\MoonShine\Resources\ClassStudentGrade\Pages\ClassStudentGradeFormPage;
use App\MoonShine\Resources\ClassStudentGrade\Pages\ClassStudentGradeIndexPage;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;

/**
 * Auto-generated MoonShine resource
 */
class ClassStudentGradeResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = ClassStudentGrade::class;

    public function getTitle(): string
    {
        return 'Class Student Grades';
    }

    protected function pages(): array
    {
        return [
            ClassStudentGradeIndexPage::class,
            ClassStudentGradeFormPage::class,
            ClassStudentGradeDetailPage::class,
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Class'), 'class', resource: \App\MoonShine\Resources\ClassesModel\ClassesModelResource::class),
            BelongsTo::make(__('Student'), 'student', resource: \App\MoonShine\Resources\Student\StudentResource::class),
            BelongsTo::make(__('Grade'), 'grade', resource: \App\MoonShine\Resources\Grade\GradeResource::class),
            BelongsTo::make(__('Subject'), 'subject', resource: \App\MoonShine\Resources\Subject\SubjectResource::class),
            Number::make(__('Q1'), 'q1'),
            Number::make(__('Q2'), 'q2'),
            Number::make(__('Q3'), 'q3'),
            Number::make(__('Q4'), 'q4'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Class'), 'class', resource: \App\MoonShine\Resources\ClassesModel\ClassesModelResource::class),
            StudentBelongsTo::make(__('Student')),
            BelongsTo::make(__('Grade'), 'grade', resource: \App\MoonShine\Resources\Grade\GradeResource::class),
            BelongsTo::make(__('Subject'), 'subject', resource: \App\MoonShine\Resources\Subject\SubjectResource::class),
            Number::make(__('Q1'), 'q1'),
            Number::make(__('Q2'), 'q2'),
            Number::make(__('Q3'), 'q3'),
            Number::make(__('Q4'), 'q4'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Class'), 'class', resource: \App\MoonShine\Resources\ClassesModel\ClassesModelResource::class),
            BelongsTo::make(__('Student'), 'student', resource: \App\MoonShine\Resources\Student\StudentResource::class),
            BelongsTo::make(__('Grade'), 'grade', resource: \App\MoonShine\Resources\Grade\GradeResource::class),
            BelongsTo::make(__('Subject'), 'subject', resource: \App\MoonShine\Resources\Subject\SubjectResource::class),
            Number::make(__('Q1'), 'q1'),
            Number::make(__('Q2'), 'q2'),
            Number::make(__('Q3'), 'q3'),
            Number::make(__('Q4'), 'q4'),
        ];
    }
}
