<?php

declare(strict_types=1);

namespace App\Services\MoonShine;

use App\Models\ClassesModel;
use App\Models\ClassStudent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ClassStudentSaveHandler
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Model $model, array $data): Model
    {
        /** @var ClassStudent $model */
        $classId = (int) ($data['class_id'] ?? $model->class_id ?? request()->getScalar('_parentId') ?? 0);
        $class = ClassesModel::query()->findOrFail($classId);

        $studentIds = collect(Arr::wrap($data['student_ids'] ?? $data['student_id'] ?? $model->student_id ?? []))
            ->filter()
            ->map(fn ($studentId): int => (int) $studentId)
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return $model;
        }

        $sharedData = [
            'class_id' => $class->id,
            'school_year_id' => $class->school_year_id,
            'hidden_grade' => (bool) ($data['hidden_grade'] ?? false),
            'notes' => $data['notes'] ?? null,
        ];

        if ($model->exists) {
            $model->forceFill([
                ...$sharedData,
                'student_id' => $studentIds->first(),
            ])->save();

            return $model;
        }

        $firstCreated = null;

        foreach ($studentIds as $studentId) {
            $created = ClassStudent::query()->updateOrCreate(
                [
                    'class_id' => $class->id,
                    'student_id' => $studentId,
                    'school_year_id' => $class->school_year_id,
                ],
                $sharedData
            );

            $firstCreated ??= $created;
        }

        return $firstCreated ?? $model;
    }
}
