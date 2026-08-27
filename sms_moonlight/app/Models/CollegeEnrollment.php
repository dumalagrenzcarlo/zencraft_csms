<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class CollegeEnrollment extends Model
{
    public const STATUSES = [
        'enrolled' => 'Enrolled',
        'pending' => 'Pending',
        'completed' => 'Completed',
        'withdrawn' => 'Withdrawn',
    ];

    protected $fillable = [
        'student_id',
        'program_id',
        'school_year_id',
        'semester',
        'year_level',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'program_id' => 'integer',
            'school_year_id' => 'integer',
            'semester' => 'integer',
            'year_level' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(CollegeProgram::class, 'program_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(CollegeEnrollmentCourse::class, 'enrollment_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $student = trim(($this->student?->lastname ?? '').', '.($this->student?->firstname ?? ''), ' ,');

        $semester = CollegeProgramCourse::SEMESTERS[$this->semester] ?? 'Semester '.$this->semester;

        return trim($student.' - '.($this->schoolYear?->school_year ?? '').' - '.$semester, ' -');
    }

    protected static function booted(): void
    {
        static::saving(function (CollegeEnrollment $enrollment): void {
            $program = CollegeProgram::query()->find($enrollment->program_id);

            if (! $program
                || ! Student::query()->whereKey($enrollment->student_id)->exists()
                || ! SchoolYear::query()->whereKey($enrollment->school_year_id)->exists()) {
                throw ValidationException::withMessages([
                    'student_id' => 'The college enrollment requires a valid student, course, and school year.',
                ]);
            }

            if (! array_key_exists((string) $enrollment->status, self::STATUSES)) {
                throw ValidationException::withMessages([
                    'status' => 'Select a valid enrollment status.',
                ]);
            }

            if ((int) $enrollment->year_level < 1
                || (int) $enrollment->year_level > (int) $program->duration_years) {
                throw ValidationException::withMessages([
                    'year_level' => 'The year level exceeds the selected program duration.',
                ]);
            }

            if (! in_array((int) $enrollment->semester, [1, 2], true)) {
                throw ValidationException::withMessages([
                    'semester' => 'College enrollments use First or Second Semester.',
                ]);
            }

            $duplicateExists = self::query()
                ->where('student_id', $enrollment->student_id)
                ->where('school_year_id', $enrollment->school_year_id)
                ->where('semester', $enrollment->semester)
                ->where('year_level', $enrollment->year_level)
                ->when(
                    $enrollment->exists,
                    fn ($query) => $query->whereKeyNot($enrollment->getKey())
                )
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'student_id' => 'This student is already enrolled for the selected school year, semester, and year level. Open the existing enrollment instead.',
                ]);
            }
        });

        static::saved(function (CollegeEnrollment $enrollment): void {
            if ($enrollment->status !== 'enrolled') {
                return;
            }

            self::query()
                ->where('student_id', $enrollment->student_id)
                ->where('school_year_id', $enrollment->school_year_id)
                ->where('semester', $enrollment->semester)
                ->whereKeyNot($enrollment->getKey())
                ->where('status', 'enrolled')
                ->update(['status' => 'completed']);
        });
    }
}
