<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\ClassesModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AttendanceTardySummary
{
    /**
     * Count students whose first recorded scan on a date was after class start.
     *
     * @param  Collection<int, int>|array<int, int>  $studentIds
     */
    public static function countForClassDate(
        ?ClassesModel $class,
        Collection|array $studentIds,
        string $date,
    ): int {
        $studentIds = collect($studentIds)->map(static fn ($id): int => (int) $id)->unique()->values();

        if (! $class?->start_time || $studentIds->isEmpty()) {
            return 0;
        }

        return AttendanceRecord::query()
            ->whereIn('student_id', $studentIds->all())
            ->whereDate('currentdate', $date)
            ->whereNotNull('logged_time')
            ->select('student_id')
            ->groupBy('student_id')
            ->havingRaw('MIN(logged_time) > ?', [$class->start_time])
            ->get()
            ->count();
    }

    /**
     * Return every class in the school year with its number of late arrival days.
     *
     * One student contributes at most one late arrival per date.
     *
     * @return Collection<int, array{class_id: int, label: string, total: int}>
     */
    public static function perClass(
        ?int $schoolYearId,
        Collection|array|null $classIds = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): Collection {
        if ($schoolYearId === null) {
            return collect();
        }

        $classIds = $classIds === null
            ? null
            : collect($classIds)->map(static fn ($id): int => (int) $id)->unique()->values();

        $classes = ClassesModel::query()
            ->with('grade:id,grade')
            ->where('school_year_id', $schoolYearId)
            ->when($classIds !== null, fn ($classes) => $classes->whereIn('id', $classIds->all()))
            ->orderBy('grade_id')
            ->orderBy('section')
            ->get(['id', 'grade_id', 'section', 'start_time']);

        if ($classes->isEmpty()) {
            return collect();
        }

        $firstScans = DB::table('attendance_record')
            ->join('class_students', function ($join): void {
                $join->on('class_students.student_id', '=', 'attendance_record.student_id');
            })
            ->join('classes', 'classes.id', '=', 'class_students.class_id')
            ->where('class_students.school_year_id', $schoolYearId)
            ->where('classes.school_year_id', $schoolYearId)
            ->whereNotNull('classes.start_time')
            ->whereNotNull('attendance_record.currentdate')
            ->whereNotNull('attendance_record.logged_time')
            ->when($classIds !== null, fn ($records) => $records->whereIn('classes.id', $classIds->all()))
            ->when($dateFrom !== null, fn ($records) => $records->whereDate('attendance_record.currentdate', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($records) => $records->whereDate('attendance_record.currentdate', '<=', $dateTo))
            ->select('classes.id as class_id')
            ->selectRaw('attendance_record.currentdate as attendance_date')
            ->selectRaw('attendance_record.student_id as student_id')
            ->groupBy(
                'classes.id',
                'classes.start_time',
                'attendance_record.currentdate',
                'attendance_record.student_id',
            )
            ->havingRaw('MIN(attendance_record.logged_time) > classes.start_time');

        $counts = DB::query()
            ->fromSub($firstScans, 'late_first_scans')
            ->select('class_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('class_id')
            ->pluck('total', 'class_id');

        return $classes->map(static function (ClassesModel $class) use ($counts): array {
            $grade = $class->grade?->grade ?? 'Grade';

            return [
                'class_id' => (int) $class->id,
                'label' => trim($grade.' - '.$class->section),
                'total' => (int) ($counts[$class->id] ?? 0),
            ];
        })->values();
    }
}
