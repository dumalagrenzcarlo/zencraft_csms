<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class CollegeEnrollmentCourse extends Model
{
    protected $fillable = [
        'enrollment_id',
        'program_course_id',
        'offering_id',
        'prelim_grade',
        'midterm_grade',
        'prefinal_grade',
        'final_grade',
        'remarks',
        'grades_submitted_at',
        'grades_submitted_by',
    ];

    protected function casts(): array
    {
        return [
            'enrollment_id' => 'integer',
            'program_course_id' => 'integer',
            'offering_id' => 'integer',
            'prelim_grade' => 'decimal:2',
            'midterm_grade' => 'decimal:2',
            'prefinal_grade' => 'decimal:2',
            'final_grade' => 'decimal:2',
            'grades_submitted_at' => 'datetime',
            'grades_submitted_by' => 'integer',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CollegeEnrollment::class, 'enrollment_id');
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CollegeCourseOffering::class, 'offering_id');
    }

    public function programCourse(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramCourse::class, 'program_course_id');
    }

    public function gradesAreSubmitted(): bool
    {
        return filled($this->grades_submitted_at);
    }

    protected static function booted(): void
    {
        static::saving(function (CollegeEnrollmentCourse $course): void {
            $enrollment = CollegeEnrollment::query()->find($course->enrollment_id);
            $offering = $course->offering_id
                ? CollegeCourseOffering::query()->find($course->offering_id)
                : null;

            if ($offering) {
                $course->program_course_id = $offering->program_course_id;
            }

            $programCourse = CollegeProgramCourse::query()->find($course->program_course_id);

            if (! $enrollment || ($offering
                && (int) $enrollment->school_year_id !== (int) $offering->school_year_id)) {
                throw ValidationException::withMessages([
                    'offering_id' => 'The class must belong to the enrollment school year.',
                ]);
            }

            if (! $programCourse
                || (int) $programCourse->program_id !== (int) $enrollment->program_id
                || (int) $programCourse->year_level !== (int) $enrollment->year_level
                || (int) $programCourse->semester !== (int) $enrollment->semester) {
                throw ValidationException::withMessages([
                    'offering_id' => 'The class must match the student course, year level, and semester.',
                ]);
            }

            $duplicateExists = static::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('program_course_id', $programCourse->id)
                ->when($course->exists, fn ($query) => $query->whereKeyNot($course->getKey()))
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'program_course_id' => 'The student is already enrolled in this class.',
                ]);
            }

            if (! $offering) {
                return;
            }

            $enrolledCount = static::query()
                ->where('offering_id', $offering->id)
                ->when($course->exists, fn ($query) => $query->whereKeyNot($course->getKey()))
                ->count();

            if ($enrolledCount >= (int) $offering->capacity) {
                throw ValidationException::withMessages([
                    'offering_id' => 'The selected class has reached its capacity.',
                ]);
            }
        });
    }
}
