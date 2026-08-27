<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CollegeProgram extends Model
{
    protected $fillable = ['code', 'name', 'duration_years', 'active'];

    protected function casts(): array
    {
        return [
            'duration_years' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(CollegeProgramCourse::class, 'program_id')
            ->orderBy('year_level')
            ->orderBy('semester')
            ->orderBy('course_order')
            ->orderBy('id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CollegeEnrollment::class, 'program_id');
    }

    public function offerings(): HasManyThrough
    {
        return $this->hasManyThrough(
            CollegeCourseOffering::class,
            CollegeProgramCourse::class,
            'program_id',
            'program_course_id'
        );
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->code.' - '.$this->name);
    }
}
