<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\CollegeEnrollmentCourseAssigner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class CollegeCourseOffering extends Model
{
    protected $fillable = [
        'school_year_id',
        'program_course_id',
        'instructor_id',
        'section',
        'schedule',
        'room',
        'capacity',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'school_year_id' => 'integer',
            'program_course_id' => 'integer',
            'instructor_id' => 'integer',
            'capacity' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function programCourse(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramCourse::class, 'program_course_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function enrollmentCourses(): HasMany
    {
        return $this->hasMany(CollegeEnrollmentCourse::class, 'offering_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $course = $this->programCourse
            ? $this->programCourse->display_name
            : 'College class';

        return trim($course.' - '.$this->section, ' -');
    }

    public function scopeUnscheduled(Builder $query): Builder
    {
        return $query->where(function (Builder $schedule): void {
            $schedule
                ->whereNull('schedule')
                ->orWhereRaw("TRIM(schedule) = ''");
        });
    }

    protected static function booted(): void
    {
        static::saving(function (CollegeCourseOffering $offering): void {
            if (! $offering->program_course_id) {
                return;
            }

            $programCourse = CollegeProgramCourse::query()
                ->find($offering->program_course_id);
            $instructorExists = Instructor::query()->whereKey($offering->instructor_id)->exists();

            if (! $programCourse) {
                throw ValidationException::withMessages([
                    'program_course_id' => 'The selected class is invalid.',
                ]);
            }

            if (! $instructorExists) {
                throw ValidationException::withMessages([
                    'instructor_id' => 'College classes must be assigned to an instructor or professor.',
                ]);
            }
        });

        static::saved(function (CollegeCourseOffering $offering): void {
            $offering->refresh();

            if (! $offering->active) {
                return;
            }

            $programCourse = $offering->programCourse;

            if (! $programCourse) {
                return;
            }

            $assigner = app(CollegeEnrollmentCourseAssigner::class);
            CollegeEnrollment::query()
                ->where('program_id', $programCourse->program_id)
                ->where('school_year_id', $offering->school_year_id)
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
