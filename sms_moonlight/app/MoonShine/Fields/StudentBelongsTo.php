<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use App\MoonShine\Resources\Student\StudentResource;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;

final class StudentBelongsTo
{
    public static function make(
        string $label = 'Student',
        string $relation = 'student',
        string $resource = StudentResource::class,
    ): BelongsTo {
        $formatStudent = static fn ($student): string => trim(
            "$student->lastname, $student->firstname".
            ($student->lrn ? " — LRN: $student->lrn" : '')
        );

        return BelongsTo::make(
            $label,
            $relation,
            $formatStudent,
            resource: $resource,
        )
            ->placeholder('--Search student by LRN/student number/name--')
            ->asyncSearch(
                column: 'lrn',
                searchQuery: static function ($query, string $term) {
                    return $query->where(function ($studentQuery) use ($term): void {
                        $studentQuery
                            ->where('lrn', 'like', "%{$term}%")
                            ->orWhere('student_number', 'like', "%{$term}%")
                            ->orWhere('firstname', 'like', "%{$term}%")
                            ->orWhere('lastname', 'like', "%{$term}%")
                            ->orWhere('middlename', 'like', "%{$term}%");
                    });
                },
                formatted: $formatStudent,
                limit: 15,
            );
    }
}
