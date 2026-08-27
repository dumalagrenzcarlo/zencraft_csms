<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\CollegeEnrollmentCourseAssigner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class CollegeProgramCourse extends Model
{
    public const YEAR_LEVELS = [
        1 => '1st Year',
        2 => '2nd Year',
        3 => '3rd Year',
        4 => '4th Year',
    ];

    public const SEMESTERS = [
        1 => 'First Semester',
        2 => 'Second Semester',
    ];

    protected $table = 'college_curriculum_subjects';

    protected $fillable = [
        'program_id',
        'course_code',
        'description',
        'prerequisite_program_course_id',
        'year_level',
        'semester',
        'units',
        'course_order',
    ];

    protected function casts(): array
    {
        return [
            'program_id' => 'integer',
            'prerequisite_program_course_id' => 'integer',
            'year_level' => 'integer',
            'semester' => 'integer',
            'units' => 'decimal:2',
            'course_order' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(CollegeProgram::class, 'program_id');
    }

    public function prerequisiteProgramCourse(): BelongsTo
    {
        return $this->belongsTo(self::class, 'prerequisite_program_course_id');
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(CollegeCourseOffering::class, 'program_course_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $program = $this->program?->code;
        $course = trim($this->course_code.' - '.$this->description, ' -');

        return collect([
            $program,
            $course,
            self::yearLevelLabel($this->year_level),
            self::SEMESTERS[$this->semester] ?? 'Semester '.$this->semester,
        ])->filter()->implode(' - ');
    }

    public static function yearLevelLabel(int|string|null $yearLevel): string
    {
        if ($yearLevel === null || $yearLevel === '') {
            return '-';
        }

        return self::YEAR_LEVELS[(int) $yearLevel] ?? 'Year '.$yearLevel;
    }

    protected static function booted(): void
    {
        static::saving(function (CollegeProgramCourse $item): void {
            $item->course_code = trim((string) $item->course_code);
            $item->description = trim((string) $item->description);

            if ($item->course_code === '' || $item->description === '') {
                throw ValidationException::withMessages([
                    'course_code' => 'A college class code and description are required.',
                ]);
            }

            if (mb_strlen($item->course_code) > 30 || mb_strlen($item->description) > 255) {
                throw ValidationException::withMessages([
                    'description' => 'College class codes are limited to 30 characters and descriptions to 255 characters.',
                ]);
            }

            $program = CollegeProgram::query()->find($item->program_id);
            $durationYears = (int) ($program?->duration_years ?? 0);

            if ((int) $item->year_level < 1
                || ($durationYears > 0 && (int) $item->year_level > $durationYears)) {
                throw ValidationException::withMessages([
                    'year_level' => 'The class year level must be within the course duration.',
                ]);
            }

            if (! array_key_exists((int) $item->semester, self::SEMESTERS)) {
                throw ValidationException::withMessages([
                    'semester' => 'The selected semester is invalid.',
                ]);
            }

            $duplicateExists = self::query()
                ->where('program_id', $item->program_id)
                ->whereRaw('LOWER(course_code) = ?', [mb_strtolower($item->course_code)])
                ->where('year_level', $item->year_level)
                ->where('semester', $item->semester)
                ->when(
                    $item->exists,
                    fn ($query) => $query->whereKeyNot($item->getKey())
                )
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'course_code' => 'This class code already exists for the selected course, year level, and semester.',
                ]);
            }

            if ((float) $item->units < 0) {
                throw ValidationException::withMessages([
                    'units' => 'Class units cannot be negative.',
                ]);
            }

            if ($item->prerequisite_program_course_id
                && (int) $item->prerequisite_program_course_id === (int) $item->getKey()) {
                throw ValidationException::withMessages([
                    'prerequisite_program_course_id' => 'A class cannot be its own prerequisite.',
                ]);
            }

            if ($item->prerequisite_program_course_id) {
                $prerequisite = self::query()->find($item->prerequisite_program_course_id);

                if (! $prerequisite || (int) $prerequisite->program_id !== (int) $item->program_id) {
                    throw ValidationException::withMessages([
                        'prerequisite_program_course_id' => 'The prerequisite must belong to the same course.',
                    ]);
                }
            }
        });

        static::created(function (CollegeProgramCourse $programCourse): void {
            $assigner = app(CollegeEnrollmentCourseAssigner::class);

            CollegeEnrollment::query()
                ->where('program_id', $programCourse->program_id)
                ->where('year_level', $programCourse->year_level)
                ->where('semester', $programCourse->semester)
                ->where('status', 'enrolled')
                ->orderBy('id')
                ->eachById(
                    static fn (CollegeEnrollment $enrollment) => $assigner->assignAvailableCourses($enrollment)
                );
        });
    }
}
