<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClassesModel;
use App\Models\Student;
use App\Models\StudentAccess;
use Illuminate\Support\Facades\DB;

final class StudentArchiver
{
    public function archive(Student $student): bool
    {
        if ($student->archived) {
            return false;
        }

        DB::transaction(function () use ($student): void {
            $student->updateQuietly([
                'archived' => true,
                'archive_date' => now(),
            ]);

            StudentAccess::query()
                ->where('student_id', $student->getKey())
                ->update(['active' => 0]);
        });

        return true;
    }

    public function restore(Student $student): bool
    {
        if (! $student->archived) {
            return false;
        }

        DB::transaction(function () use ($student): void {
            $student->updateQuietly([
                'archived' => false,
                'archive_date' => null,
            ]);
            $student->syncMoonshineUser();
        });

        return true;
    }

    public function archiveClass(ClassesModel $class): int
    {
        $students = Student::query()
            ->active()
            ->whereHas('classStudents', static fn ($query) => $query->where('class_id', $class->getKey()))
            ->get();

        $archived = 0;

        foreach ($students as $student) {
            $archived += $this->archive($student) ? 1 : 0;
        }

        return $archived;
    }
}
