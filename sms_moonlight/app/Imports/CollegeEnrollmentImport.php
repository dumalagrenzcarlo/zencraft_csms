<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\CollegeEnrollment;
use App\Models\CollegeProgram;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Support\CollegeEnrollmentCourseAssigner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CollegeEnrollmentImport implements ToCollection, WithMultipleSheets
{
    public int $created = 0;

    public int $updated = 0;

    public int $totalRows = 0;

    public int $errorRows = 0;

    public int $duplicateRows = 0;

    private const REQUIRED_HEADERS = [
        'lrn',
        'studentname',
        'coursecode',
        'schoolyear',
        'semester',
        'yearlevel',
        'status',
    ];

    public function __construct(
        private readonly CollegeEnrollmentCourseAssigner $courseAssigner,
    ) {}

    public function sheets(): array
    {
        return [0 => $this];
    }

    public function collection(Collection $rows): void
    {
        @set_time_limit(0);

        $activeSchoolYear = SchoolYear::query()->where('active', true)->first();

        if (! $activeSchoolYear) {
            throw ValidationException::withMessages([
                'file' => 'No active school year is configured. Set an active school year before importing.',
            ]);
        }

        $headerRow = $rows->shift();

        if ($rows->count() > 5000) {
            throw ValidationException::withMessages([
                'file' => 'A college enrollment import can contain at most 5,000 data rows.',
            ]);
        }

        $headerIndexes = $this->headerIndexes($headerRow);
        $missingHeaders = array_diff(self::REQUIRED_HEADERS, array_keys($headerIndexes));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'file' => 'The template is missing required columns: '.implode(', ', array_map(
                    static fn (string $header): string => match ($header) {
                        'studentname' => 'Student Name',
                        'coursecode' => 'Course Code',
                        'schoolyear' => 'School Year',
                        'yearlevel' => 'Year Level',
                        default => ucfirst($header),
                    },
                    $missingHeaders,
                )).'.',
            ]);
        }

        $parsedRows = [];

        foreach ($rows as $index => $row) {
            $values = $row instanceof Collection ? $row->all() : (array) $row;

            if ($this->rowIsEmpty($values, $headerIndexes)) {
                continue;
            }

            $spreadsheetRow = $index + 2;
            $lrn = trim((string) ($values[$headerIndexes['lrn']] ?? ''));
            $courseCode = trim((string) ($values[$headerIndexes['coursecode']] ?? ''));
            $semester = $this->semesterValue($values[$headerIndexes['semester']] ?? null);
            $yearLevel = filter_var(
                $values[$headerIndexes['yearlevel']] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]],
            );
            $status = $this->statusValue($values[$headerIndexes['status']] ?? null);

            $parsedRows[] = compact('spreadsheetRow', 'lrn', 'courseCode', 'semester', 'yearLevel', 'status');
        }

        if ($parsedRows === []) {
            throw ValidationException::withMessages(['file' => 'The spreadsheet does not contain any enrolment rows.']);
        }

        $this->totalRows = count($parsedRows);

        $students = Student::query()
            ->whereIn('lrn', collect($parsedRows)->pluck('lrn')->filter()->unique()->values())
            ->get()
            ->keyBy(static fn (Student $student): string => (string) $student->lrn);
        $programs = CollegeProgram::query()
            ->get()
            ->keyBy(static fn (CollegeProgram $program): string => strtolower($program->code));

        $validatedRows = [];
        $errors = [];
        $keysInFile = [];

        foreach ($parsedRows as $parsedRow) {
            $spreadsheetRow = $parsedRow['spreadsheetRow'];
            $lrn = $parsedRow['lrn'];
            $courseCode = $parsedRow['courseCode'];
            $semester = $parsedRow['semester'];
            $yearLevel = $parsedRow['yearLevel'];
            $status = $parsedRow['status'];

            $student = $lrn === '' ? null : $students->get($lrn);
            $program = $courseCode === '' ? null : $programs->get(strtolower($courseCode));

            $rowErrors = [];

            if ($lrn === '') {
                $rowErrors[] = 'LRN is required';
            } elseif (! $student) {
                $rowErrors[] = "LRN {$lrn} does not match an existing student";
            }

            if ($courseCode === '') {
                $rowErrors[] = 'Course Code is required';
            } elseif (! $program) {
                $rowErrors[] = "Course Code {$courseCode} was not found";
            } elseif (! $program->active) {
                $rowErrors[] = "Course Code {$courseCode} is inactive";
            }

            if ($semester === null) {
                $rowErrors[] = 'Semester must be 1, 2, First Semester, or Second Semester';
            }

            if ($yearLevel === false) {
                $rowErrors[] = 'Year Level must be a whole number starting at 1';
            } elseif ($program && $yearLevel > (int) $program->duration_years) {
                $rowErrors[] = "Year Level exceeds the {$program->duration_years}-year duration of {$program->code}";
            }

            if ($status === null) {
                $rowErrors[] = 'Status must be Enrolled, Pending, Completed, or Withdrawn';
            }

            if ($student && $semester !== null && $yearLevel !== false) {
                $key = implode('|', [$student->id, $activeSchoolYear->id, $semester, $yearLevel]);

                if (isset($keysInFile[$key])) {
                    $rowErrors[] = "duplicates spreadsheet row {$keysInFile[$key]}";
                    $this->duplicateRows++;
                } else {
                    $keysInFile[$key] = $spreadsheetRow;
                }
            }

            if ($rowErrors !== []) {
                $this->errorRows++;
                $errors[] = "Row {$spreadsheetRow}: ".implode('; ', $rowErrors).'.';

                continue;
            }

            $validatedRows[] = compact('student', 'program', 'semester', 'yearLevel', 'status');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => [
                "Import failed. Rows processed: {$this->totalRows}. Imported: 0. Error rows: {$this->errorRows}. Duplicate rows in file: {$this->duplicateRows}. No changes were saved.",
                ...$errors,
            ]]);
        }

        $existingEnrollments = CollegeEnrollment::query()
            ->where('school_year_id', $activeSchoolYear->id)
            ->whereIn('student_id', collect($validatedRows)->pluck('student.id')->unique()->values())
            ->get()
            ->keyBy(fn (CollegeEnrollment $enrollment): string => $this->enrollmentKey(
                $enrollment->student_id,
                $enrollment->semester,
                $enrollment->year_level,
            ));

        DB::transaction(function () use ($validatedRows, $activeSchoolYear, $existingEnrollments): void {
            $enrollmentsToAssign = collect();

            foreach ($validatedRows as $row) {
                $key = $this->enrollmentKey($row['student']->id, $row['semester'], $row['yearLevel']);
                $enrollment = $existingEnrollments->get($key);
                $wasRecentlyCreated = ! $enrollment;

                if (! $enrollment) {
                    $enrollment = new CollegeEnrollment([
                        'student_id' => $row['student']->id,
                        'school_year_id' => $activeSchoolYear->id,
                        'semester' => $row['semester'],
                        'year_level' => $row['yearLevel'],
                    ]);
                }

                $enrollment->fill([
                    'program_id' => $row['program']->id,
                    'status' => $row['status'],
                ])->save();

                $wasRecentlyCreated ? $this->created++ : $this->updated++;
                $enrollmentsToAssign->push($enrollment);
            }

            $this->courseAssigner->assignAvailableCoursesToMany($enrollmentsToAssign);
        });
    }

    private function headerIndexes(mixed $headerRow): array
    {
        if ($headerRow instanceof Collection) {
            $headerRow = $headerRow->all();
        }

        return collect((array) $headerRow)
            ->mapWithKeys(fn (mixed $header, int $index): array => [$this->normalize($header) => $index])
            ->filter(static fn (int $index, string $header): bool => $header !== '')
            ->all();
    }

    private function rowIsEmpty(array $row, array $headerIndexes): bool
    {
        return collect(['lrn', 'coursecode', 'semester', 'yearlevel', 'status'])
            ->every(static fn (string $header): bool => trim((string) ($row[$headerIndexes[$header]] ?? '')) === '');
    }

    private function normalize(mixed $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', trim((string) $value)) ?? '');
    }

    private function semesterValue(mixed $value): ?int
    {
        return match ($this->normalize($value)) {
            '1', 'first', '1st', 'firstsemester', '1stsemester' => 1,
            '2', 'second', '2nd', 'secondsemester', '2ndsemester' => 2,
            default => null,
        };
    }

    private function statusValue(mixed $value): ?string
    {
        $status = $this->normalize($value);

        return array_key_exists($status, CollegeEnrollment::STATUSES) ? $status : null;
    }

    private function enrollmentKey(int $studentId, int $semester, int $yearLevel): string
    {
        return implode('|', [$studentId, $semester, $yearLevel]);
    }
}
