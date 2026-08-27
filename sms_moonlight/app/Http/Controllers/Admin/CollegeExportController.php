<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\CollegeCourseOffering;
use App\Models\CollegeEnrollmentCourse;
use App\Models\CollegeProgram;
use App\Models\CollegeProgramCourse;
use App\Support\CsvCell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CollegeExportController extends Controller
{
    public function grades(Request $request): StreamedResponse
    {
        $this->ensureCollegeModuleIsEnabled();

        $query = CollegeEnrollmentCourse::query()->with([
            'enrollment.student',
            'enrollment.program',
            'enrollment.schoolYear',
            'programCourse',
            'offering.instructor',
        ]);

        $this->applyGradeFilters($query, $request);

        return $this->csv('college-grades', [
            'ID',
            'Student Number',
            'Student',
            'Course',
            'School Year',
            'Year Level',
            'Semester',
            'Class Code',
            'Class Description',
            'Section',
            'Instructor / Professor',
            'Prelim',
            'Midterm',
            'Pre-final',
            'Final',
            'Remarks',
            'Submitted At',
        ], $query, static function (CollegeEnrollmentCourse $grade): array {
            $enrollment = $grade->enrollment;
            $student = $enrollment?->student;
            $class = $grade->programCourse;
            $offering = $grade->offering;

            return [
                $grade->id,
                $student?->lrn,
                trim(($student?->lastname ?? '').', '.($student?->firstname ?? ''), ' ,'),
                $enrollment?->program?->display_name,
                $enrollment?->schoolYear?->school_year,
                CollegeProgramCourse::yearLevelLabel($enrollment?->year_level),
                CollegeProgramCourse::SEMESTERS[$enrollment?->semester] ?? $enrollment?->semester,
                $class?->course_code,
                $class?->description,
                $offering?->section,
                $offering?->instructor?->name,
                $grade->prelim_grade,
                $grade->midterm_grade,
                $grade->prefinal_grade,
                $grade->final_grade,
                $grade->remarks,
                $grade->grades_submitted_at?->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function schedules(Request $request): StreamedResponse
    {
        $this->ensureCollegeModuleIsEnabled();

        $query = CollegeCourseOffering::query()->with([
            'schoolYear',
            'programCourse.program',
            'instructor',
        ]);

        $this->applyScheduleFilters($query, $request);

        return $this->csv('college-class-schedules', [
            'ID',
            'School Year',
            'Course',
            'Class Code',
            'Class Description',
            'Year Level',
            'Semester',
            'Instructor / Professor',
            'Section',
            'Schedule',
            'Room',
            'Capacity',
            'Active',
        ], $query, static function (CollegeCourseOffering $schedule): array {
            $class = $schedule->programCourse;

            return [
                $schedule->id,
                $schedule->schoolYear?->school_year,
                $class?->program?->display_name,
                $class?->course_code,
                $class?->description,
                CollegeProgramCourse::yearLevelLabel($class?->year_level),
                CollegeProgramCourse::SEMESTERS[$class?->semester] ?? $class?->semester,
                $schedule->instructor?->name,
                $schedule->section,
                $schedule->schedule,
                $schedule->room,
                $schedule->capacity,
                $schedule->active ? 'Yes' : 'No',
            ];
        });
    }

    public function courses(Request $request): StreamedResponse
    {
        $this->ensureCollegeModuleIsEnabled();

        $query = CollegeProgram::query();
        $this->applyCourseFilters($query, $request);

        return $this->csv('college-courses', [
            'ID',
            'Course Code',
            'Course Name',
            'Duration (Years)',
            'Active',
        ], $query, static function (CollegeProgram $program): array {
            return [
                $program->id,
                $program->code,
                $program->name,
                $program->duration_years,
                $program->active ? 'Yes' : 'No',
            ];
        });
    }

    private function applyGradeFilters(Builder $query, Request $request): void
    {
        $filters = (array) $request->input('filter', []);

        $query
            ->when($filters['student_keyword'] ?? null, function (Builder $query, string $keyword): void {
                $query->whereHas('enrollment.student', function (Builder $studentQuery) use ($keyword): void {
                    $search = '%'.trim($keyword).'%';
                    $studentQuery->where(function (Builder $nameQuery) use ($search): void {
                        $nameQuery->where('lrn', 'like', $search)
                            ->orWhere('firstname', 'like', $search)
                            ->orWhere('middlename', 'like', $search)
                            ->orWhere('lastname', 'like', $search);
                    });
                });
            })
            ->when($filters['program_id'] ?? null, fn (Builder $query, $value) => $query->whereHas('enrollment', fn (Builder $enrollment) => $enrollment->where('program_id', $value)))
            ->when($filters['school_year_id'] ?? null, fn (Builder $query, $value) => $query->whereHas('enrollment', fn (Builder $enrollment) => $enrollment->where('school_year_id', $value)))
            ->when($filters['year_level'] ?? null, fn (Builder $query, $value) => $query->whereHas('enrollment', fn (Builder $enrollment) => $enrollment->where('year_level', $value)))
            ->when($filters['semester'] ?? null, fn (Builder $query, $value) => $query->whereHas('enrollment', fn (Builder $enrollment) => $enrollment->where('semester', $value)))
            ->when($filters['offering_id'] ?? null, fn (Builder $query, $value) => $query->where('offering_id', $value))
            ->when($filters['remarks'] ?? null, fn (Builder $query, $value) => $query->where('remarks', $value));
    }

    private function applyScheduleFilters(Builder $query, Request $request): void
    {
        $filters = (array) $request->input('filter', []);

        $query
            ->when($filters['school_year_id'] ?? null, fn (Builder $query, $value) => $query->where('school_year_id', $value))
            ->when($filters['program_id'] ?? null, fn (Builder $query, $value) => $query->whereHas('programCourse', fn (Builder $class) => $class->where('program_id', $value)))
            ->when($filters['program_course_id'] ?? null, fn (Builder $query, $value) => $query->where('program_course_id', $value))
            ->when($filters['instructor_id'] ?? null, fn (Builder $query, $value) => $query->where('instructor_id', $value))
            ->when(array_key_exists('active', $filters) && $filters['active'] !== '', fn (Builder $query) => $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN)));
    }

    private function applyCourseFilters(Builder $query, Request $request): void
    {
        $filters = (array) $request->input('filter', []);

        $query
            ->when($filters['code'] ?? null, fn (Builder $query, $value) => $query->where('code', 'like', '%'.$value.'%'))
            ->when($filters['name'] ?? null, fn (Builder $query, $value) => $query->where('name', 'like', '%'.$value.'%'))
            ->when(array_key_exists('active', $filters) && $filters['active'] !== '', fn (Builder $query) => $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN)));
    }

    /**
     * @param  list<string>  $headings
     * @param  callable(mixed): list<mixed>  $map
     */
    private function csv(string $prefix, array $headings, Builder $query, callable $map): StreamedResponse
    {
        $filename = $prefix.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headings, $query, $map): void {
            $output = fopen('php://output', 'wb');

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headings);

            $query->orderBy('id')->chunkById(500, function ($rows) use ($output, $map): void {
                foreach ($rows as $row) {
                    fputcsv($output, CsvCell::row($map($row)));
                }
            });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function ensureCollegeModuleIsEnabled(): void
    {
        abort_unless((bool) config('school_portal.features.college_module'), 404);
    }
}
